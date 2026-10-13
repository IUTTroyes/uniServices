import { ref, computed } from 'vue';
import type { Ref } from 'vue';
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
            statut: null,
            department: null,
        },
        sortBy: 'nom',
        sortOrder: 'asc',
        viewMode: 'grid',
    });

    const filteredStudents = computed(() => {
        if (filters.value.mode !== 'students') return [];

        let result = [...students.value];

        // Apply search filter
        if (filters.value.searchTerm && filters.value.searchTerm.trim() !== '') {
            const searchLower = filters.value.searchTerm.toLowerCase().trim();
            result = result.filter(student => {
                const s = student as any;
                return (
                    (student.prenom && student.prenom.toLowerCase().includes(searchLower)) ||
                    (student.nom && student.nom.toLowerCase().includes(searchLower)) ||
                    (student.mailUniv && student.mailUniv.toLowerCase().includes(searchLower)) ||
                    (s.numEtudiant && String(s.numEtudiant).toLowerCase().includes(searchLower))
                );
            });
        }

        // Apply semester filter
        if (filters.value.studentFilters.semester !== null && filters.value.studentFilters.semester !== '') {
            const targetSem = String(filters.value.studentFilters.semester);
            result = result.filter(student => {
                const s = (student as any).semestre;
                const lib = (student as any).semestreLibelle;
                return (
                    (s !== undefined && s !== null && String(s) === targetSem) ||
                    (lib && String(lib).toLowerCase() === targetSem.toLowerCase())
                );
            });
        }

        // Apply group filters
        if (filters.value.studentFilters.groupType) {
            const groupType = filters.value.studentFilters.groupType;
            result = result.filter(student => {
                const s = student as any;
                const groups: string[] = s.groupes?.[groupType] || [];
                const groupIds: (string | number)[] = s.groupesIds?.[groupType] || [];

                if (filters.value.studentFilters.group !== null && filters.value.studentFilters.group !== '') {
                    const targetGroup = String(filters.value.studentFilters.group);
                    return (
                        groupIds.some(id => String(id) === targetGroup) ||
                        groups.some(lib => String(lib).toLowerCase() === targetGroup.toLowerCase())
                    );
                }
                return groups.length > 0;
            });
        }

        // Apply sorting
        result.sort((a, b) => {
            let aValue: any = '';
            let bValue: any = '';

            switch (filters.value.sortBy) {
                case 'prenom':
                    aValue = (a.prenom || '').toLowerCase();
                    bValue = (b.prenom || '').toLowerCase();
                    break;
                case 'nom':
                    aValue = (a.nom || '').toLowerCase();
                    bValue = (b.nom || '').toLowerCase();
                    break;
                case 'semester':
                    aValue = ((a as any).semestreLibelle || (a as any).semestre || '').toString().toLowerCase();
                    bValue = ((b as any).semestreLibelle || (b as any).semestre || '').toString().toLowerCase();
                    break;
                case 'groupe':
                    const aG = (a as any).groupes?.TD?.[0] || (a as any).groupes?.TP?.[0] || (a as any).groupes?.CM?.[0] || '';
                    const bG = (b as any).groupes?.TD?.[0] || (b as any).groupes?.TP?.[0] || (b as any).groupes?.CM?.[0] || '';
                    aValue = aG.toLowerCase();
                    bValue = bG.toLowerCase();
                    break;
                default:
                    aValue = (a.nom || '').toLowerCase();
                    bValue = (b.nom || '').toLowerCase();
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
        if (filters.value.searchTerm && filters.value.searchTerm.trim() !== '') {
            const searchLower = filters.value.searchTerm.toLowerCase().trim();
            result = result.filter(person => {
                const p = person as any;
                return (
                    (person.prenom && person.prenom.toLowerCase().includes(searchLower)) ||
                    (person.nom && person.nom.toLowerCase().includes(searchLower)) ||
                    (person.mailUniv && person.mailUniv.toLowerCase().includes(searchLower)) ||
                    (person.statut && person.statut.toLowerCase().includes(searchLower)) ||
                    (p.displayStatut && p.displayStatut.toLowerCase().includes(searchLower)) ||
                    (p.bureau && p.bureau.toLowerCase().includes(searchLower)) ||
                    (p.domaines && p.domaines.toLowerCase().includes(searchLower))
                );
            });
        }

        // Apply statut filter
        if (filters.value.staffFilters.statut) {
            result = result.filter(person => person.statut === filters.value.staffFilters.statut);
        }

        // Apply department filter
        if (filters.value.staffFilters.department) {
            result = result.filter(person => (person as any).department === filters.value.staffFilters.department);
        }

        // Apply sorting
        result.sort((a, b) => {
            let aValue: any = '';
            let bValue: any = '';

            switch (filters.value.sortBy) {
                case 'nom':
                    aValue = (a.nom || '').toLowerCase();
                    bValue = (b.nom || '').toLowerCase();
                    break;
                case 'prenom':
                    aValue = (a.prenom || '').toLowerCase();
                    bValue = (b.prenom || '').toLowerCase();
                    break;
                case 'statut':
                    aValue = (a.statut || '').toLowerCase();
                    bValue = (b.statut || '').toLowerCase();
                    break;
                default:
                    aValue = (a.nom || '').toLowerCase();
                    bValue = (b.nom || '').toLowerCase();
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
            mode: filters.value.mode,
            searchTerm: '',
            studentFilters: {
                semester: null,
                groupType: null,
                group: null,
            },
            staffFilters: {
                statut: null,
                department: null,
            },
            sortBy: 'nom',
            sortOrder: 'asc',
            viewMode: filters.value.viewMode,
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
            csvContent = '\uFEFFPrénom,Nom,Numéro étudiant,Email,Semestre,Groupes CM,Groupes TD,Groupes TP,Statut\n';
            (currentData as any[]).forEach(student => {
                const cm = (student.groupes?.CM || []).join('; ');
                const td = (student.groupes?.TD || []).join('; ');
                const tp = (student.groupes?.TP || []).join('; ');
                const row = [
                    student.prenom || '',
                    student.nom || '',
                    student.numEtudiant || '',
                    student.mailUniv || '',
                    student.semestreLibelle || student.semestre || '',
                    cm,
                    td,
                    tp,
                    student.status || 'actif'
                ].map(field => `"${String(field).replace(/"/g, '""')}"`).join(',');
                csvContent += row + '\n';
            });
        } else {
            csvContent = '\uFEFFPrénom,Nom,Email,Statut,Bureau,Téléphone,Département\n';
            (currentData as any[]).forEach(person => {
                const row = [
                    person.prenom || '',
                    person.nom || '',
                    person.mailUniv || '',
                    person.displayStatut || person.statut || '',
                    person.bureau || '',
                    person.telBureau || '',
                    person.department || ''
                ].map(field => `"${String(field).replace(/"/g, '""')}"`).join(',');
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
