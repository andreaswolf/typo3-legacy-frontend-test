<?php
declare(strict_types = 1);

namespace FGTCLB\HttpApi\Http;

use FGTCLB\HttpApi\Resource\ResourceInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;

/**
 * API specific response with unified format:
 *
 * {
 *   "success" => true,
 *   "data" => {...}
 * }
 *
 * In case of an error:
 *
 * {
 *   "success" => false,
 *   "error" => "..."
 * }
 */
final class ApiResponse extends JsonResponse
{
    /**
     * Build an API response for a successful response
     *
     * @param ResourceInterface|array<string, mixed> $resource the response resource or a plain key-value array
     * @return ApiResponse
     */
    public static function success($resource = null, string $message = ''): self
    {
        $responseData = ['success' => true];
        if (!empty($message)) {
            $responseData['message'] = $message;
        }

        if ($resource instanceof ResourceInterface) {
            $responseData['data'] = $resource->toArray();
        } elseif ($resource !== null) {
            $responseData['data'] = $resource;
        }

        return new self($responseData);
    }

    /**
     * Basic response function for simple OK responses
     *
     * @param string $message The response message
     * @return ApiResponse
     */
    public static function basicSuccess(string $message): self
    {
        return new self(['success' => true, 'message' => $message]);
    }

    /**
     * Build an API response for an error response
     *
     * @param string $error the error message
     * @param int $status the error status (HTTP)
     * @return ApiResponse
     */
    public static function error(string $error, int $status = 400): self
    {
        return new self(['success' => false, 'error' => $error], $status);
    }

    public static function exception(\Throwable $e, int $status = 500): self
    {
        $response = [
            'success' => false,
            'error' => $e->getMessage(),
        ];

        if (!Environment::getContext()->isProduction()) {
            $response['exception'] = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return new self($response, $status);
    }

    /**
     * Build an API response for an error response, based on Extbase validation result (with errors)
     *
     * @param Result $resultWithError Extbase (validation) error result
     * @param int $status the error status (HTTP)
     * @return ApiResponse
     */
    public static function validationError(Result $resultWithError, int $status = 400): self
    {
        $errors = [];
        /** @var Error[] $flattenedErrors TODO: Does not support errors for properties yet */
        $flattenedErrors = $resultWithError->getFlattenedErrors()[''];
        foreach ($flattenedErrors as $error) {
            $newError = ['message' => $error->getMessage()];
            if ($error->getCode()) {
                $newError['code'] = $error->getCode();
            }
            if ($error->getTitle()) {
                $newError['title'] = $error->getTitle();
            }
            $errors[] = $newError;
        }
        return new self(['success' => false, 'errors' => $errors], $status);
    }

    /**
     * {@inheritdoc}
     */
    private function __construct(...$arguments)
    {
        parent::__construct(...$arguments);
    }
}
