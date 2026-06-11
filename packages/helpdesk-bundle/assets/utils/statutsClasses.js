const getStatutsClasses = (statut) => {
    switch (statut) {
        case 'À traiter': return 'bg-blue-100 text-blue-700 border-blue-200';
        case 'En cours': return 'bg-orange-100 text-orange-700 border-orange-200';
        case 'En attente': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        case 'Refusé': return 'bg-red-100 text-red-700 border-red-200';
        case 'Clôturé': return 'bg-green-100 text-green-700 border-green-200';
        case 'Accepté': return 'bg-blue-100 text-blue-700 border-blue-200';
        default:          return 'bg-gray-100 text-gray-700 border-gray-200';
    }
};
