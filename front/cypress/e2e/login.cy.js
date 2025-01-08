describe('Login Test', () => {
  it('Visits UniServices and logs in as a guest', () => {
    cy.visit('http://localhost:3000')

    cy.contains('Connexion invité').click()

    // Remplir les champs
    cy.get('#username').type('hero0010')
    cy.get('#password').type('test')

    cy.get('form').submit()

    // Vérifier le message d'erreur
    cy.get('.error-message').should('contain', 'Une erreur est survenue, veuillez contacter l\'administrateur du site')

    // Vérifier la réussite de la connexion et la redirection
    cy.url().should('include', '/auth/portail')
    cy.getCookie('token').should('exist')
  })
})
