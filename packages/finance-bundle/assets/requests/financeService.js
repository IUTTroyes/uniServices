import api from '@helpers/axios';

/**
 * Service pour la gestion des demandes d'achat et bons de commande
 */
export async function getFinanceBonsCommandeService(params = {}) {
    return api.get('/api/finance_bon_commandes', { params });
}

export async function getFinanceBonCommandeDetailService(id) {
    return api.get(`/api/finance_bon_commandes/${id}`);
}

export async function createFinanceBonCommandeService(data) {
    return api.post('/api/finance_bon_commandes', data);
}

export async function updateFinanceBonCommandeService(id, data) {
    return api.patch(`/api/finance_bon_commandes/${id}`, data, {
        headers: {
            'Content-Type': 'application/merge-patch+json',
        },
    });
}

export async function deleteFinanceBonCommandeService(id) {
    return api.delete(`/api/finance_bon_commandes/${id}`);
}

export async function getFinanceFournisseursService(params = {}) {
    return api.get('/api/finance_fournisseurs', { params });
}

export async function createFinanceReceptionService(data) {
    return api.post('/api/finance_receptions', data);
}

export async function updateFinanceReceptionService(id, data) {
    return api.patch(`/api/finance_receptions/${id}`, data, {
        headers: {
            'Content-Type': 'application/merge-patch+json',
        },
    });
}
