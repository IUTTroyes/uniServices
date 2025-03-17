describe('UniServices Navigation Tests', () => {

  before(() => {
    // Connexion avant les tests
    cy.visit('http://localhost:3000')
    cy.contains('Connexion invité').click()

    // Remplir les champs
    cy.get('#username').type('cyndel')
    cy.get('#password').type('test')

    cy.get('form').submit()

    // Vérifier que le login a bien fonctionné
    cy.url().should('include', '/auth/portail')
    cy.getCookie('token').should('exist')
  })

  it('Accéder à l\'intranet', () => {
    cy.get('.UniTranet').click()

    cy.url().should('include', '/intranet')
    cy.getCookie('token').should('exist')
  })

})
