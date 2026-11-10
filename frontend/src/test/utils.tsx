import { render } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { vi } from 'vitest'
import { catalogueApi } from '../api/client'
import type { Category, Product } from '../api/types'
import { routes } from '../routes'

/**
 * Render the whole app (real routes, layout and pages) at `path`.
 * Tests mock `catalogueApi` so no network is involved.
 */
export function renderApp(path = '/') {
  const router = createMemoryRouter(routes, { initialEntries: [path] })
  const user = userEvent.setup()
  render(<RouterProvider router={router} />)
  return { router, user }
}

/** Typed handle on the mocked API (see vi.mock in each test file). */
export const api = vi.mocked(catalogueApi)

export const categories: Category[] = [
  { slug: 'electronics', name: 'electronics' },
  { slug: 'mens-clothing', name: "men's clothing" },
]

let nextId = 1

export function makeProduct(overrides: Partial<Product> = {}): Product {
  const id = overrides.id ?? nextId++
  return {
    id,
    title: `Product ${id}`,
    description: `Description of product ${id}`,
    price: 10 + id,
    image_url: `https://example.com/${id}.png`,
    category: categories[0],
    rating: { rate: 4, count: 100 },
    ...overrides,
  }
}
