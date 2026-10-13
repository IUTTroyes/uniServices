export interface FilterState {
    mode: 'students' | 'staff';
    searchTerm: string;
    studentFilters: {
        semester: string | number | null;
        groupType: 'CM' | 'TD' | 'TP' | null;
        group: string | number | null;
    };
    staffFilters: {
        statut: string | null;
        department?: string | number | null;
    };
    sortBy: 'prenom' | 'nom' | 'semester' | 'statut' | 'groupe';
    sortOrder: 'asc' | 'desc';
    viewMode: 'grid' | 'list';
}
