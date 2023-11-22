<?php
declare(strict_types = 1);

namespace AndreasWolf\FrontendTest\Service;

use Nimut\TestingFramework\TestSystem\AbstractTestSystem;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extension of the underlying test system that lets us run the setup within an existing HTTP request (as opposed to
 * running it from the PHPUnit test runner, as is normally done for functional tests).
 */
class TestSystem extends AbstractTestSystem
{
    protected $tablesToNotTruncate = [
        'static_countries',
        'static_country_zones',
        'static_currencies',
        'static_languages',
        'static_territories',
    ];

    protected function includeAndStartCoreBootstrap(): void
    {
        // no-op, since we're already within a TYPO3 request
    }

    /**
     * Resets the test database to its original state, by truncating the tables.
     */
    protected function initializeTestDatabase(): void
    {
        $originalDatabaseName = $this->changeDatabaseName();

        parent::initializeTestDatabase();

        $this->restoreOriginalDatabaseName($originalDatabaseName);
    }

    /**
     * Creates (or drops and recreates) the test database for a fresh test environment.
     */
    protected function setUpTestDatabase(): void
    {
        $originalDatabaseName = $this->changeDatabaseName();

        parent::setUpTestDatabase();

        $this->restoreOriginalDatabaseName($originalDatabaseName);
    }

    /**
     * Creates the table schemata in the test database based on the installed extensions.
     *
     * This uses an override for the PackageManager, to get the list of available/activated packages from the test
     * system instead of the "mother" TYPO3 system.
     */
    protected function createDatabaseStructure(): void
    {
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);

        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 11) {
            $packageManager = GeneralUtility::makeInstance(ContainerInterface::class)->get(TestSystemPackageManager::class);
        } else {
            // fallback for TYPO3 v9
            $packageManager = GeneralUtility::makeInstance(TestSystemPackageManager::class);
        }
        $packageManager->setTestSystemPath($this->systemPath);
        $packageManager->initialize();

        $sqlReader = GeneralUtility::makeInstance(SqlReader::class, GeneralUtility::makeInstance(EventDispatcherInterface::class), $packageManager);
        ///////////// method is unmodified from here on
        $sqlCode = $sqlReader->getTablesDefinitionString(true);

        $createTableStatements = $sqlReader->getCreateTableStatementArray($sqlCode);

        $updateResult = $schemaMigrationService->install($createTableStatements);
        $failedStatements = array_filter($updateResult);
        $result = [];
        /**
         * @var string $query
         * @var string $error
         */
        foreach ($failedStatements as $query => $error) {
            $result[] = 'Query "' . $query . '" returned "' . $error . '"';
        }

        if (!empty($result)) {
            throw new \RuntimeException(implode("\n", $result), 1505058450);
        }

        $insertStatements = $sqlReader->getInsertStatementArray($sqlCode);
        $schemaMigrationService->importStaticData($insertStatements);
    }

    /**
     * This method was copied from the parent class and extended to skip the tables listed in $this->tablesNotToTruncate
     */
    protected function truncateAllTablesForMysql()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $databaseName = $connection->getDatabase();
        $tableNames = $connection->getSchemaManager()->listTableNames();
        $tableNames = array_diff($tableNames, $this->tablesToNotTruncate);

        if (empty($tableNames)) {
            return;
        }

        // Build a sub select to get joinable table with information if table has at least one row.
        // This is needed because information_schema.table_rows is not reliable enough for innodb engine.
        // see https://dev.mysql.com/doc/mysql-infoschema-excerpt/5.7/en/information-schema-tables-table.html TABLE_ROWS
        $fromTableUnionSubSelectQuery = [];
        foreach ($tableNames as $tableName) {
            $fromTableUnionSubSelectQuery[] = sprintf(
                ' SELECT %s AS table_name, exists(SELECT * FROM %s LIMIT 1) AS has_rows',
                $connection->quote($tableName),
                $connection->quoteIdentifier($tableName)
            );
        }
        $fromTableUnionSubSelectQuery = implode(' UNION ', $fromTableUnionSubSelectQuery);
        $query = sprintf(
            'SELECT
                table_real_rowcounts.*,
                information_schema.tables.AUTO_INCREMENT AS auto_increment
            FROM (%s) AS table_real_rowcounts
            INNER JOIN information_schema.tables ON (
                information_schema.tables.TABLE_SCHEMA = %s
                AND information_schema.tables.TABLE_NAME = table_real_rowcounts.table_name
            )',
            $fromTableUnionSubSelectQuery,
            $connection->quote($databaseName)
        );
        $result = $connection->executeQuery($query)->fetchAll();
        foreach ($result as $tableData) {
            $hasChangedAutoIncrement = ((int)$tableData['auto_increment']) > 1;
            $hasRows = (bool)$tableData['has_rows'];
            $isChanged = $hasChangedAutoIncrement || $hasRows;
            if ($isChanged) {
                $connection->truncate($tableData['table_name']);
            }
        }
    }

    /**
     * @return string The original database name for later restore
     */
    public function changeDatabaseName(): string
    {
        $defaultDatabaseConnection = &$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $originalDatabaseName = $defaultDatabaseConnection['dbname'];

        $databaseNameWithoutSuffix = preg_replace('/_ft[0-9a-f]{7}$/', '', $originalDatabaseName);

        $defaultDatabaseConnection['dbname'] = sprintf('%s_ft%s', $databaseNameWithoutSuffix, $this->getSystemIdentifier());

        static::closeDatabaseConnections();

        return $originalDatabaseName;
    }

    public function restoreOriginalDatabaseName(string $originalDatabaseName): void
    {
        $defaultDatabaseConnection = &$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default'];
        $defaultDatabaseConnection['dbname'] = $originalDatabaseName;

        static::closeDatabaseConnections();
    }

    protected static function closeDatabaseConnections(): void
    {
        static $closer;
        if ($closer === null) {
            // The TYPO3 core misses to reset its internal connection state
            // This means we need to reset all connections to ensure database connection can be initialized
            $closer = \Closure::bind(static function () {
                foreach (ConnectionPool::$connections as $connection) {
                    $connection->close();
                }
                ConnectionPool::$connections = [];
            }, null, ConnectionPool::class);
        }
        $closer();
    }
}
