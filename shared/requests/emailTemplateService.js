import api from '@helpers/axios';

/**
 * Éditeur des e-mails de l'application (super administrateur) : modèles de tous les packages, personnalisables au
 * niveau de l'établissement (departementId null) ou d'un département. Voir docs/EMAIL_SYSTEM.md.
 */
const avecDepartement = (departementId) => (departementId ? { params: { departement: departementId } } : {});

/** Définitions groupées par application : [{ bundle, items: [{ key, label, personnalisation, … }] }]. */
const getEmailDefinitionsService = async (departementId = null) => (await api.get('/api/email/definitions', avecDepartement(departementId))).data;

/** Modèle d'un e-mail au niveau demandé, avec son modèle par défaut (defaultSubject, defaultBodyHtml). */
const getEmailTemplateService = async (key, departementId = null) =>
    (await api.get(`/api/email/templates/${encodeURIComponent(key)}`, avecDepartement(departementId))).data;

/** Enregistre une personnalisation (vérifiée par le serveur : 422 avec un message si le modèle est invalide). */
const saveEmailTemplateService = async ({ emailKey, departementId = null, subject, bodyHtml }) =>
    (await api.post('/api/email/templates', { emailKey, departement: departementId, subject, bodyHtml })).data;

/** Supprime une personnalisation : le niveau supérieur reprend la main. */
const deleteEmailTemplateService = async (id) => { await api.delete(`/api/email/templates/${id}`); };

/** Aperçu avec des valeurs d'exemple : { subject, html }. */
const previewEmailTemplateService = async ({ emailKey, subject, bodyHtml }) => (await api.post('/api/email/apercu', { emailKey, subject, bodyHtml })).data;

/** Envoie l'aperçu à l'administrateur connecté : { envoyeA }. */
const sendTestEmailTemplateService = async ({ emailKey, subject, bodyHtml }) => (await api.post('/api/email/test', { emailKey, subject, bodyHtml })).data;

const getEmailDepartementsService = async () => (await api.get('/api/email/departements')).data;

// ---------- Habillage de l'établissement (en-tête, signature, mentions imposées dans tous les e-mails) ----------

/** { reglages: { couleur, expediteur_nom, reply_to, signature, mentions_legales, contact_rgpd }, etablissement, universite, logo } */
const getEmailHabillageService = async () => (await api.get('/api/email/habillage')).data;

const saveEmailHabillageService = async (reglages) => (await api.put('/api/email/habillage', reglages)).data;

/** Aperçu d'une notification avec des réglages non enregistrés : { html }. */
const previewEmailHabillageService = async (reglages) => (await api.post('/api/email/habillage/apercu', { reglages })).data;

export {
    getEmailDefinitionsService,
    getEmailTemplateService,
    saveEmailTemplateService,
    deleteEmailTemplateService,
    previewEmailTemplateService,
    sendTestEmailTemplateService,
    getEmailDepartementsService,
    getEmailHabillageService,
    saveEmailHabillageService,
    previewEmailHabillageService
};
