import { ref, type Ref } from 'vue';
import {
  getEtudiantsService,
  getPersonnelsService,
  exportTrombinoscopeSemestreService,
  exportListingSemestreService,
} from '@requests';
import type { FilterState } from '@types';
import type { StudentCardItem, StaffCardItem, GroupesMap, GroupesIdsMap } from '@/types/trombinoscope';

export function mapEtudiantToStudentCard(
  e: any,
  selectedAnneeUnivId: number | null = null,
  semesterFilter: string | number | null = null
): StudentCardItem {
  const scolarites = e.scolarites || [];

  let activeScol: any = null;
  if (selectedAnneeUnivId) {
    activeScol =
      scolarites.find((s: any) => {
        const au = s?.anneeUniversitaire;
        const auId = au?.id ?? (typeof au === 'string' ? parseInt(au.split('/').pop() || '') : null);
        return auId === selectedAnneeUnivId && s?.actif === true;
      }) ||
      scolarites.find((s: any) => {
        const au = s?.anneeUniversitaire;
        const auId = au?.id ?? (typeof au === 'string' ? parseInt(au.split('/').pop() || '') : null);
        return auId === selectedAnneeUnivId;
      });
  }

  if (!activeScol) {
    activeScol = scolarites.find((s: any) => s?.actif === true) || scolarites[scolarites.length - 1] || null;
  }

  const scolariteSemestres = activeScol?.scolariteSemestre || [];

  let activeScolSem: any = null;
  if (semesterFilter) {
    activeScolSem = scolariteSemestres.find((ss: any) => {
      const sId = ss?.semestre?.id ?? ss?.legacyContext?.semestre?.oldId;
      return sId === semesterFilter;
    });
  }
  if (!activeScolSem) {
    activeScolSem = scolariteSemestres.find((ss: any) => ss?.groupes && ss.groupes.length > 0) || scolariteSemestres[0] || null;
  }

  const semObj = activeScolSem?.semestre;
  const legacySem = activeScolSem?.legacyContext?.semestre;

  const semestreId = semObj?.id ?? legacySem?.oldId ?? null;
  const semestreLibelle = semObj?.libelle ?? legacySem?.libelle ?? '';

  const groupesMap: GroupesMap = { CM: [], TD: [], TP: [] };
  const groupesIdsMap: GroupesIdsMap = { CM: [], TD: [], TP: [] };

  const rawGroups = activeScolSem?.groupes && activeScolSem.groupes.length > 0
    ? activeScolSem.groupes
    : (e.groupes || []);

  rawGroups.forEach((g: any) => {
    const rawType = String(g.type || 'TD').toUpperCase();
    const type = (['CM', 'TD', 'TP'].includes(rawType) ? rawType : 'TD') as 'CM' | 'TD' | 'TP';
    if (groupesMap[type]) {
      groupesMap[type].push(g.libelle);
      groupesIdsMap[type].push(g.id);
    }
  });

  return {
    id: e.id,
    prenom: e.prenom || '',
    nom: e.nom || '',
    mailUniv: e.mailUniv,
    mailPerso: e.mailPerso,
    photoName: e.photoName,
    scolarites,
    scolarite: semestreLibelle || (activeScol ? `Scolarité #${activeScol.id}` : 'Inscrit'),
    semestre: semestreId,
    semestreLibelle,
    groupes: groupesMap,
    groupesIds: groupesIdsMap,
    numEtudiant: e.num_etudiant || e.numEtudiant || '',
    bac: e.bac?.libelle || '',
    anneeSortie: e.annee_sortie ?? e.anneeSortie ?? 0,
    status: activeScol?.actif ? 'active' : 'inactive',
  };
}

export function mapPersonnelToStaffCard(p: any, departmentLibelle: string = ''): StaffCardItem {
  return {
    id: p.id,
    prenom: p.prenom || '',
    nom: p.nom || '',
    mailUniv: p.mailUniv,
    photoName: p.photoName,
    statut: p.statut || '',
    displayStatut: p.displayStatut || p.statut || '',
    statutSeverity: p.statutSeverity || 'info',
    bureau: p.bureau || '',
    telBureau: p.telBureau || '',
    posteInterne: p.posteInterne || '',
    domaines: Array.isArray(p.domaines) ? p.domaines.join(', ') : (p.domaines || ''),
    position: p.displayStatut || p.statut || '',
    department: departmentLibelle,
  };
}

