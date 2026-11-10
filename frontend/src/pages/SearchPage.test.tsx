import { screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { api, categories, makeProduct, renderApp } from '../test/utils'

// vi.mock is hoisted above imports, so the helper is imported inside the factory.
vi.mock('../api/client', async (importOriginal) => {
  const { mockCatalogueApi } = await import('../test/mockApi')
  return mockCatalogueApi(importOriginal)
})

describe('Search', () => {
  beforeEach(() => {
    api.categories.mockResolvedValue(categories)
    api.featuredProducts.mockResolvedValue([])
  })

  it('shows only the matching products after clicking the search button', async () => {
    api.searchProducts.mockResolvedValue([makeProduct({ title: 'Mens Cotton Jacket' })])

    const { user, router } = renderApp('/')
    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), '  jacket ')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    expect(router.state.location.search).toBe('?q=jacket')
    expect(api.searchProducts).toHaveBeenCalledWith('jacket')
    expect(await screen.findByRole('heading', { name: 'Results for “jacket”' })).toBeInTheDocument()
    expect(screen.getByText('1 product found')).toBeInTheDocument()
    expect(screen.getByText('Mens Cotton Jacket')).toBeInTheDocument()
  })

  it('tells the customer when nothing matches', async () => {
    api.searchProducts.mockResolvedValue([])

    renderApp('/search?q=trampoline')

    expect(await screen.findByText('No products match “trampoline”.')).toBeInTheDocument()
  })

  it('asks for a longer term instead of searching for a single character', async () => {
    const { user } = renderApp('/')
    await user.type(screen.getByRole('searchbox', { name: 'Search products' }), 'a')
    await user.click(screen.getByRole('button', { name: 'Search' }))

    expect(screen.getByRole('alert')).toHaveTextContent('Enter at least 2 characters.')
    expect(api.searchProducts).not.toHaveBeenCalled()
  })
})
