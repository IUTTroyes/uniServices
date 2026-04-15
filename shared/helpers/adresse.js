export function formatAdresse(adresse) {
  if (adresse) {
    if (adresse.length !== 0 && adresse.adresse && adresse.ville && adresse.codePostal) {
      return `${adresse.adresse} ${adresse.complement1} ${adresse.complement2}, ${adresse.codePostal} ${adresse.ville} ${adresse.pays}`;
    } if (adresse.length === 0) {
      return null;
    }
    else {
      return "Adresse incomplète";
    }
  }
  return null;
}
