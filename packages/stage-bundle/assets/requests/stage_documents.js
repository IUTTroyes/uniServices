import api from '@helpers/axios';

const defaultHeaders = {
  'Accept': 'application/ld+json, application/json'
};

/**
 * Récupère les documents d'offres de stage issus du document-bundle (GED)
 */
export const fetchStageOfferDocuments = async () => {
  try {
    const [categoriesResp, docsResp] = await Promise.all([
      api.get('/api/document_categories', { headers: defaultHeaders }),
      api.get('/api/documents', { headers: defaultHeaders })
    ]);

    const rawCategories = Array.isArray(categoriesResp.data)
      ? categoriesResp.data
      : (categoriesResp.data['hydra:member'] || categoriesResp.data['member'] || categoriesResp.data['data'] || []);

    const rawDocs = Array.isArray(docsResp.data)
      ? docsResp.data
      : (docsResp.data['hydra:member'] || docsResp.data['member'] || docsResp.data['data'] || []);

    // Identifier les IDs de catégories rattachées au package 'stage' ou contenant 'Offre' / 'Stage'
    const stageCategoryIds = new Set();
    rawCategories.forEach((cat) => {
      const catId = cat.id ? cat.id.toString() : cat['@id']?.split('/').pop();
      const libelle = (cat.libelle || cat.name || '').toLowerCase();
      const packageKey = cat.packageKey || '';

      if (packageKey === 'stage' || libelle.includes('offre') || libelle.includes('stage')) {
        if (catId) stageCategoryIds.add(catId);
      }
    });

    // Filtrer et mapper les documents
    const stageOfferDocs = rawDocs
      .filter((doc) => {
        let catId = '';
        if (doc.category) {
          if (typeof doc.category === 'object' && doc.category.id) {
            catId = doc.category.id.toString();
          } else if (typeof doc.category === 'string') {
            catId = doc.category.split('/').pop();
          }
        }
        const tags = Array.isArray(doc.tags) ? doc.tags.map(t => t.toLowerCase()) : [];
        const title = (doc.titre || doc.title || '').toLowerCase();

        return stageCategoryIds.has(catId) || tags.includes('stage') || tags.includes('offre') || title.includes('offre');
      })
      .map((doc) => {
        const id = doc.id ? doc.id.toString() : doc['@id']?.split('/').pop() || Date.now().toString();
        const rawSize = doc.fileSize || doc.size || 0;
        const sizeFormatted = rawSize > 1048576 
          ? `${(rawSize / 1048576).toFixed(1)} Mo`
          : `${Math.round(rawSize / 1024)} Ko`;

        return {
          id,
          title: doc.titre || doc.title || 'Fiche d\'Offre de Stage',
          company: doc.author || 'Entreprise Partenaire',
          location: 'Département MMI / IUT',
          description: doc.description || 'Fiche d\'offre de stage déposée et validée par l\'administration.',
          type: doc.type || 'pdf',
          size: sizeFormatted,
          rawSize,
          author: doc.author || 'Responsable Stage',
          updatedAt: new Date(doc.updatedAt || doc.createdAt || Date.now()).toLocaleDateString('fr-FR'),
          gratification: 'Selon réglementation',
          tags: Array.isArray(doc.tags) ? doc.tags : ['Stage', 'Offre'],
          downloadUrl: `/api/documents/${id}`
        };
      });

    return stageOfferDocs;
  } catch (error) {
    console.error('Erreur lors de la récupération des offres de stage depuis la GED:', error);
    return [];
  }
};
