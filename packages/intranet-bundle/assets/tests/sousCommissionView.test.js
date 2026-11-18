import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createApp, defineComponent, h, nextTick, reactive } from 'vue';

const mocks = vi.hoisted(() => ({
  getAnneesService: vi.fn(),
  getSemestresService: vi.fn(),
  getCommissionSemestre: vi.fn(),
  validerCommission: vi.fn(),
  rouvrirCommission: vi.fn(),
  getHistoriqueCommission: vi.fn(),
  route: null,
  routerReplace: vi.fn(),
  anneeStore: null,
  anneeUnivStore: null,
  usersStore: null,
}));

vi.mock('@stores', () => ({
  useAnneeStore: () => mocks.anneeStore,
  useAnneeUnivStore: () => mocks.anneeUnivStore,
  useUsersStore: () => mocks.usersStore,
}));

vi.mock('@requests', () => ({
  getAnneesService: mocks.getAnneesService,
  getSemestresService: mocks.getSemestresService,
}));

vi.mock('vue-router', () => ({
  useRoute: () => mocks.route,
  useRouter: () => ({ replace: mocks.routerReplace }),
}));

vi.mock('@/service/commissionService', () => ({
  getCommissionSemestre: mocks.getCommissionSemestre,
  validerCommission: mocks.validerCommission,
  rouvrirCommission: mocks.rouvrirCommission,
  getHistoriqueCommission: mocks.getHistoriqueCommission,
}));

vi.mock('@components', async () => {
  const { defineComponent, h } = await import('vue');
  const stub = defineComponent({ setup: () => () => h('div') });
  return { HeaderComponent: stub, SimpleSkeleton: stub, Kpi: stub };
});

vi.mock('@heroicons/vue/24/outline', () => ({
  UsersIcon: {},
  ChartBarIcon: {},
  ArrowsRightLeftIcon: {},
  CheckCircleIcon: {},
  AcademicCapIcon: {},
}));

vi.mock('../views/Administration/SousCommission/components/SousCommissionApcTable.vue', async () => {
  const { defineComponent, h } = await import('vue');
  return { default: defineComponent({ setup: () => () => h('div') }) };
});

vi.mock('../views/Administration/SousCommission/components/SousCommissionClassiqueTable.vue', async () => {
  const { defineComponent, h } = await import('vue');
  return { default: defineComponent({ setup: () => () => h('div') }) };
});

import SousCommissionView from '../views/Administration/SousCommission/SousCommissionView.vue';

const SelectStub = defineComponent({
  props: {
    modelValue: Object,
    options: { type: Array, default: () => [] },
    inputId: String,
    optionLabel: String,
    placeholder: String,
    disabled: Boolean,
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('select', {
      id: props.inputId,
      value: String(props.modelValue?.id ?? ''),
      disabled: props.disabled,
      onChange: (event) => emit('update:modelValue', props.options.find((item) => String(item.id) === event.target.value) ?? null),
    }, [
      h('option', { value: '' }, props.placeholder),
      ...props.options.map((item) => h('option', { key: item.id, value: String(item.id) }, item[props.optionLabel])),
    ]);
  },
});

const annees = [
  { id: 1, libelle: 'Première année' },
  { id: 2, libelle: 'Deuxième année' },
];

const commission = {
  rows: [],
  columns: [],
  mode: 'classique',
  statistiques: {},
  validation: {},
  erreurs: [],
};

let mountedApps;

const flushUpdates = async () => {
  for (let index = 0; index < 8; index++) {
    await Promise.resolve();
    await nextTick();
  }
};

const mountView = () => {
  const container = document.createElement('div');
  document.body.appendChild(container);
  const app = createApp(SousCommissionView);
  app.component('Select', SelectStub);
  const componentStub = defineComponent({ setup: () => () => h('div') });
  ['Tag', 'InputText', 'Button', 'Message', 'Dialog'].forEach((name) => app.component(name, componentStub));
  app.mount(container);
  mountedApps.push({ app, container });
  return container;
};

