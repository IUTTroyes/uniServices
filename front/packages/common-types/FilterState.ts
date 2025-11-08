export interface FilterState {
    mode: 'students' | 'staff';
    searchTerm: string;
    studentFilters: {
        semester: number | null;
        groupType: 'CM' | 'TD' | 'TP' | null;
        group: string | null;
    };
    staffFilters: {
        status: string | null;
        department: string | null;
    };
    sortBy: 'lastName' | 'firstName' | 'semester' | 'status';
    sortOrder: 'asc' | 'desc';
    viewMode: 'grid' | 'list';
}
