import { screen, within } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../api/client'
import { api, categories, makeProduct, renderApp } from '../test/utils'

// vi.mock is hoisted above imports, so the helper is imported inside the factory.
vi.mock('../api/client', async (importOriginal) => {
  const { mockCatalogueApi } = await import('../test/mockApi')
  return mockCatalogueApi(importOriginal)
})

describe('Home page', () => {
  beforeEach(() => {
    api.categories.mockResolvedValue(categories)
  })

  it('shows the 5 featured products', async () => {
    const featured = [1, 2, 3, 4, 5].map((id) => makeProduct({ id, title: `Featured ${id}` }))
    api.featuredProducts.mockResolvedValue(featured)

    renderApp('/')

    const section = await screen.findByRole('region', { name: 'Top rated' })
    const tiles = await within(section).findAllByRole('link')
    expect(tiles).toHaveLength(5)
    expect(tiles.map((tile) => within(tile).getByRole('heading').textContent)).toEqual([
      'Featured 1',
      'Featured 2',
      'Featured 3',
      'Featured 4',
      'Featured 5',
    ])
  })

  it('lists categories in the navigation', async () => {
    api.featuredProducts.mockResolvedValue([])

    renderApp('/')

    const nav = await screen.findByRole('navigation', { name: 'Categories' })
    expect(within(nav).getByRole('link', { name: "men's clothing" })).toHaveAttribute(
      'href',
      '/categories/mens-clothing',
    )
  })

  it('shows an error with a retry when products fail to load', async () => {
    api.featuredProducts
      .mockRejectedValueOnce(new ApiError('Products are temporarily unavailable.', 503))
      .mockResolvedValueOnce([makeProduct({ title: 'Back again' })])

    const { user } = renderApp('/')

    expect(await screen.findByRole('alert')).toHaveTextContent('Products are temporarily unavailable.')
    await user.click(screen.getByRole('button', { name: 'Try again' }))
    expect(await screen.findByText('Back again')).toBeInTheDocument()
  })
})