describe('SousCommissionView - contexte de sélection', () => {
  beforeEach(() => {
    mountedApps = [];
    vi.clearAllMocks();
    mocks.route = reactive({ name: 'sous-commission', params: { anneeId: '2' }, query: {} });
    mocks.routerReplace.mockImplementation(async ({ params }) => {
      Object.assign(mocks.route.params, params);
    });
    mocks.anneeStore = reactive({ annee: null });
    mocks.anneeUnivStore = reactive({ selectedAnneeUniv: { id: 10 } });
    mocks.usersStore = reactive({ departementDefaut: { id: 5 } });
    mocks.getAnneesService.mockResolvedValue(annees);
    mocks.getSemestresService.mockImplementation(async ({ annee: anneeId }) => [{
      id: anneeId * 10,
      libelle: `Semestre ${anneeId}`,
    }]);
    mocks.getCommissionSemestre.mockResolvedValue(commission);
  });

  afterEach(() => {
    mountedApps.forEach(({ app, container }) => {
      app.unmount();
      container.remove();
    });
  });

  it('initialise le contexte depuis l’année de la route', async () => {
    const container = mountView();
    await flushUpdates();

    expect(mocks.getAnneesService).toHaveBeenCalledWith({ departement: 5, anneeUniversitaire: 10, actif: true }, '/liste', false);
    expect(mocks.getSemestresService).toHaveBeenCalledWith({ annee: 2, anneeUniversitaire: 10 });
    expect(mocks.getCommissionSemestre).toHaveBeenCalledWith(20);
    expect(container.querySelector('[aria-live="polite"]').textContent).toBe('Deuxième année · Semestre 2');
  });

  it('synchronise le changement d’année et recharge les semestres et la commission', async () => {
    const container = mountView();
    await flushUpdates();

    const selectAnnee = container.querySelector('#contexte-annee');
    selectAnnee.value = '1';
    selectAnnee.dispatchEvent(new Event('change', { bubbles: true }));
    await flushUpdates();

    expect(mocks.routerReplace).toHaveBeenCalledWith({
      name: 'sous-commission',
      params: { anneeId: '1' },
      query: {},
    });
    expect(mocks.getSemestresService).toHaveBeenLastCalledWith({ annee: 1, anneeUniversitaire: 10 });
    expect(mocks.getCommissionSemestre).toHaveBeenLastCalledWith(10);
    expect(container.querySelector('[aria-live="polite"]').textContent).toBe('Première année · Semestre 1');
  });

  it('vide le contexte si aucune année n’est disponible', async () => {
    mocks.getAnneesService.mockResolvedValueOnce([]);
    const container = mountView();
    await flushUpdates();

    expect(mocks.getSemestresService).not.toHaveBeenCalled();
    expect(mocks.getCommissionSemestre).not.toHaveBeenCalled();
    expect(container.querySelector('[aria-live="polite"]').textContent).toBe('Sélectionnez votre contexte');
  });

  it('recharge les données quand l’année universitaire change en externe', async () => {
    const container = mountView();
    await flushUpdates();
    mocks.getAnneesService.mockResolvedValueOnce([{ id: 3, libelle: 'Troisième année' }]);

    mocks.anneeUnivStore.selectedAnneeUniv = { id: 20 };
    await flushUpdates();

    expect(mocks.getAnneesService).toHaveBeenLastCalledWith({ departement: 5, anneeUniversitaire: 20, actif: true }, '/liste', false);
    expect(mocks.getSemestresService).toHaveBeenLastCalledWith({ annee: 3, anneeUniversitaire: 20 });
    expect(mocks.getCommissionSemestre).toHaveBeenLastCalledWith(30);
    expect(mocks.routerReplace).toHaveBeenLastCalledWith({
      name: 'sous-commission',
      params: { anneeId: '3' },
      query: {},
    });
    expect(container.querySelector('[aria-live="polite"]').textContent).toBe('Troisième année · Semestre 3');
  });
});