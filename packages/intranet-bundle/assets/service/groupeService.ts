import {
  getEtudiantScolariteSemestresService,
  updateEtudiantScolariteSemestreService,
} from '@requests';

// ---------------------------------------------------------------------------
// Utilitaires internes
// ---------------------------------------------------------------------------

/** Normalise un ID qui peut être un nombre ou une IRI (/api/xxx/123 → 123). */
const normalizeId = (id: any): number | string => {
  if (typeof id === 'string' && id.startsWith('/')) {
    const last = id.split('/').pop();
    const parsed = parseInt(last ?? '', 10);
    if (!isNaN(parsed)) return parsed;
  }
  return id;
};

/** Mélange un tableau en place (Fisher-Yates) et retourne une copie mélangée. */
const shuffle = <T>(arr: T[]): T[] => {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
};

// ---------------------------------------------------------------------------
// Logique de répartition équitable
// ---------------------------------------------------------------------------

/**
 * Calcule la répartition équitable des étudiants dans les groupes.
 *
 * Algorithme :
 *   1. Mélange aléatoire des indices d'étudiants (Fisher-Yates).
 *   2. Pour chaque type de groupe, distribue par modulo sur les groupes disponibles.
 *
 * @returns Map<indexÉtudiant, { typeGroupe: groupeId }>
 */
const repartirEquitablement = (
  etudiants: any[],
  groupesParType: Record<string, any[]>
): Map<number, Record<string, number | string | null>> => {
  const result = new Map<number, Record<string, number | string | null>>();
  const shuffled = shuffle(etudiants.map((_, i) => i));

  for (const type of Object.keys(groupesParType)) {
    const groupes = groupesParType[type];
    if (!groupes?.length) continue;

    shuffled.forEach((etudiantIndex, position) => {
      const groupe = groupes[position % groupes.length];
      if (!result.has(etudiantIndex)) result.set(etudiantIndex, {});
      result.get(etudiantIndex)![type] = groupe.id;
    });
  }

  return result;
};

/**
 * Construit la liste finale des groupes d'un étudiant après répartition :
 * - conserve les groupes de types non couverts par la répartition
 * - remplace/ajoute les groupes des types couverts
 */
const buildNewGroupes = (
  currentGroupes: any[],
  typeMap: Record<string, number | string | null>,
  allGroupes: any[]
): any[] => {
  // Garder les groupes hors périmètre de répartition
  const conserved = currentGroupes.filter((g) => {
    const obj = allGroupes.find((ag) => normalizeId(ag.id) === normalizeId(g.id));
    return obj && !Object.keys(typeMap).includes(obj.type);
  });

  // Ajouter les groupes nouvellement affectés
  const added: any[] = [];
  for (const [, groupeId] of Object.entries(typeMap)) {
    if (groupeId !== null) {
      const groupeObj = allGroupes.find((g) => normalizeId(g.id) === normalizeId(groupeId));
      if (groupeObj) added.push(groupeObj);
    }
  }

  return [...conserved, ...added];
};

/** Convertit une liste de groupes en IRIs pour l'API. */
const toIris = (groupes: any[]): string[] =>
  groupes.map((g) => {
    const gid = normalizeId(g.id);
    return typeof gid === 'number' ? `/api/structure_groupes/${gid}` : (g.id as string);
  });

// ---------------------------------------------------------------------------
// Fonction principale exportée
// ---------------------------------------------------------------------------

export interface RepartitionResult {
  updatedCount: number;
  errorCount: number;
}

/**
 * Répartit équitablement et aléatoirement tous les étudiants d'un semestre
 * dans les groupes disponibles, puis persiste les affectations via l'API.
 *
 * @param semestreId            - ID du semestre cible
 * @param anneeUniversitaireId  - ID de l'année universitaire
 * @param groupesParType        - groupes indexés par type { 'TD': [...], 'TP': [...] }
 * @returns                     - { updatedCount, errorCount }
 */
const repartitionAutoGroupe = async (
  semestreId: number,
  anneeUniversitaireId: number,
  groupesParType: Record<string, any[]>
): Promise<RepartitionResult> => {
  // 1. Charger tous les étudiants du semestre (sans pagination)
  const response = await getEtudiantScolariteSemestresService(
    { anneeUniversitaire: anneeUniversitaireId, semestre: semestreId },
    '/manage-groupes'
  );
  const tousLesEtudiants: any[] = response.member ?? response;

  if (!tousLesEtudiants.length) {
    return { updatedCount: 0, errorCount: 0 };
  }

  // 2. Calculer la répartition équitable
  const affectations = repartirEquitablement(tousLesEtudiants, groupesParType);
  const allGroupes = Object.values(groupesParType).flat();

  let updatedCount = 0;
  let errorCount = 0;

  // 3. Appliquer les affectations
  for (const [etudiantIndex, typeMap] of affectations.entries()) {
    const sco = tousLesEtudiants[etudiantIndex];
    if (!sco) continue;

    try {
      const currentGroupes = Array.isArray(sco.groupes) ? [...sco.groupes] : [];
      const newGroupes = buildNewGroupes(currentGroupes, typeMap, allGroupes);

      await updateEtudiantScolariteSemestreService(
        sco.id,
        { groupes: toIris(newGroupes) },
        false
      );
      updatedCount++;
    } catch (err) {
      console.error(`Erreur répartition étudiant ${sco.id}:`, err);
      errorCount++;
    }
  }

  return { updatedCount, errorCount };
};

