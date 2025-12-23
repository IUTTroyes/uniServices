import api from '@helpers/axios';

const getFilenameFromDisposition = (disposition) => {
    if (!disposition) return null;
    // content-disposition: attachment; filename="export.xlsx"; filename*=UTF-8''export.xlsx
    const match = /filename\*=UTF-8''([^;\n]+)/i.exec(disposition) || /filename="?([^";\n]+)"?/i.exec(disposition);
    return match && match[1] ? decodeURIComponent(match[1]) : null;
};

const exportService = async (data) => {
    try {
        const response = await api.post('/api/export/previ', data, { responseType: 'blob' });
        const contentType = response.headers?.['content-type'] || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        const blob = new Blob([response.data], { type: contentType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        const fallbackName = 'export_previsionnel.xlsx';
        a.download = getFilenameFromDisposition(response.headers?.['content-disposition']) || fallbackName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => window.URL.revokeObjectURL(url), 1000);
        return true;
    } catch (error) {
        console.error('Erreur dans exportService:', error);
        throw error;
    }
}

export { exportService };
