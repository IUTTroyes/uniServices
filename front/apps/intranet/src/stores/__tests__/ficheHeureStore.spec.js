// front/apps/intranet/src/stores/__tests__/ficheHeureStore.spec.js
import { setActivePinia, createPinia } from 'pinia';
import { useFicheHeureStore } from '../ficheHeureStore'; // Adjust path if your store is elsewhere e.g. @/stores/ficheHeureStore
import { describe, it, expect, beforeEach, vi } from 'vitest';

// Mock the ficheHeureService
vi.mock('@requests/ficheHeureService', () => ({
  getMesFichesHeures: vi.fn(),
  getFicheHeure: vi.fn(),
  createFicheHeure: vi.fn(),
  updateFicheHeure: vi.fn(),
  submitFicheHeure: vi.fn(),
  getFichesHeuresPourValidation: vi.fn(),
  validateFicheHeure: vi.fn(),
  rejectFicheHeure: vi.fn(),
}));


describe('FicheHeure Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    // Reset mocks before each test if needed
    // vi.clearAllMocks(); // Or reset specific mocks
  });

  it('initializes with correct default state', () => {
    const store = useFicheHeureStore();
    expect(store.mesFichesHeures).toEqual([]);
    expect(store.fichesHeuresPourValidation).toEqual([]);
    expect(store.currentFicheHeure).toBeNull();
    expect(store.isLoadingMesFichesHeures).toBe(false);
    expect(store.isLoadingFichesHeuresPourValidation).toBe(false);
    expect(store.isLoadingCurrentFicheHeure).toBe(false);
    expect(store.isSubmitting).toBe(false);
    expect(store.isValidating).toBe(false);
    expect(store.isRejecting).toBe(false);
    expect(store.error).toBeNull();
  });

  it('fetchMesFichesHeures action updates state (mock service)', async () => {
    const mockData = [{ id: 1, semaineAnnee: 'S01-2024', statut: 'BROUILLON' }];
    const ficheHeureService = await import('@requests/ficheHeureService');
    ficheHeureService.getMesFichesHeures.mockResolvedValue({ data: mockData }); // Mocking structure from apiCall

    const store = useFicheHeureStore();
    await store.fetchMesFichesHeures();

    expect(store.mesFichesHeures).toEqual(mockData);
    expect(store.isLoadingMesFichesHeures).toBe(false);
    expect(ficheHeureService.getMesFichesHeures).toHaveBeenCalledTimes(1);
  });
  
  it('createFicheHeure action calls service and returns data (mock service)', async () => {
    const newFicheData = { semaineAnnee: 'S02-2024', heures: [] };
    const mockResponse = { id: 2, ...newFicheData, statut: 'BROUILLON' };
    const ficheHeureService = await import('@requests/ficheHeureService');
    ficheHeureService.createFicheHeure.mockResolvedValue({ data: mockResponse });

    const store = useFicheHeureStore();
    const result = await store.createFicheHeure(newFicheData);

    expect(result).toEqual(mockResponse);
    expect(store.isSubmitting).toBe(false);
    expect(ficheHeureService.createFicheHeure).toHaveBeenCalledWith(newFicheData);
    // Optionally check if the store state (e.g., mesFichesHeures) is updated or if a refetch is triggered
  });


  // ... other action tests (update, submit, validate, reject, fetchFicheHeure, etc.)
  // Example for an action that sets an error
  it('fetchMesFichesHeures handles error state', async () => {
    const ficheHeureService = await import('@requests/ficheHeureService');
    const errorMessage = 'Network Error';
    ficheHeureService.getMesFichesHeures.mockRejectedValue(new Error(errorMessage));

    const store = useFicheHeureStore();
    await store.fetchMesFichesHeures();

    expect(store.error).toBe(errorMessage);
    expect(store.mesFichesHeures).toEqual([]); // Should remain empty or unchanged
    expect(store.isLoadingMesFichesHeures).toBe(false);
  });

  it('clearCurrentFicheHeure clears the currentFicheHeure state', () => {
    const store = useFicheHeureStore();
    store.currentFicheHeure = { id: 1, semaineAnnee: 'S01-2024' }; // Set some initial state
    store.clearCurrentFicheHeure();
    expect(store.currentFicheHeure).toBeNull();
  });

});
