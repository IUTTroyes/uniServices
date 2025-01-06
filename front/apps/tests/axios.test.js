import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import axios from 'axios';

// Mock Axios
vi.mock('axios', () => {
  const createMock = vi.fn(() => ({
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() },
    },
    defaults: {
      baseURL: 'http://mocked-url.com',
    },
  }));

  return {
    default: {
      create: createMock,
    },
    create: createMock,
  };
});

// Importez `api` après avoir configuré le mock
import api from '@helpers/axios';

describe('Axios instance', () => {
  it('should set baseURL correctly', () => {
    expect(api.defaults.baseURL).toBe('http://mocked-url.com');
  });

  it('should add request interceptors', () => {
    expect(api.interceptors.request.use).toHaveBeenCalled();
  });

  it('should add response interceptors', () => {
    expect(api.interceptors.response.use).toHaveBeenCalled();
  });
});