export function useTrombinoscope(
  departementId: Ref<number | null | undefined>,
  selectedAnneeUnivId: Ref<number | null | undefined>,
  departementLibelle: Ref<string | undefined>,
  filters: Ref<FilterState>,
  existingStudents?: Ref<StudentCardItem[]>,
  existingStaff?: Ref<StaffCardItem[]>
) {
  const students = existingStudents || ref<StudentCardItem[]>([]);
  const staff = existingStaff || ref<StaffCardItem[]>([]);
  const loading = ref<boolean>(false);
  const exporting = ref<boolean>(false);
  const error = ref<string | null>(null);

  async function fetchData() {
    if (!departementId.value) {
      error.value = 'Aucun département par défaut sélectionné.';
      return;
    }

    loading.value = true;
    error.value = null;

    try {
      const params: Record<string, any> = {
        departement: departementId.value,
        anneeSortie: 0,
        pagination: false,
        itemsPerPage: 1000,
      };

      if (selectedAnneeUnivId.value) {
        params.anneeUniversitaire = selectedAnneeUnivId.value;
      }

      const [etudiantsResp, personnels] = await Promise.all([
        getEtudiantsService(params),
        getPersonnelsService({
          departement: departementId.value,
          pagination: false,
          itemsPerPage: 1000,
        }),
      ]);

      const etudiantsList: any[] = Array.isArray(etudiantsResp) ? etudiantsResp : (etudiantsResp?.member || []);

      const activeStudents = etudiantsList.filter((e: any) => {
        const anneeSortie = e.annee_sortie ?? e.anneeSortie ?? 0;
        if (anneeSortie !== 0) return false;

        if (selectedAnneeUnivId.value) {
          const hasScolInYear = (e.scolarites || []).some((s: any) => {
            const au = s?.anneeUniversitaire;
            const auId = au?.id ?? (typeof au === 'string' ? parseInt(au.split('/').pop() || '') : null);
            return auId === selectedAnneeUnivId.value;
          });
          if (!hasScolInYear) return false;
        }

        return true;
      });

      students.value = activeStudents.map((e) =>
        mapEtudiantToStudentCard(e, selectedAnneeUnivId.value ?? null, filters.value.studentFilters.semester)
      );

      const personnelsList: any[] = Array.isArray(personnels) ? personnels : (personnels?.member || []);
      staff.value = personnelsList.map((p) => mapPersonnelToStaffCard(p, departementLibelle.value || ''));
    } catch (e: any) {
      console.error('Erreur chargement données trombinoscope:', e);
      error.value = e?.message || 'Erreur lors de la récupération des données.';
    } finally {
      loading.value = false;
    }
  }

  const exportTrombiPdf = async (toast?: any) => {
    const semId = filters.value.studentFilters.semester;
    if (!semId) {
      toast?.add({
        severity: 'warn',
        summary: 'Export PDF',
        detail: 'Veuillez sélectionner un semestre pour exporter le trombinoscope.',
        life: 3000,
      });
      return;
    }

    exporting.value = true;
    try {
      await exportTrombinoscopeSemestreService(
        Number(semId),
        filters.value.studentFilters.groupType || null,
        filters.value.studentFilters.group ? Number(filters.value.studentFilters.group) : null
      );
      toast?.add({
        severity: 'success',
        summary: 'Export PDF',
        detail: 'Le trombinoscope PDF a été généré avec succès.',
        life: 3000,
      });
    } catch (err: any) {
      toast?.add({
        severity: 'error',
        summary: 'Erreur Export PDF',
        detail: err?.message || 'Impossible de générer le trombinoscope PDF.',
        life: 4000,
      });
    } finally {
      exporting.value = false;
    }
  };

  const exportListingExcel = async (toast?: any) => {
    const semId = filters.value.studentFilters.semester;
    if (!semId) {
      toast?.add({
        severity: 'warn',
        summary: 'Export Excel',
        detail: 'Veuillez sélectionner un semestre pour exporter le listing.',
        life: 3000,
      });
      return;
    }

    exporting.value = true;
    try {
      await exportListingSemestreService(
        Number(semId),
        'xlsx',
        filters.value.studentFilters.groupType || null,
        filters.value.studentFilters.group ? Number(filters.value.studentFilters.group) : null
      );
      toast?.add({
        severity: 'success',
        summary: 'Export Excel',
        detail: 'Le listing Excel a été téléchargé avec succès.',
        life: 3000,
      });
    } catch (err: any) {
      toast?.add({
        severity: 'error',
        summary: 'Erreur Export Excel',
        detail: err?.message || 'Impossible de générer le listing Excel.',
        life: 4000,
      });
    } finally {
      exporting.value = false;
    }
  };

  return {
    students,
    staff,
    loading,
    exporting,
    error,
    fetchData,
    exportTrombiPdf,
    exportListingExcel,
  };
}

