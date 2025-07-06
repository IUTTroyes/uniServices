import { ref, computed } from 'vue';
import type { FilterState, Student, Staff } from '../types';

export function useFilters(students: Student[], staff: Staff[]) {
    const filters = ref<FilterState>({
        mode: 'students',
        searchTerm: '',
        studentFilters: {
            semester: null,
            groupType: null,
            group: null,
        },
        staffFilters: {
            status: null,
            department: null,
        },
        sortBy: 'lastName',
        sortOrder: 'asc',
        viewMode: 'grid',
    });

    const filteredStudents = computed(() => {
        if (filters.value.mode !== 'students') return [];

        let result = [...students];

        // Apply search filter
        if (filters.value.searchTerm) {
            const searchLower = filters.value.searchTerm.toLowerCase();
            result = result.filter(student =>
                student.firstName.toLowerCase().includes(searchLower) ||
                student.lastName.toLowerCase().includes(searchLower) ||
                student.email.toLowerCase().includes(searchLower)
            );
        }

        // Apply semester filter
        if (filters.value.studentFilters.semester) {
            result = result.filter(student => student.semester === filters.value.studentFilters.semester);
        }

        // Apply group filters
        if (filters.value.studentFilters.groupType) {
            result = result.filter(student => {
                const groups = student.groups[filters.value.studentFilters.groupType!];
                if (filters.value.studentFilters.group) {
                    return groups.includes(filters.value.studentFilters.group);
                }
                return groups.length > 0;
            });
        }

        // Apply sorting
        result.sort((a, b) => {
            let aValue: any, bValue: any;

            switch (filters.value.sortBy) {
                case 'firstName':
                    aValue = a.firstName.toLowerCase();
                    bValue = b.firstName.toLowerCase();
                    break;
                case 'lastName':
                    aValue = a.lastName.toLowerCase();
                    bValue = b.lastName.toLowerCase();
                    break;
                case 'semester':
                    aValue = a.semester;
                    bValue = b.semester;
                    break;
                default:
                    aValue = a.lastName.toLowerCase();
                    bValue = b.lastName.toLowerCase();
            }

            if (aValue < bValue) return filters.value.sortOrder === 'asc' ? -1 : 1;
            if (aValue > bValue) return filters.value.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    });

    const filteredStaff = computed(() => {
        if (filters.value.mode !== 'staff') return [];

        let result = [...staff];

        // Apply search filter
        if (filters.value.searchTerm) {
            const searchLower = filters.value.searchTerm.toLowerCase();
            result = result.filter(person =>
                person.firstName.toLowerCase().includes(searchLower) ||
                person.lastName.toLowerCase().includes(searchLower) ||
                person.email.toLowerCase().includes(searchLower) ||
                person.position.toLowerCase().includes(searchLower) ||
                person.department.toLowerCase().includes(searchLower)
            );
        }

        // Apply status filter
        if (filters.value.staffFilters.status) {
            result = result.filter(person => person.status === filters.value.staffFilters.status);
        }

        // Apply department filter
        if (filters.value.staffFilters.department) {
            result = result.filter(person => person.department === filters.value.staffFilters.department);
        }

        // Apply sorting
        result.sort((a, b) => {
            let aValue: any, bValue: any;

            switch (filters.value.sortBy) {
                case 'firstName':
                    aValue = a.firstName.toLowerCase();
                    bValue = b.firstName.toLowerCase();
                    break;
                case 'lastName':
                    aValue = a.lastName.toLowerCase();
                    bValue = b.lastName.toLowerCase();
                    break;
                case 'status':
                    aValue = a.status.toLowerCase();
                    bValue = b.status.toLowerCase();
                    break;
                default:
                    aValue = a.lastName.toLowerCase();
                    bValue = b.lastName.toLowerCase();
            }

            if (aValue < bValue) return filters.value.sortOrder === 'asc' ? -1 : 1;
            if (aValue > bValue) return filters.value.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    });

    const updateFilters = (newFilters: Partial<FilterState>) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const resetFilters = () => {
        filters.value = {
            mode: filters.value.mode, // Keep the current mode
            searchTerm: '',
            studentFilters: {
                semester: null,
                groupType: null,
                group: null,
            },
            staffFilters: {
                status: null,
                department: null,
            },
            sortBy: 'lastName',
            sortOrder: 'asc',
            viewMode: filters.value.viewMode, // Keep the current view mode
        };
    };

    const exportData = () => {
        const currentData = filters.value.mode === 'students' ? filteredStudents.value : filteredStaff.value;

        if (currentData.length === 0) {
            alert('Aucune donnée à exporter');
            return;
        }

        let csvContent = '';

        if (filters.value.mode === 'students') {
            csvContent = 'Prénom,Nom,Email,Semestre,Statut,Groupes CM,Groupes TD,Groupes TP\n';
            (currentData as Student[]).forEach(student => {
                const row = [
                    student.firstName,
                    student.lastName,
                    student.email,
                    student.semester.toString(),
                    student.status,
                    student.groups.CM.join(';'),
                    student.groups.TD.join(';'),
                    student.groups.TP.join(';')
                ].map(field => `"${field}"`).join(',');
                csvContent += row + '\n';
            });
        } else {
            csvContent = 'Prénom,Nom,Email,Statut,Département,Poste,Date d\'embauche\n';
            (currentData as Staff[]).forEach(person => {
                const row = [
                    person.firstName,
                    person.lastName,
                    person.email,
                    person.status,
                    person.department,
                    person.position,
                    person.hireDate
                ].map(field => `"${field}"`).join(',');
                csvContent += row + '\n';
            });
        }

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `trombinoscope_${filters.value.mode}_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    return {
        filters,
        filteredStudents,
        filteredStaff,
        updateFilters,
        resetFilters,
        exportData,
    };
}
