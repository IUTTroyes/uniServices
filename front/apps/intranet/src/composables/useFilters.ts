import { ref, computed } from 'vue';
import type { Ref } from 'vue'
import type { FilterState, Etudiant, Personnel } from '@types';

export function useFilters(students: Ref<Etudiant[]>, staff: Ref<Personnel[]>) {
    const filters = ref<FilterState>({
        mode: 'students',
        searchTerm: '',
        studentFilters: {
            semester: null,
            groupType: null,
            group: null,
        },
        staffFilters: {
            statut: null
        },
        sortBy: 'nom',
        sortOrder: 'asc',
        viewMode: 'grid',
    });
    console.log('useFilters')

    const filteredStudents = computed(() => {
        console.log('filteredStudents--')
        if (filters.value.mode !== 'students') return [];

        let result = [...students.value];

        // Apply search filter
        if (filters.value.searchTerm) {
            const searchLower = filters.value.searchTerm.toLowerCase();
            result = result.filter(student =>
                student.prenom.toLowerCase().includes(searchLower) ||
                student.nom.toLowerCase().includes(searchLower) ||
                student.mailUniv.toLowerCase().includes(searchLower)
            );
        }

        // Apply semester filter
        if (filters.value.studentFilters.semester) {
            result = result.filter(student => student.semestre === filters.value.studentFilters.semester);
        }

        // Apply group filters
        if (filters.value.studentFilters.groupType) {
            result = result.filter(student => {
                const groups = student.groupes[filters.value.studentFilters.groupType!];
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
                case 'prenom':
                    aValue = a.prenom.toLowerCase();
                    bValue = b.prenom.toLowerCase();
                    break;
                case 'nom':
                    aValue = a.nom.toLowerCase();
                    bValue = b.nom.toLowerCase();
                    break;
                case 'semester':
                    aValue = a.semestre;
                    bValue = b.semestre;
                    break;
                default:
                    aValue = a.nom.toLowerCase();
                    bValue = b.nom.toLowerCase();
            }

            if (aValue < bValue) return filters.value.sortOrder === 'asc' ? -1 : 1;
            if (aValue > bValue) return filters.value.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        return result;
    });

    const filteredStaff = computed(() => {
        if (filters.value.mode !== 'staff') return [];

        let result = [...staff.value];

        // Apply search filter
        if (filters.value.searchTerm) {
            const searchLower = filters.value.searchTerm.toLowerCase();
            result = result.filter(person =>
                person.prenom.toLowerCase().includes(searchLower) ||
                person.nom.toLowerCase().includes(searchLower) ||
                person.mailUniv.toLowerCase().includes(searchLower)
            );
        }

        // Apply statut filter
        if (filters.value.staffFilters.statut) {
            result = result.filter(person => person.statut === filters.value.staffFilters.statut);
        }

        // Apply sorting
        result.sort((a, b) => {
            let aValue: any, bValue: any;

            switch (filters.value.sortBy) {
                case 'nom':
                    aValue = a.nom.toLowerCase();
                    bValue = b.nom.toLowerCase();
                    break;
                case 'prenom':
                    aValue = a.prenom.toLowerCase();
                    bValue = b.prenom.toLowerCase();
                    break;
                case 'statut':
                    aValue = a.statut.toLowerCase();
                    bValue = b.statut.toLowerCase();
                    break;
                default:
                    aValue = a.nom.toLowerCase();
                    bValue = b.nom.toLowerCase();
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
                statut: null
            },
            sortBy: 'nom',
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
            (currentData as Etudiant[]).forEach(student => {
                const row = [
                    student.prenom,
                    student.nom,
                    student.mailUniv,
                    student.semestre.toString(),
                    student.groupes['CM'].join('; '),
                    student.groupes['TD'].join('; '),
                    student.groupes['TP'].join('; ')
                ].map(field => `"${field}"`).join(',');
                csvContent += row + '\n';
            });
        } else {
            csvContent = 'Prénom,Nom,Email,Statut,Département,Poste,Date d\'embauche\n';
            (currentData as Personnel[]).forEach(person => {
                const row = [
                    person.prenom,
                    person.nom,
                    person.mailUniv,
                    person.statut,
                    // person.department,
                    // person.position,
                    // person.hireDate
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
