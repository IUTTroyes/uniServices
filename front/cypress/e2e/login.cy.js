describe('Login Test', () => {
  it('Logs in as a guest', () => {
    cy.visit('/auth/login')

    cy.get('#username').type('guest')
    cy.get('#password').type('guestpassword')
    cy.get('form').submit()

    cy.intercept('POST', '/api/login').as('loginRequest')

    cy.wait('@loginRequest').then((interception) => {
      assert.isNotNull(interception.response.body, 'Login API call has data')
      expect(interception.response.statusCode).to.eq(200)
      expect(interception.response.body).to.have.property('data')
      expect(interception.response.body.data).to.have.property('token')

      const token = interception.response.body.data.token
      cy.setCookie('token', token)
    })

    cy.url().should('include', '/auth/portail')
  })
})
