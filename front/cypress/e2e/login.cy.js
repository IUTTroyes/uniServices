describe('Login Test', () => {
  beforeEach(() => {
    cy.visit('http://localhost:3000')
  })

  it('Logs in as a guest', () => {
    // Vérifier si on est sur Cypress
    // if (window.Cypress) {
    //
    //   // Intercepter la requête de login et renvoyer un fixture
    //   cy.intercept('POST', 'https://localhost:8000/api/login', { fixture: 'login.json' }).as('loginRequest')
    // }

    cy.contains('Connexion invité').should('be.visible').click()

    // Remplir les champs de connexion
    cy.get('#username').should('be.visible').type('hero0010')
    cy.get('#password').should('be.visible').type('test')

    cy.get('form').submit()

    // Attendre la fin de la requête de login
    // if (window.Cypress) {
    //   cy.wait('@loginRequest').its('response.statusCode').should('eq', 200)
    // }

    // Vérifier si on est redirigé vers la page de portail
    cy.url().should('include', '/auth/portail')
    cy.getCookie('token').should('exist')
  })
})
