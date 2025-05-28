/**
 * Script pour exécuter les tests Cypress
 *
 * Ce script permet de lancer les tests Cypress avec différentes options
 * et de générer un rapport de test.
 */

const cypress = require('cypress')
const { merge } = require('mochawesome-merge')
const generator = require('mochawesome-report-generator')
const rm = require('rimraf')

// Options par défaut
const options = {
  reporter: 'mochawesome',
  browser: 'chrome',
  headless: true,
  spec: './cypress/e2e/**/*.cy.js',
  reporterOptions: {
    reportDir: 'cypress/reports',
    overwrite: false,
    html: false,
    json: true
  }
}

// Nettoyer les rapports précédents
rm.sync('cypress/reports')

// Exécuter les tests
cypress.run({
  ...options,
  config: {
    video: true,
    screenshotOnRunFailure: true
  }
})
.then(async (results) => {
  // Générer un rapport combiné
  const jsonReport = await merge({ files: ['cypress/reports/*.json'] })
  await generator.create(jsonReport, { reportDir: 'cypress/reports/html' })

  console.log('Tests terminés avec succès !')
  console.log(`Total de tests: ${results.totalTests}`)
  console.log(`Tests réussis: ${results.totalPassed}`)
  console.log(`Tests échoués: ${results.totalFailed}`)
  console.log(`Rapport disponible dans: cypress/reports/html`)

  process.exit(results.totalFailed === 0 ? 0 : 1)
})
.catch((err) => {
  console.error('Erreur lors de l\'exécution des tests:', err)
  process.exit(1)
})
