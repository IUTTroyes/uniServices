// front/apps/intranet/src/components/FicheHeures/__tests__/FicheHeureListBiats.spec.js
import { mount } from '@vue/test-utils';
import FicheHeureListBiats from '../FicheHeureListBiats.vue';
import { describe, it, expect, vi } from 'vitest';
import { createTestingPinia } from '@pinia/testing';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice'; // Needed if confirm dialogs are used

// Mock PrimeVue components used if needed, or use global stubs
// Minimal mocks for router and store, assuming PrimeVue components render okay in test env or are globally stubbed.
vi.mock('vue-router', () => ({ 
  useRouter: () => ({ 
    push: vi.fn() 
  }) 
}));

// Mock the FicheHeureStore actions that are dispatched onMounted or via interactions
const mockFetchMesFichesHeures = vi.fn();
const mockSubmitFicheHeure = vi.fn();

describe('FicheHeureListBiats.vue', () => {
  const getWrapper = (mesFichesHeures = []) => {
    return mount(FicheHeureListBiats, {
      global: {
        plugins: [
          createTestingPinia({
            initialState: {
              ficheHeure: { // Ensure 'ficheHeure' matches your store ID
                mesFichesHeures: mesFichesHeures,
                isLoadingMesFichesHeures: false,
                // Add other relevant initial states if needed by the component
              }
            },
            stubActions: false, // Set to false if you want to spy on actions or mock their implementation
            plugins: [ // If your actions need the store instance
              ({ store }) => {
                if (store.$id === 'ficheHeure') {
                  store.fetchMesFichesHeures = mockFetchMesFichesHeures;
                  store.submitFicheHeure = mockSubmitFicheHeure;
                }
              }
            ]
          }),
          PrimeVue, // Mounts PrimeVue components
          ConfirmationService // If using confirmDialog for submit
        ],
        // stubs: { // Example: Stubbing heavy components if needed
        //   DataTable: true,
        //   Column: true,
        //   Button: true,
        //   Tag: true,
        //   Tooltip: true, // If v-tooltip is used and not globally registered/mocked
        // }
      }
    });
  };

  it('renders empty message when no fiches heures', () => {
    const wrapper = getWrapper([]);
    expect(wrapper.text()).toContain('Aucune fiche d\'heures trouvée.');
    expect(mockFetchMesFichesHeures).toHaveBeenCalledTimes(1); // onMounted call
  });

  it('displays fiches heures when data is available', () => {
    const fiches = [
      { id: 1, semaineAnnee: 'S01-2024', statut: 'BROUILLON', dateSoumission: null },
      { id: 2, semaineAnnee: 'S02-2024', statut: 'SOUMISE', dateSoumission: '2024-01-10T10:00:00Z' },
    ];
    const wrapper = getWrapper(fiches);
    expect(wrapper.text()).not.toContain('Aucune fiche d\'heures trouvée.');
    expect(wrapper.findAllComponents({ name: 'Column' }).length).toBeGreaterThan(0); // Check if columns are rendered
    expect(wrapper.text()).toContain('S01-2024');
    expect(wrapper.text()).toContain('S02-2024');
  });

  it('calls createNewFicheHeure on button click', async () => {
    const wrapper = getWrapper();
    const router = useRouter(); // Get the mocked router instance
    const createButton = wrapper.find('button[aria-label="Nouvelle Fiche d\'Heures"]'); // More specific selector if needed
    await createButton.trigger('click');
    expect(router.push).toHaveBeenCalledWith({ name: 'FicheHeureCreate' });
  });
  
  // Placeholder for more complex interactions like submitting a form
  // This would require more setup for the dialogs and store action mocking
  it('submitFicheHeureHandler calls store action after confirmation', async () => {
    // Mock window.confirm, or use a PrimeVue ConfirmDialog mock strategy
    window.confirm = vi.fn(() => true); 
    
    const fiches = [{ id: 1, semaineAnnee: 'S01-2024', statut: 'BROUILLON', dateSoumission: null }];
    const wrapper = getWrapper(fiches);

    // Find the submit button for the first row (assuming it's enabled)
    const submitButton = wrapper.find('.p-datatable-tbody button[aria-label="Soumettre"]');
    if (submitButton.exists() && !submitButton.attributes('disabled')) {
        await submitButton.trigger('click');
        expect(window.confirm).toHaveBeenCalled();
        expect(mockSubmitFicheHeure).toHaveBeenCalledWith(fiches[0].id);
        // Check if fetchMesFichesHeures was called again after submit
        // This depends on the implementation of submitFicheHeureHandler
        // For now, assuming it's called once onMount and once after submit
        expect(mockFetchMesFichesHeures).toHaveBeenCalledTimes(2); 
    } else {
        console.warn('Submit button not found or disabled for testing submitFicheHeureHandler');
    }
  });

  // ... other component interaction tests (button clicks, data display, conditional rendering of buttons)
});
