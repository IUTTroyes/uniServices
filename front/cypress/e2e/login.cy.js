describe('Login Test', () => {
  it('Visits UniServices and logs in as a guest', () => {
    cy.visit('http://localhost:3000')

    cy.contains('Connexion invité').click()

    // Intercepter la requête API de login et répondre avec la fixture
    cy.intercept('POST', 'https://localhost:8000/api/login', { fixture: 'login.json' }).as('loginRequest')

    // Remplir les champs
    cy.get('#username').type('user')
    cy.get('#password').type('test')

    cy.get('form').submit()

    // Attendre que la requête de login soit interceptée
    cy.wait('@loginRequest')

    // Vérifier la réussite de la connexion et la redirection
    cy.url().should('include', '/auth/portail')
    cy.getCookie('token').should('exist')
  })
})
