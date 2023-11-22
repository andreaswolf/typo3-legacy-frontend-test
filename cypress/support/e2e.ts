// ***********************************************************
// This example support/index.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands';

// Alternatively you can use CommonJS syntax:
// require('./commands')

export class UserCredentials {
    constructor (public readonly username: string, public readonly password: string) {}

    public toString () {
        return `${this.username}/${this.password}`;
    }
}

declare global {
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        interface Chainable {
            importData(dataSet: string): Chainable<string>

            login(email: string): Chainable<any>
            loginWithCredentials(credentials: UserCredentials): Chainable<any>
        }
    }
}
