export interface FilterState {
    mode: 'students' | 'staff';
    searchTerm: string;
    studentFilters: {
        semester: string | null;
        groupType: 'CM' | 'TD' | 'TP' | null;
        group: string | null;
    };
    staffFilters: {
        statut: string | null;
    };
    sortBy: 'prenom' | 'nom' | 'semester' | 'statut';
    sortOrder: 'asc' | 'desc';
    viewMode: 'grid' | 'list';
}
