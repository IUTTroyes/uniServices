export function construireHeures(ligne) {
  const heures = {};
  const groupes = {};

  for (const type of ['CM', 'TD', 'TP', 'Projet']) {
    const saisie = ligne.heures?.[type];
    const parGroupe = Number(String(saisie?.NbHrGrp ?? 0).replace(',', '.'));
    const nombre = Number(saisie?.NbGrp ?? ligne.groupes?.[type] ?? 0);
    if (!Number.isFinite(parGroupe) || parGroupe < 0 || !Number.isSafeInteger(nombre) || nombre < 0) {
      throw new RangeError(`${type} : indiquez un nombre d’heures positif et un nombre entier de groupes.`);
    }
    const total = type === 'Projet' ? parGroupe : parGroupe * Math.max(1, nombre);
    heures[type] = Math.round(total * 1e8) / 1e8;
    groupes[type] = nombre;
  }

  return { heures, groupes };
}
