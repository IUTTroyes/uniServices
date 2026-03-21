// Shared API Platform JSON-LD header for all resources
// This matches Hydra JSON-LD responses from API Platform
export interface JsonLd {
  "@context"?: string | null;
  "@id": string;
  "@type": string;
}

// Helper to compose a resource payload that includes JSON-LD header
export type ApiResource<T> = JsonLd & T;
