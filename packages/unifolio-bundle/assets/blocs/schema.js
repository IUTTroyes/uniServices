// Schéma des blocs : source unique partagée avec le serveur (blocs.json, validé par BlocValidator).
import schema from '../../blocs.json';

export const TYPES = schema.types;
export const GROUPES = schema.groupes;
export const LIMITES = schema.limites;

export const libelleType = (type) => TYPES[type]?.libelle ?? type;
export const icone = (type) => TYPES[type]?.icone ?? 'pi pi-box';
export const estConteneur = (type) => TYPES[type]?.enfants !== undefined;

/** Types de blocs que l'on peut ajouter dans un parent donné (null = racine d'une page). */
export const typesAutorises = (parentType = null) => {
  const enfants = parentType === null ? '*' : TYPES[parentType]?.enfants;
  if (!enfants) return [];
  const candidats = enfants === '*' ? Object.keys(TYPES).filter((t) => !TYPES[t].seulement_dans) : enfants;
  return candidats.filter((t) => TYPES[t]);
};

/** Types autorisés regroupés pour le menu d'ajout, dans l'ordre du schéma. */
export const groupesAutorises = (parentType = null) => {
  const autorises = typesAutorises(parentType);

  return GROUPES
    .map((g) => ({ ...g, types: autorises.filter((t) => TYPES[t].groupe === g.cle).map((t) => ({ type: t, libelle: TYPES[t].libelle, icone: TYPES[t].icone })) }))
    .filter((g) => g.types.length > 0);
};

export const nouvelId = () => {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();
  return `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 12)}`;
};

const clone = (v) => (v === undefined ? v : JSON.parse(JSON.stringify(v)));

/** Valeurs par défaut des attributs d'un type. */
export const attrsParDefaut = (type) => Object.fromEntries(
  Object.entries(TYPES[type]?.attrs ?? {}).map(([nom, def]) => [nom, clone(def.defaut ?? null)]),
);

/** Un bloc neuf, avec ses enfants initiaux (ex. 2 colonnes). */
export const nouveauBloc = (type) => {
  const definition = TYPES[type];
  if (!definition) throw new Error(`Type de bloc inconnu : ${type}`);
  const bloc = { id: nouvelId(), type, attrs: attrsParDefaut(type) };
  if (definition.enfants !== undefined) {
    bloc.children = (definition.enfants_initiaux ?? []).map(nouveauBloc);
  }

  return bloc;
};

/** Copie d'un bloc avec de nouveaux identifiants (récursif). */
export const dupliquer = (bloc) => ({
  ...clone(bloc),
  id: nouvelId(),
  ...(bloc.children ? { children: bloc.children.map(dupliquer) } : {}),
});

/** Tous les identifiants d'un arbre de blocs. */
export const identifiants = (blocs, ids = []) => {
  for (const b of blocs) {
    ids.push(b.id);
    if (b.children) identifiants(b.children, ids);
  }

  return ids;
};

/** Anomalies de complétude (champ obligatoire vide), pour avertir avant publication. */
export const incomplets = (blocs, chemin = 'Page') => {
  const manques = [];
  blocs.forEach((bloc, i) => {
    const ou = `${chemin} › ${libelleType(bloc.type).toLowerCase()} ${i + 1}`;
    for (const [nom, def] of Object.entries(TYPES[bloc.type]?.attrs ?? {})) {
      const v = bloc.attrs?.[nom];
      if (def.requis && (v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0))) {
        manques.push(`${ou} : « ${def.libelle} » est à renseigner.`);
      }
    }
    if (bloc.children) manques.push(...incomplets(bloc.children, ou));
  });

  return manques;
};

export const copie = clone;
