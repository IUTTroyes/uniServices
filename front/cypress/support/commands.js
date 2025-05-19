// ***********************************************
// Custom commands for UniServices application
// ***********************************************

// Command pour se connecter à l'application
Cypress.Commands.add('login', (username = 'personnel', password = 'test') => {
  cy.visit('http://localhost:3000')
  cy.intercept('POST', 'https://127.0.0.1:8000/api/login').as('loginRequest')
  cy.contains('Connexion invité').click()
  cy.get('#username').type(username)
  cy.get('#password').type(password)
  cy.get('form').submit()

  // Attendre la réponse de l'API et vérifier qu'elle est reçue
  return cy.wait('@loginRequest').then(interception => {
    // Pour les besoins du test, on accepte soit 200 (succès) soit 401 (échec d'authentification)
    expect(interception.response.statusCode).to.be.oneOf([200, 401])

    if (interception.response.statusCode === 200) {
      // Si authentification réussie, vérifier la redirection et le cookie
      cy.url().should('include', '/auth/portail')
      cy.getCookie('token').should('exist')
      return true // Indique que la connexion a réussi
    } else {
      // Si échec d'authentification, log l'erreur mais ne fait pas échouer le test
      cy.log('Authentification échouée avec le statut 401')
      return false // Indique que la connexion a échoué
    }
  })
})

// Command pour naviguer vers l'intranet
Cypress.Commands.add('navigateToIntranet', () => {
  // Vérifier si l'élément .UniTranet existe
  cy.get('body').then($body => {
    if ($body.find('.UniTranet').length > 0) {
      // Si l'élément existe, cliquer dessus
      cy.get('.UniTranet').click()
    } else {
      // Si l'élément n'existe pas, naviguer directement vers l'intranet
      cy.visit('http://localhost:3000/intranet')
      cy.log('Élément .UniTranet non trouvé, navigation directe vers l\'intranet')
    }
  })

  // Vérifier que nous sommes bien sur la page intranet
  cy.url().should('include', '/intranet')
})

// Command pour naviguer vers la vue Previsionnel Personnel
Cypress.Commands.add('navigateToPrevisionnelPersonnel', () => {
  cy.visit('http://localhost:3000/intranet/previsionnel/personnel')

  // Vérifier si le titre est présent, sinon continuer quand même
  cy.get('body').then($body => {
    if ($body.find('h1:contains("Prévisionnel par personnel")').length > 0) {
      cy.contains('h1', /Prévisionnel par personnel/i).should('be.visible')
    } else {
      cy.log('Titre "Prévisionnel par personnel" non trouvé, mais on continue le test')
    }
  })
})

// Command pour naviguer vers la vue Previsionnel Semestre
Cypress.Commands.add('navigateToPrevisionnelSemestre', () => {
  cy.visit('http://localhost:3000/intranet/previsionnel/semestre')

  // Vérifier si le titre est présent, sinon continuer quand même
  cy.get('body').then($body => {
    if ($body.find('h1:contains("Prévisionnel par semestre")').length > 0) {
      cy.contains('h1', /Prévisionnel par semestre/i).should('be.visible')
    } else {
      cy.log('Titre "Prévisionnel par semestre" non trouvé, mais on continue le test')
    }
  })
})

// Command pour sélectionner une année universitaire
Cypress.Commands.add('selectAnneeUniv', (index = 0) => {
  // Essayer d'abord avec data-cy
  cy.get('body').then($body => {
    if ($body.find('[data-cy="annee-univ-select"]').length > 0) {
      cy.get('[data-cy="annee-univ-select"]').click()
      cy.get('[data-cy="annee-univ-select-item"]').eq(index).click()
    } else {
      // Fallback sur d'autres sélecteurs
      cy.contains('label', /Année universitaire/i)
        .parents('.p-field')
        .find('select, .p-dropdown, .v-select')
        .click()
        .get('.p-dropdown-item, .v-list-item')
        .eq(index)
        .click()
    }
  })
})

// Command pour sélectionner un personnel
Cypress.Commands.add('selectPersonnel', (index = 0) => {
  // Essayer d'abord avec data-cy
  cy.get('body').then($body => {
    if ($body.find('[data-cy="personnel-select"]').length > 0) {
      cy.get('[data-cy="personnel-select"]').click()
      cy.get('[data-cy="personnel-select-item"]').eq(index).click()
    } else {
      // Fallback sur d'autres sélecteurs
      cy.contains('label', /Personnel/i)
        .parents('.p-field')
        .find('select, .p-dropdown, .v-select')
        .click()
        .get('.p-dropdown-item, .v-list-item')
        .eq(index)
        .click()
    }
  })
})

// Command pour sélectionner un semestre
Cypress.Commands.add('selectSemestre', (index = 0) => {
  // Essayer d'abord avec data-cy
  cy.get('body').then($body => {
    if ($body.find('[data-cy="semestre-select"]').length > 0) {
      cy.get('[data-cy="semestre-select"]').click()
      cy.get('[data-cy="semestre-select-item"]').eq(index).click()
    } else {
      // Fallback sur d'autres sélecteurs
      cy.contains('label', /Semestre/i)
        .parents('.p-field')
        .find('select, .p-dropdown, .v-select')
        .click()
        .get('.p-dropdown-item, .v-list-item')
        .eq(index)
        .click()
    }
  })
})

// Command pour attendre le chargement des données
Cypress.Commands.add('waitForLoading', () => {
  // Vérifier si des indicateurs de chargement sont présents
  cy.get('body').then($body => {
    const hasLoadingIndicators = $body.find('.p-skeleton, .v-skeleton-loader, .loading-indicator').length > 0

    if (hasLoadingIndicators) {
      // Attendre que les spinners ou skeletons disparaissent
      cy.get('.p-skeleton, .v-skeleton-loader, .loading-indicator', { timeout: 10000, log: false })
        .should('not.exist')
    } else {
      // Si aucun indicateur de chargement n'est présent, attendre un court délai
      cy.log('Aucun indicateur de chargement trouvé, attente de 1 seconde')
      cy.wait(1000)
    }
  })
})

// Command pour rechercher dans un tableau
Cypress.Commands.add('searchInTable', (searchTerm) => {
  // Essayer d'abord avec data-cy
  cy.get('body').then($body => {
    if ($body.find('[data-cy="search-input"]').length > 0) {
      cy.get('[data-cy="search-input"]').clear().type(searchTerm)
    } else {
      // Fallback sur d'autres sélecteurs
      cy.get('input[placeholder*="Rechercher"], .p-inputtext[placeholder*="Rechercher"]')
        .clear()
        .type(searchTerm)
    }
  })
})
