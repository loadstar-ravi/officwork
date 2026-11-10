import { vi } from 'vitest'

/**
 * Factory for vi.mock('../api/client'): keeps the real ApiError class and
 * replaces every catalogueApi call with a mock.
 */
export async function mockCatalogueApi(importOriginal: () => Promise<typeof import('../api/client')>) {
  const actual = await importOriginal()
  return {
    ...actual,
    catalogueApi: {
      featuredProducts: vi.fn(),
      categories: vi.fn(),
      categoryListing: vi.fn(),
      searchProducts: vi.fn(),
      product: vi.fn(),
      submitEnquiry: vi.fn(),
    },
  }
}
