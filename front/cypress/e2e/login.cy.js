describe('Login Test', () => {
  beforeEach(() => {
    cy.visit('http://localhost:3000')
  })

  it('Logs in as a guest', () => {
    cy.contains('Connexion invité').should('be.visible').click()

    // intercepte la requête POST vers /api/login et renvoie un fichier JSON
    cy.intercept('POST', 'https://localhost:8000/api/login', { fixture: 'login.json' }).as('loginRequest')

    // renseigne les champs du formulaire
    cy.get('#username').should('be.visible').type('user')
    cy.get('#password').should('be.visible').type('test')

    cy.get('form').submit()

    // Attend la fin de la requête
    cy.wait('@loginRequest').its('response.statusCode').should('eq', 200)

    // Vérifie que l'utilisateur est bien connecté
    cy.url().should('include', '/auth/portail')
    cy.getCookie('token').should('exist')
  })
})
