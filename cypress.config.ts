import { defineConfig } from 'cypress';

export default defineConfig({
    // baseUrl is set in the beforeEach() callback in support/commands.ts
    siteUrl: process.env.DDEV_PRIMARY_URL ?? 'http://frontend-test-legacy.ddev.site',
    video: true,
    e2e: {
        setupNodeEvents (on, config) {
            let storedUrl = null;

            on('task', {
                storeUrl (url) {
                    storedUrl = url;
                    return null;
                },
                getStoredUrl () {
                    return storedUrl;
                },
            });
        },
    },
});
