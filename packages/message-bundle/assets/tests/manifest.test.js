import { describe, it, expect, vi } from 'vitest';
import manifest from '../manifest';

// Mock Vue component imports that manifest pulls in
vi.mock('@components/components/layout/AppLayout.vue', () => ({
  default: { name: 'AppLayout' },
}));
vi.mock('@images/logo/logo_intranet_iut_troyes.svg', () => ({
  default: 'logo.svg',
}));

describe('message manifest', () => {
  it('defines the bundle name and branding', () => {
    expect(manifest.name).toBe('message');
    expect(manifest.primaryColor).toBe('cyan');
  });

  it('exposes routes with correct paths and permissions', () => {
    expect(manifest.routes).toBeInstanceOf(Array);
    const rootRoute = manifest.routes.find((r) => r.path === '/message');
    expect(rootRoute).toBeDefined();

    const childPaths = rootRoute.children.map((c) => c.path);
    expect(childPaths).toContain('');
    expect(childPaths).toContain('nouveau');
    expect(childPaths).toContain('envoyes');
    expect(childPaths).toContain(':id');

    const nouveauRoute = rootRoute.children.find((c) => c.path === 'nouveau');
    expect(nouveauRoute.meta?.permission).toBe('isPersonnel');

    const envoyesRoute = rootRoute.children.find((c) => c.path === 'envoyes');
    expect(envoyesRoute.meta?.permission).toBe('isPersonnel');
  });

  it('registers widget definition function', () => {
    expect(typeof manifest.registerWidgets).toBe('function');
  });
});

