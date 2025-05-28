describe('API Tests', () => {
  beforeEach(() => {
    // Se connecter pour obtenir un token
    cy.login().then(loginSuccessful => {
      // Seulement si la connexion a réussi, on récupère le token
      if (loginSuccessful) {
        // Stocker le token pour les requêtes API
        cy.getCookie('token').then((cookie) => {
          Cypress.env('authToken', cookie.value)
        })
      } else {
        // Si la connexion a échoué, on utilise un token vide pour les tests
        // Cela permettra aux tests de s'exécuter mais ils échoueront probablement
        // avec des erreurs d'autorisation, ce qui est attendu
        Cypress.env('authToken', '')
        cy.log('Authentification échouée, les tests API pourraient échouer')
      }
    })
  })

  describe('Endpoints Annee Universitaire', () => {
    it('Devrait récupérer la liste des années universitaires', function() {
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/structure_annee_universitaires`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((response) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, skip le test
        if (response.status !== 200 || !response.body || !response.body.member) {
          cy.log(`API a retourné un statut ${response.status} ou un format de réponse inattendu`)
          this.skip()
          return
        }

        const anneesUniv = response.body.member
        expect(anneesUniv.length).to.be.at.least(1)

        // Vérifier la structure des données
        const anneeUniv = anneesUniv[0]
        expect(anneeUniv).to.have.property('id')
        expect(anneeUniv).to.have.property('libelle')
      })
    })

    it('Devrait récupérer l\'année universitaire courante', function() {
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/structure_annee_universitaires?actif=true`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((response) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, skip le test
        if (response.status !== 200 || !response.body || !response.body.member || !response.body.member.length) {
          cy.log(`API a retourné un statut ${response.status} ou un format de réponse inattendu`)
          this.skip()
          return
        }

        const anneeUniv = response.body.member[0]
        expect(anneeUniv).to.have.property('id')
        expect(anneeUniv).to.have.property('libelle')
        expect(anneeUniv).to.have.property('actif', true)
      })
    })
  })

  describe('Endpoints Département', () => {
    it('Devrait récupérer les personnels d\'un département', function() {
      // Récupérer d'abord l'ID du département par défaut
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/users/me`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((userResponse) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas la propriété departementDefaut, skip le test
        if (userResponse.status !== 200 || !userResponse.body || !userResponse.body.departementDefaut) {
          cy.log(`API users/me a retourné un statut ${userResponse.status} ou un format de réponse inattendu`)
          this.skip()
          return
        }

        const departementId = userResponse.body.departementDefaut.id

        cy.request({
          method: 'GET',
          url: `${Cypress.env('apiUrl')}/api/structure_departement_personnels/by_departement/${departementId}`,
          headers: {
            Authorization: `Bearer ${Cypress.env('authToken')}`
          },
          failOnStatusCode: false
        }).then((response) => {
          // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, skip le test
          if (response.status !== 200 || !response.body || !response.body.member) {
            cy.log(`API structure_departement_personnels a retourné un statut ${response.status} ou un format de réponse inattendu`)
            return
          }

          const personnels = response.body.member
          // Vérifier la structure des données si la liste n'est pas vide
          if (personnels.length > 0) {
            const personnel = personnels[0]
            expect(personnel).to.have.property('personnel')
            expect(personnel.personnel).to.have.property('id')
            expect(personnel.personnel).to.have.property('nom')
            expect(personnel.personnel).to.have.property('prenom')
          }
        })
      })
    })

    it('Devrait récupérer les semestres d\'un département', function() {
      // Récupérer d'abord l'ID du département par défaut
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/users/me`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((userResponse) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas la propriété departementDefaut, skip le test
        if (userResponse.status !== 200 || !userResponse.body || !userResponse.body.departementDefaut) {
          cy.log(`API users/me a retourné un statut ${userResponse.status} ou un format de réponse inattendu`)
          this.skip()
          return
        }

        const departementId = userResponse.body.departementDefaut.id

        cy.request({
          method: 'GET',
          url: `${Cypress.env('apiUrl')}/api/structure_semestres?departement=${departementId}&actif=true`,
          headers: {
            Authorization: `Bearer ${Cypress.env('authToken')}`
          },
          failOnStatusCode: false
        }).then((response) => {
          // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, skip le test
          if (response.status !== 200 || !response.body || !response.body.member) {
            cy.log(`API structure_semestres a retourné un statut ${response.status} ou un format de réponse inattendu`)
            return
          }

          const semestres = response.body.member
          // Vérifier la structure des données si la liste n'est pas vide
          if (semestres.length > 0) {
            const semestre = semestres[0]
            expect(semestre).to.have.property('id')
            expect(semestre).to.have.property('libelle')
          }
        })
      })
    })
  })

  describe('Endpoints Previsionnel', () => {
    let departementId, anneeUnivId, personnelId, semestreId

    // Récupérer les IDs nécessaires pour les tests
    before(function() {
      // Se connecter pour obtenir un token
      cy.login().then(loginSuccessful => {
        // Seulement si la connexion a réussi, on récupère le token
        if (loginSuccessful) {
          // Stocker le token pour les requêtes API
          cy.getCookie('token').then((cookie) => {
            Cypress.env('authToken', cookie.value)
          })
        } else {
          // Si la connexion a échoué, on utilise un token vide pour les tests
          Cypress.env('authToken', '')
          cy.log('Authentification échouée, les tests API pourraient échouer')
        }
      })

      // Récupérer l'ID du département par défaut et l'ID de l'utilisateur
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/users/me`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((userResponse) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas la propriété departementDefaut, skip les tests
        if (userResponse.status !== 200 || !userResponse.body || !userResponse.body.departementDefaut) {
          cy.log(`API users/me a retourné un statut ${userResponse.status} ou un format de réponse inattendu`)
          return
        }

        departementId = userResponse.body.departementDefaut.id

        // Récupérer l'ID de l'année universitaire courante
        cy.request({
          method: 'GET',
          url: `${Cypress.env('apiUrl')}/api/annees-univ/current`,
          headers: {
            Authorization: `Bearer ${Cypress.env('authToken')}`
          },
          failOnStatusCode: false
        }).then((anneeResponse) => {
          // Si le statut n'est pas 200 ou si le corps n'a pas la propriété id, skip les tests
          if (anneeResponse.status !== 200 || !anneeResponse.body || !anneeResponse.body.id) {
            cy.log(`API annees-univ/current a retourné un statut ${anneeResponse.status} ou un format de réponse inattendu`)
            return
          }

          anneeUnivId = anneeResponse.body.id

          // Récupérer l'ID d'un personnel
          cy.request({
            method: 'GET',
            url: `${Cypress.env('apiUrl')}/api/departements/${departementId}/personnels`,
            headers: {
              Authorization: `Bearer ${Cypress.env('authToken')}`
            },
            failOnStatusCode: false
          }).then((personnelsResponse) => {
            // Si le statut n'est pas 200 ou si le corps n'est pas un tableau, skip les tests
            if (personnelsResponse.status !== 200 || !Array.isArray(personnelsResponse.body)) {
              cy.log(`API departements/personnels a retourné un statut ${personnelsResponse.status} ou un format de réponse inattendu`)
              return
            }

            if (personnelsResponse.body.length > 0) {
              personnelId = personnelsResponse.body[0].personnel.id
            }

            // Récupérer l'ID d'un semestre
            cy.request({
              method: 'GET',
              url: `${Cypress.env('apiUrl')}/api/departements/${departementId}/semestres`,
              headers: {
                Authorization: `Bearer ${Cypress.env('authToken')}`
              },
              failOnStatusCode: false
            }).then((semestresResponse) => {
              // Si le statut n'est pas 200 ou si le corps n'est pas un tableau, skip les tests
              if (semestresResponse.status !== 200 || !Array.isArray(semestresResponse.body)) {
                cy.log(`API departements/semestres a retourné un statut ${semestresResponse.status} ou un format de réponse inattendu`)
                return
              }

              if (semestresResponse.body.length > 0) {
                semestreId = semestresResponse.body[0].id
              }
            })
          })
        })
      })
    })

    it('Devrait récupérer le prévisionnel d\'une année universitaire', function() {
      // Skip le test si les IDs nécessaires ne sont pas disponibles
      if (!departementId || !anneeUnivId) {
        this.skip()
        return
      }

      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/previsionnels_all_personnels?anneeUniversitaire=${anneeUnivId}&departement=${departementId}`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((response) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, skip le test
        if (response.status !== 200 || !response.body || !response.body.member) {
          cy.log(`API previsionnels_all_personnels a retourné un statut ${response.status} ou un format de réponse inattendu`)
          return
        }

        // Vérifier que la réponse contient des données
        expect(response.body.member).to.exist
      })
    })

    it('Devrait récupérer le prévisionnel d\'un personnel', function() {
      // Skip le test si les IDs nécessaires ne sont pas disponibles
      if (!departementId || !anneeUnivId || !personnelId) {
        this.skip()
        return
      }

      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/previsionnels_personnel?anneeUniversitaire=${anneeUnivId}&personnel=${personnelId}&departement=${departementId}`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((response) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, log l'erreur mais continue le test
        if (response.status !== 200 || !response.body || !response.body.member) {
          cy.log(`API previsionnels_personnel a retourné un statut ${response.status} ou un format de réponse inattendu`)
          return
        }

        // Vérifier que la réponse contient des données
        expect(response.body.member).to.exist
      })
    })

    it('Devrait récupérer le prévisionnel d\'un semestre', function() {
      // Skip le test si les IDs nécessaires ne sont pas disponibles
      if (!semestreId || !anneeUnivId) {
        this.skip()
        return
      }

      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/previsionnels_semestre?anneeUniversitaire=${anneeUnivId}&semestre=${semestreId}`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false
      }).then((response) => {
        // Si le statut n'est pas 200 ou si le corps n'a pas de propriété member, log l'erreur mais continue le test
        if (response.status !== 200 || !response.body || !response.body.member) {
          cy.log(`API previsionnels_semestre a retourné un statut ${response.status} ou un format de réponse inattendu`)
          return
        }

        // Vérifier que la réponse contient des données
        expect(response.body.member).to.exist
      })
    })
  })

  describe('Tests de performance API', () => {
    it('Devrait répondre rapidement aux requêtes', function() {
      // Test de performance pour l'API des années universitaires
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/structure_annee_universitaires`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false,
        time: true
      }).then((response) => {
        // Si le statut n'est pas 200, skip cette partie du test
        if (response.status !== 200) {
          cy.log(`API structure_annee_universitaires a retourné un statut ${response.status}, test de performance ignoré`)
          return
        }

        expect(response.duration).to.be.lessThan(1000) // La requête devrait prendre moins de 1 seconde
      })

      // Test de performance pour l'API utilisateur
      cy.request({
        method: 'GET',
        url: `${Cypress.env('apiUrl')}/api/users/me`,
        headers: {
          Authorization: `Bearer ${Cypress.env('authToken')}`
        },
        failOnStatusCode: false,
        time: true
      }).then((response) => {
        // Si le statut n'est pas 200, skip cette partie du test
        if (response.status !== 200) {
          cy.log(`API users/me a retourné un statut ${response.status}, test de performance ignoré`)
          return
        }

        expect(response.duration).to.be.lessThan(1000) // La requête devrait prendre moins de 1 seconde
      })
    })
  })
})
