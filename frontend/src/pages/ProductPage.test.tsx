import { screen, within } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../api/client'
import { api, categories, makeProduct, renderApp } from '../test/utils'

// vi.mock is hoisted above imports, so the helper is imported inside the factory.
vi.mock('../api/client', async (importOriginal) => {
  const { mockCatalogueApi } = await import('../test/mockApi')
  return mockCatalogueApi(importOriginal)
})

const jacket = makeProduct({
  id: 3,
  title: 'Mens Cotton Jacket',
  description: 'Great outerwear for Spring.',
  price: 55.99,
  category: categories[1],
  rating: { rate: 4.7, count: 500 },
})

describe('Product page', () => {
  beforeEach(() => {
    api.categories.mockResolvedValue(categories)
    api.featuredProducts.mockResolvedValue([jacket])
    api.product.mockResolvedValue(jacket)
  })

  it('shows the product details when a product tile is clicked', async () => {
    const { user, router } = renderApp('/')
    const section = await screen.findByRole('region', { name: 'Top rated' })
    await user.click(await within(section).findByRole('link', { name: /Mens Cotton Jacket/ }))

    expect(router.state.location.pathname).toBe('/products/3')
    expect(api.product).toHaveBeenCalledWith(3)
    expect(await screen.findByRole('heading', { level: 1, name: 'Mens Cotton Jacket' })).toBeInTheDocument()
    expect(screen.getByText('$55.99')).toBeInTheDocument()
    expect(screen.getByText('Great outerwear for Spring.')).toBeInTheDocument()
    const details = screen.getByRole('article')
    expect(within(details).getByRole('link', { name: "men's clothing" })).toHaveAttribute(
      'href',
      '/categories/mens-clothing',
    )
    expect(screen.getByRole('button', { name: 'Enquire' })).toBeInTheDocument()
  })

  it('shows not found for an unknown product', async () => {
    api.product.mockRejectedValue(new ApiError('Product not found.', 404))

    renderApp('/products/999')

    expect(await screen.findByText("We couldn't find that product.")).toBeInTheDocument()
  })
})
