import api from '@helpers/axios';

const getFilenameFromDisposition = (disposition, fallbackName) => {
    if (!disposition) return fallbackName;
    const match = /filename\*=UTF-8''([^;\n]+)/i.exec(disposition) || /filename="?([^";\n]+)"?/i.exec(disposition);
    return match && match[1] ? decodeURIComponent(match[1]) : fallbackName;
};

const triggerBlobDownload = (response, fallbackName) => {
    const contentType = response.headers?.['content-type'] || 'application/octet-stream';
    const blob = new Blob([response.data], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = getFilenameFromDisposition(response.headers?.['content-disposition'], fallbackName);
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => window.URL.revokeObjectURL(url), 1000);
    return true;
};

const exportListingGroupeService = async (groupeId, format = 'pdf', semestreId = null) => {
    try {
        const response = await api.get(`/api/intranet/export/listing/groupe/${groupeId}`, {
            params: {
                format,
                semestre: semestreId,
            },
            responseType: 'blob',
        });
        const ext = format === 'xlsx' ? 'xlsx' : (format === 'csv' ? 'csv' : 'pdf');
        return triggerBlobDownload(response, `listing_groupe_${groupeId}.${ext}`);
    } catch (error) {
        console.error('Erreur dans exportListingGroupeService:', error);
        throw error;
    }
};

const exportListingSemestreService = async (semestreId, format = 'pdf', type = null, groupeId = null) => {
    try {
        const params = { format };
        if (type && type !== 'ALL') {
            params.type = type;
        }
        if (groupeId) {
            params.groupeId = groupeId;
        }

        const response = await api.get(`/api/intranet/export/listing/semestre/${semestreId}`, {
            params,
            responseType: 'blob',
        });
        const ext = format === 'xlsx' ? 'xlsx' : (format === 'csv' ? 'csv' : 'pdf');
        return triggerBlobDownload(response, `listing_semestre_${semestreId}.${ext}`);
    } catch (error) {
        console.error('Erreur dans exportListingSemestreService:', error);
        throw error;
    }
};

const exportTrombinoscopeSemestreService = async (semestreId, type = null, groupeId = null) => {
    try {
        const params = {};
        if (type && type !== 'ALL') {
            params.type = type;
        }
        if (groupeId) {
            params.groupeId = groupeId;
        }

        const response = await api.get(`/api/intranet/export/trombinoscope/semestre/${semestreId}`, {
            params,
            responseType: 'blob',
        });
        return triggerBlobDownload(response, `trombinoscope_semestre_${semestreId}.pdf`);
    } catch (error) {
        console.error('Erreur dans exportTrombinoscopeSemestreService:', error);
        throw error;
    }
};

const exportPersonnaliseService = async (payload) => {
    try {
        const response = await api.post('/api/intranet/export/personnalise', payload, {
            responseType: 'blob',
        });
        const format = payload.format || 'xlsx';
        const ext = format === 'pdf' ? 'pdf' : (format === 'csv' ? 'csv' : 'xlsx');
        return triggerBlobDownload(response, `export_personnalise.${ext}`);
    } catch (error) {
        console.error('Erreur dans exportPersonnaliseService:', error);
        throw error;
    }
};

export {
    exportListingGroupeService,
    exportListingSemestreService,
    exportTrombinoscopeSemestreService,
    exportPersonnaliseService,
};
