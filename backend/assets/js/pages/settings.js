// Copyright (c) 2022. | David Annebicque | IUT de Troyes  - All Rights Reserved
// @file /Users/davidannebicque/Sites/intranetV3/assets/js/pages/settings.js
// @author davidannebicque
// @project intranetV3
// @lastUpdate 07/07/2022 13:30
import $ from 'jquery'
import { addCallout } from '../util'
import { post } from '../fetch'
import Routing from 'fos-router'

$(document).on('change', '.departementParDefaut', function () {
  $.ajax({
    url: Routing.generate('user_change_departement_defaut', { departement: $(this).val() }),
    method: 'POST',
    success() {
      addCallout('Mofification enregistrée !', 'success')
    },
    error() {
      addCallout('Erreur lors de la sauvegarde de la modification !', 'danger')
    },
  })
})

document.querySelectorAll('.changeConfigurationPersonne').forEach((elem) => {
  elem.addEventListener('change', (e) => {
    e.preventDefault()
    post(Routing.generate('settings_configuration_personnel'), { field: elem.name, value: elem.checked }).then(() => {
      addCallout('Mofification enregistrée !', 'success')
    }).catch(() => {
      addCallout('Erreur lors de la sauvegarde de la modification !', 'danger')
    })
  })
})

$(document).on('click', '#valideNewpassword', (e) => {
  e.preventDefault()
  const passwd1 = $('#password_1').val().trim()
  const passwd2 = $('#password_2').val().trim()
  const passwdactuel = $('#password_actuel').val().trim()

  if (passwd2 !== '' && passwd1 !== '' && passwdactuel !== '') {
    if (passwd1 !== passwd2) {
      addCallout('Les deux nouveaux mot de passe ne sont pas identique!', 'danger')
    } else {
      $.ajax({
        url: Routing.generate('user_change_password'),
        data: {
          passwd1,
          passwd2,
          passwdactuel,
        },
        method: 'POST',
        success() {
          addCallout('Mofification de votre mot de passe effectuée !', 'success')
        },
        error() {
          addCallout('Erreur lors de la modification du mot de passe !', 'danger')
        },
      })
    }
  } else {
    addCallout('Tous les champs sont obligatoires pour la modification du mot de passe!', 'danger')
  }
})
