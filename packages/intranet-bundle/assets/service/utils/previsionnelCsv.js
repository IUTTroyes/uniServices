const types = ['CM', 'TD', 'TP', 'Projet'];
export const colonnesPrevisionnel = ['personnel', 'enseignement', ...types.flatMap((type) => [`heures_${type}`, `groupes_${type}`])];

export async function lireCsvPrevisionnel(fichier) {
  if (!fichier || fichier.size > 2_000_000) throw new Error('Sélectionnez un fichier CSV de 2 Mo maximum.');
  const { read, utils } = await import('xlsx');
  const classeur = read(await fichier.text(), { type: 'string', raw: true, FS: ';' });
  const feuille = classeur.Sheets[classeur.SheetNames[0]];
  const [entete, ...lignes] = utils.sheet_to_json(feuille, { header: 1, defval: '', blankrows: false, raw: true });
  const index = (entete ?? []).map((colonne) => String(colonne).trim());
  if (colonnesPrevisionnel.some((colonne) => index.filter((nom) => nom === colonne).length !== 1)) {
    throw new Error('Les colonnes du CSV doivent correspondre au modèle.');
  }
  if (!lignes.length || lignes.length > 2000) throw new Error('Le CSV doit contenir entre 1 et 2 000 lignes.');

  return lignes.map((ligne, position) => {
    const champ = (nom) => String(ligne[index.indexOf(nom)] ?? '').trim();
    const personnel = Number(champ('personnel'));
    const enseignement = champ('enseignement');
    if (!Number.isSafeInteger(personnel) || personnel <= 0 || !enseignement) {
      throw new Error(`Ligne ${position + 2} : personnel ou code d’enseignement invalide.`);
    }

    const heures = {};
    const groupes = {};
    for (const type of types) {
      heures[type] = Number(champ(`heures_${type}`).replace(',', '.'));
      groupes[type] = Number(champ(`groupes_${type}`));
      if (!Number.isFinite(heures[type]) || heures[type] < 0 || !Number.isSafeInteger(groupes[type]) || groupes[type] < 0) {
        throw new Error(`Ligne ${position + 2} : heures ou groupes invalides pour ${type}.`);
      }
    }
    return { personnel, enseignement, heures, groupes };
  });
}
