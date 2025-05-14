export function formatAdresse(adresse) {

  if (adresse && adresse.adresse !== "" && adresse.ville !== "" && adresse.codePostal !== "") {
    return `${adresse.adresse} ${adresse.complement1} ${adresse.complement2}, ${adresse.codePostal} ${adresse.ville} ${adresse.pays}`;
  } else {
    return null;
  }
}
