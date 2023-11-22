describe('template spec', () => {
  before(() => {
    cy.importData('\\AndreasWolf\\TestExample\\Tests\\FrontendIntegration\\HelloWorldTest');
  });

  it('passes', () => {
    cy.visit('/');

    cy.contains('Hello World')
        .should('be.visible');
  });
})