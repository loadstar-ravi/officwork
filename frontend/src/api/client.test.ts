import { afterEach, describe, expect, it, vi } from 'vitest'
import { ApiError, catalogueApi } from './client'

function mockFetch(status: number, body: unknown) {
  const fetchMock = vi.fn().mockResolvedValue(
    new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }),
  )
  vi.stubGlobal('fetch', fetchMock)
  return fetchMock
}

describe('catalogueApi', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('calls the versioned backend API and unwraps data', async () => {
    const fetchMock = mockFetch(200, { data: [{ slug: 'electronics', name: 'electronics' }] })

    await expect(catalogueApi.categories()).resolves.toEqual([{ slug: 'electronics', name: 'electronics' }])
    expect(fetchMock).toHaveBeenCalledWith('/api/v1/categories', expect.anything())
  })

  it('encodes the search term', async () => {
    const fetchMock = mockFetch(200, { data: [] })

    await catalogueApi.searchProducts("men's & co")

    expect(fetchMock).toHaveBeenCalledWith('/api/v1/products?search=men%27s+%26+co', expect.anything())
  })

  it('posts enquiries as JSON', async () => {
    const fetchMock = mockFetch(201, { message: 'Thank you' })

    await catalogueApi.submitEnquiry({ product_id: 3, name: 'Ada', email: 'ada@example.com' })

    expect(fetchMock).toHaveBeenCalledWith('/api/v1/enquiries', {
      method: 'POST',
      body: JSON.stringify({ product_id: 3, name: 'Ada', email: 'ada@example.com' }),
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    })
  })

  it('turns validation failures into an ApiError with field errors', async () => {
    mockFetch(422, { message: 'Invalid.', errors: { email: ['Bad email.'] } })

    const error = await catalogueApi.submitEnquiry({ product_id: 3, name: 'A', email: 'x' }).catch((e) => e)

    expect(error).toBeInstanceOf(ApiError)
    expect(error).toMatchObject({ status: 422, message: 'Invalid.', fieldErrors: { email: ['Bad email.'] } })
  })

  it('reports network failures as status 0', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))

    await expect(catalogueApi.featuredProducts()).rejects.toMatchObject({ status: 0 })
  })
})
