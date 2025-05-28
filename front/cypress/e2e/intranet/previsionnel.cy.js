describe('Previsionnel Tests', () => {
  beforeEach(() => {
    // Se connecter avant chaque test en utilisant la commande personnalisée
    cy.login().then(loginSuccessful => {
      if (loginSuccessful) {
        // Si la connexion a réussi, naviguer vers l'intranet
        cy.get('.UniTranet', { timeout: 10000 }).should('be.visible').click()
        cy.url().should('include', '/intranet')
      } else {
        // Si la connexion a échoué, naviguer directement vers l'intranet
        cy.visit('http://localhost:3000/intranet')
        cy.log('Authentification échouée, navigation directe vers l\'intranet')
      }
    })

    // Intercepter les requêtes API communes
    cy.intercept('GET', `${Cypress.env('apiUrl')}/api/structure_annee_universitaires*`).as('getAnneesUniv')
    cy.intercept('GET', `${Cypress.env('apiUrl')}/api/structure_departement_personnels/by_departement/*`).as('getPersonnels')
    cy.intercept('GET', `${Cypress.env('apiUrl')}/api/structure_semestres*`).as('getSemestres')
    cy.intercept('GET', `${Cypress.env('apiUrl')}/api/previsionnels_all_personnels*`).as('getPrevisionnels')
    cy.intercept('GET', `${Cypress.env('apiUrl')}/api/previsionnels_personnel*`).as('getPrevisionnelsPersonnel')
    cy.intercept('GET', `${Cypress.env('apiUrl')}/api/previsionnels_semestre*`).as('getPrevisionnelsSemestre')
    cy.intercept('PUT', `${Cypress.env('apiUrl')}/api/previsionnels*`).as('updatePrevisionnels')
  })

  describe('Previsionnel Personnel View', () => {
    beforeEach(() => {
      // Naviguer vers la vue Previsionnel Personnel avec la commande personnalisée
      cy.navigateToPrevisionnelPersonnel()

      // Attendre le chargement des données initiales (sans bloquer si les requêtes n'arrivent pas)
      cy.waitForLoading()
    })

    it('Devrait charger la page Previsionnel Personnel correctement', () => {
      // Vérifier que les éléments principaux sont présents
      cy.contains('h1', /Prévisionnel par personnel/i).should('be.visible')

      // Vérifier la présence des sélecteurs (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="annee-univ-select"]').length > 0) {
          cy.get('[data-cy="annee-univ-select"]').should('exist')
        } else {
          cy.contains('label', /Année universitaire/i).should('exist')
        }

        if ($body.find('[data-cy="personnel-select"]').length > 0) {
          cy.get('[data-cy="personnel-select"]').should('exist')
        } else {
          cy.contains('label', /Personnel/i).should('exist')
        }
      })
    })

    it('Devrait afficher la liste des personnels', () => {
      // Utiliser la commande personnalisée pour sélectionner un personnel
      cy.selectPersonnel()
    })

    it('Devrait charger les données prévisionnelles d\'un personnel', () => {
      // Sélectionner un personnel avec la commande personnalisée
      cy.selectPersonnel(0)

      // Attendre le chargement des données (sans bloquer si la requête n'arrive pas)
      cy.waitForLoading()

      // Vérifier que le tableau de prévisionnel est affiché
      cy.get('table, .p-datatable, .v-data-table').should('be.visible')
    })

    it('Devrait permettre de modifier un prévisionnel', () => {
      // Sélectionner un personnel avec la commande personnalisée
      cy.selectPersonnel(0)

      // Attendre le chargement des données (sans bloquer si la requête n'arrive pas)
      cy.waitForLoading()

      // Cliquer sur le bouton d'édition (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="edit-button"]').length > 0) {
          cy.get('[data-cy="edit-button"]').first().click()
        } else {
          cy.get('button:contains("Modifier"), button.p-button-edit, .edit-button')
            .first()
            .click({ force: true })
        }
      })

      // Modifier une valeur (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="heures-input"]').length > 0) {
          cy.get('[data-cy="heures-input"]').clear().type('10')
        } else {
          cy.get('input[type="number"], .p-inputnumber input')
            .first()
            .clear()
            .type('10')
        }
      })

      // Sauvegarder (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="save-button"]').length > 0) {
          cy.get('[data-cy="save-button"]').click()
        } else {
          cy.get('button:contains("Enregistrer"), button:contains("Sauvegarder"), button.p-button-success')
            .click({ force: true })
        }
      })

      // Attendre la mise à jour
      cy.wait('@updatePrevisionnels', { timeout: 10000 }).its('response.statusCode').should('be.oneOf', [200, 201, 204])

      // Vérifier que la sauvegarde a réussi (avec tolérance pour différents messages)
      cy.get('body').contains(/Modifications? enregistrée?s?|Sauvegarde réussie|Mise à jour effectuée/i, { timeout: 5000 })
        .should('be.visible')
    })
  })

  describe('Previsionnel Semestre View', () => {
    beforeEach(() => {
      // Naviguer vers la vue Previsionnel Semestre avec la commande personnalisée
      cy.navigateToPrevisionnelSemestre()

      // Attendre le chargement des données initiales (sans bloquer si les requêtes n'arrivent pas)
      cy.waitForLoading()
    })

    it('Devrait charger la page Previsionnel Semestre correctement', () => {
      // Vérifier que les éléments principaux sont présents
      cy.contains('h1', /Prévisionnel par semestre/i).should('be.visible')

      // Vérifier la présence des sélecteurs (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="annee-univ-select"]').length > 0) {
          cy.get('[data-cy="annee-univ-select"]').should('exist')
        } else {
          cy.contains('label', /Année universitaire/i).should('exist')
        }

        if ($body.find('[data-cy="semestre-select"]').length > 0) {
          cy.get('[data-cy="semestre-select"]').should('exist')
        } else {
          cy.contains('label', /Semestre/i).should('exist')
        }
      })
    })

    it('Devrait afficher la liste des semestres', () => {
      // Utiliser la commande personnalisée pour sélectionner un semestre
      cy.selectSemestre()
    })

    it('Devrait charger les données prévisionnelles d\'un semestre', () => {
      // Sélectionner un semestre avec la commande personnalisée
      cy.selectSemestre(0)

      // Attendre le chargement des données (sans bloquer si la requête n'arrive pas)
      cy.waitForLoading()

      // Vérifier que le tableau de prévisionnel est affiché
      cy.get('table, .p-datatable, .v-data-table').should('be.visible')
    })

    it('Devrait permettre de filtrer les enseignements', () => {
      // Sélectionner un semestre avec la commande personnalisée
      cy.selectSemestre(0)

      // Attendre le chargement des données
      cy.wait('@getPrevisionnels', { timeout: 10000 })
      cy.waitForLoading()

      // Utiliser la commande personnalisée pour rechercher
      cy.searchInTable('math')

      // Vérifier que le filtre fonctionne (avec tolérance pour différentes structures de tableau)
      cy.get('table, .p-datatable, .v-data-table')
        .contains(/math/i, { timeout: 5000 })
        .should('be.visible')
    })

    it('Devrait permettre d\'assigner un enseignant à un cours', () => {
      // Sélectionner un semestre avec la commande personnalisée
      cy.selectSemestre(0)

      // Attendre le chargement des données (sans bloquer si la requête n'arrive pas)
      cy.waitForLoading()

      // Cliquer sur le bouton d'édition (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="edit-button"]').length > 0) {
          cy.get('[data-cy="edit-button"]').first().click()
        } else {
          cy.get('button:contains("Modifier"), button.p-button-edit, .edit-button')
            .first()
            .click({ force: true })
        }
      })

      // Sélectionner un enseignant (avec fallback)
      cy.selectPersonnel(0)

      // Sauvegarder (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="save-button"]').length > 0) {
          cy.get('[data-cy="save-button"]').click()
        } else {
          cy.get('button:contains("Enregistrer"), button:contains("Sauvegarder"), button.p-button-success')
            .click({ force: true })
        }
      })

      // Attendre la mise à jour
      cy.wait('@updatePrevisionnels', { timeout: 10000 }).its('response.statusCode').should('be.oneOf', [200, 201, 204])

      // Vérifier que la sauvegarde a réussi (avec tolérance pour différents messages)
      cy.get('body').contains(/Modifications? enregistrée?s?|Sauvegarde réussie|Mise à jour effectuée/i, { timeout: 5000 })
        .should('be.visible')
    })
  })

  describe('Navigation entre les vues', () => {
    it('Devrait naviguer entre les vues Personnel et Semestre', () => {
      // Naviguer vers la vue Personnel avec la commande personnalisée
      cy.navigateToPrevisionnelPersonnel()

      // Naviguer vers la vue Semestre (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="semestre-view-link"]').length > 0) {
          cy.get('[data-cy="semestre-view-link"]').click()
        } else {
          cy.get('a:contains("Semestre"), a:contains("Par semestre"), button:contains("Semestre")')
            .click({ force: true })
        }
      })

      cy.url().should('include', '/previsionnel/semestre')
      cy.contains('h1', /Prévisionnel par semestre/i).should('be.visible')

      // Retourner à la vue Personnel (avec fallback)
      cy.get('body').then($body => {
        if ($body.find('[data-cy="personnel-view-link"]').length > 0) {
          cy.get('[data-cy="personnel-view-link"]').click()
        } else {
          cy.get('a:contains("Personnel"), a:contains("Par personnel"), button:contains("Personnel")')
            .click({ force: true })
        }
      })

      cy.url().should('include', '/previsionnel/personnel')
      cy.contains('h1', /Prévisionnel par personnel/i).should('be.visible')
    })
  })

  // Test de performance
  it('Devrait charger rapidement les pages prévisionnelles', () => {
    // Mesurer le temps de chargement de la page Personnel
    const startPersonnel = Date.now()
    cy.navigateToPrevisionnelPersonnel()
    cy.waitForLoading().then(() => {
      const loadTimePersonnel = Date.now() - startPersonnel
      cy.log(`Temps de chargement de la page Personnel: ${loadTimePersonnel}ms`)
      expect(loadTimePersonnel).to.be.lessThan(10000) // La page devrait charger en moins de 10 secondes
    })

    // Mesurer le temps de chargement de la page Semestre
    const startSemestre = Date.now()
    cy.navigateToPrevisionnelSemestre()
    cy.waitForLoading().then(() => {
      const loadTimeSemestre = Date.now() - startSemestre
      cy.log(`Temps de chargement de la page Semestre: ${loadTimeSemestre}ms`)
      expect(loadTimeSemestre).to.be.lessThan(10000) // La page devrait charger en moins de 10 secondes
    })
  })
})
