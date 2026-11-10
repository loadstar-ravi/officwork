import { screen, within } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../api/client'
import { api, categories, makeProduct, renderApp } from '../test/utils'

// vi.mock is hoisted above imports, so the helper is imported inside the factory.
vi.mock('../api/client', async (importOriginal) => {
  const { mockCatalogueApi } = await import('../test/mockApi')
  return mockCatalogueApi(importOriginal)
})

describe('Category page', () => {
  beforeEach(() => {
    api.categories.mockResolvedValue(categories)
    api.featuredProducts.mockResolvedValue([])
  })

  it('shows top rated products and the other products when a category link is clicked', async () => {
    const mens = categories[1]
    api.categoryListing.mockResolvedValue({
      category: mens,
      top_rated: [makeProduct({ title: 'Cotton Jacket', category: mens })],
      other_products: [makeProduct({ title: 'Slim Fit', category: mens })],
    })

    const { user } = renderApp('/')
    const nav = await screen.findByRole('navigation', { name: 'Categories' })
    await user.click(within(nav).getByRole('link', { name: "men's clothing" }))

    expect(api.categoryListing).toHaveBeenCalledWith('mens-clothing')
    expect(await screen.findByRole('heading', { level: 1, name: "men's clothing" })).toBeInTheDocument()
    const topRated = screen.getByRole('region', { name: 'Top rated' })
    expect(within(topRated).getByText('Cotton Jacket')).toBeInTheDocument()
    const more = screen.getByRole('region', { name: "More in men's clothing" })
    expect(within(more).getByText('Slim Fit')).toBeInTheDocument()
  })

  it('hides the "More in" section when the category has no other products', async () => {
    api.categoryListing.mockResolvedValue({
      category: categories[0],
      top_rated: [makeProduct()],
      other_products: [],
    })

    renderApp('/categories/electronics')

    expect(await screen.findByRole('region', { name: 'Top rated' })).toBeInTheDocument()
    expect(screen.queryByRole('region', { name: /More in/ })).not.toBeInTheDocument()
  })

  it('shows not found for an unknown category', async () => {
    api.categoryListing.mockRejectedValue(new ApiError('Category not found.', 404))

    renderApp('/categories/garden')

    expect(await screen.findByText("We couldn't find that category.")).toBeInTheDocument()
  })
})