// ---------------------------------------------------------------------------
// Synchronisation des groupes parents
// ---------------------------------------------------------------------------

export interface SynchroParentsResult {
  updatedCount: number;
  errorCount: number;
}

/**
 * Pour chaque étudiant de la liste, remonte la hiérarchie de son groupe de plus
 * bas niveau et affecte automatiquement les groupes parents manquants ou incorrects.
 *
 * @param etudiants      - liste des EtudiantScolariteSemestre (page courante)
 * @param groupesParType - groupes indexés par type { 'TD': [...], 'TP': [...] }
 * @returns              - { updatedCount, errorCount }
 */
const synchroParentsGroupe = async (
  etudiants: any[],
  groupesParType: Record<string, any[]>
): Promise<SynchroParentsResult> => {
  const allGroupes = Object.values(groupesParType).flat();

  // Map d'accès rapide par ID (numérique et IRI)
  const groupeMap = new Map<number | string, any>();
  allGroupes.forEach((g) => {
    groupeMap.set(g.id, g);
    if (typeof g.id === 'string') {
      const numId = parseInt(g.id.split('/').pop() ?? '', 10);
      if (!isNaN(numId)) groupeMap.set(numId, g);
    }
  });

  // Remonte la chaîne de parents d'un groupe
  const getParentChain = (groupe: any): any[] => {
    const parents: any[] = [];
    let current = groupe;
    while (current?.parent) {
      let parentId = current.parent.id ?? current.parent;
      if (typeof parentId === 'string' && parentId.startsWith('/')) {
        const parts = parentId.split('/');
        parentId = parseInt(parts[parts.length - 1], 10);
      }
      const parentGroupe = groupeMap.get(parentId);
      if (parentGroupe) {
        parents.push(parentGroupe);
        current = parentGroupe;
      } else {
        break;
      }
    }
    return parents;
  };

  // Plus la priorité est basse, plus le type est bas dans la hiérarchie
  const typePriority: Record<string, number> = { TP: 1, LV: 2, PROJET: 2, AUTRE: 2, TD: 3, CM: 4 };

  let updatedCount = 0;
  let errorCount = 0;

  for (const sco of etudiants) {
    try {
      const currentGroupes: any[] = Array.isArray(sco.groupes) ? [...sco.groupes] : [];

      // Trouver le groupe de plus bas niveau
      let lowestGroupe: any = null;
      let lowestPriority = Infinity;
      for (const g of currentGroupes) {
        const obj = groupeMap.get(normalizeId(g.id));
        if (obj) {
          const priority = typePriority[obj.type] ?? 99;
          if (priority < lowestPriority) {
            lowestPriority = priority;
            lowestGroupe = obj;
          }
        }
      }

      if (!lowestGroupe) continue; // pas de groupe assigné

      const parentChain = getParentChain(lowestGroupe);
      if (parentChain.length === 0) continue; // pas de parents

      // Construire la nouvelle liste
      const currentTypes = new Set(
        currentGroupes.map((g) => groupeMap.get(normalizeId(g.id))?.type)
      );
      const newGroupes = [...currentGroupes];
      let hasChanges = false;

      for (const parent of parentChain) {
        if (!currentTypes.has(parent.type)) {
          newGroupes.push(parent);
          currentTypes.add(parent.type);
          hasChanges = true;
        } else {
          const existingIndex = newGroupes.findIndex(
            (g) => groupeMap.get(normalizeId(g.id))?.type === parent.type
          );
          if (existingIndex !== -1 && normalizeId(newGroupes[existingIndex].id) !== parent.id) {
            newGroupes[existingIndex] = parent;
            hasChanges = true;
          }
        }
      }

      if (!hasChanges) continue;

      await updateEtudiantScolariteSemestreService(
        sco.id,
        { groupes: toIris(newGroupes) },
        false
      );

      sco.groupes = newGroupes; // mise à jour locale
      updatedCount++;
    } catch (err) {
      console.error(`Erreur synchro parents étudiant ${sco.id}:`, err);
      errorCount++;
    }
  }

  return { updatedCount, errorCount };
};

export { repartitionAutoGroupe, synchroParentsGroupe };

