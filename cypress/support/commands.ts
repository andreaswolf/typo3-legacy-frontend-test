// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

import { UserCredentials } from './e2e';

// eslint-disable-next-line dot-notation
const getSiteUrl = () => (Cypress.env('SITE_URL') ?? Cypress.config()['siteUrl'] ?? 'Error: Site URL not set!')
    .replace(/\/$/, '');

Cypress.Commands.add('importData', (dataSet) => {
    const siteUrl = getSiteUrl();

    return cy.request({
        url: `${siteUrl}/api/v1/frontend-tests/setup`,
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: {
            testCase: dataSet,
        },
    })
        .its('body')
        .then((body): string => {
            // this must be reset again with a global beforeAll() callback, otherwise subsequent test setups may fail
            const baseUrl = `${siteUrl}/_ft-${body.message}`;
            Cypress.config('baseUrl', baseUrl);
            return body.message;
        });
});

Cypress.Commands.add('login', (email: string) => {
    return cy.request({
        url: '/api/v1/frontend-tests/login',
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: {
            username: email,
        },
    });
});
Cypress.Commands.add('loginWithCredentials', (credentials: UserCredentials) => {
    return cy.request({
        url: '/api/v1/frontend-tests/login',
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: {
            username: credentials.username,
            password: credentials.password,
        },
    });
});
