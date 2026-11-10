import type { Category, CategoryListing, EnquiryInput, Product } from './types'

// Empty by default: requests go to /api on the same origin (the Vite proxy
// in dev). Set VITE_API_BASE_URL when the API lives on another domain.
const BASE_URL = `${import.meta.env.VITE_API_BASE_URL ?? ''}/api/v1`

export type FieldErrors = Record<string, string[]>

export class ApiError extends Error {
  readonly status: number
  readonly fieldErrors: FieldErrors

  constructor(message: string, status: number, fieldErrors: FieldErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
  }

  get isNotFound(): boolean {
    return this.status === 404
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (init.body) {
    headers['Content-Type'] = 'application/json'
  }

  let response: Response
  try {
    response = await fetch(`${BASE_URL}${path}`, { ...init, headers })
  } catch {
    throw new ApiError("We couldn't reach the server. Check your connection and try again.", 0)
  }

  const body: unknown = await response.json().catch(() => null)

  if (!response.ok) {
    const { message, errors } = (body ?? {}) as { message?: string; errors?: FieldErrors }
    throw new ApiError(message ?? 'Something went wrong. Please try again.', response.status, errors)
  }

  return body as T
}

type Wrapped<T> = { data: T }

export const catalogueApi = {
  featuredProducts: () =>
    request<Wrapped<Product[]>>('/featured-products').then((response) => response.data),

  categories: () => request<Wrapped<Category[]>>('/categories').then((response) => response.data),

  categoryListing: (slug: string) =>
    request<Wrapped<CategoryListing>>(`/categories/${encodeURIComponent(slug)}`).then(
      (response) => response.data,
    ),

  searchProducts: (term: string) =>
    request<Wrapped<Product[]>>(`/products?${new URLSearchParams({ search: term })}`).then(
      (response) => response.data,
    ),

  product: (id: number) =>
    request<Wrapped<Product>>(`/products/${id}`).then((response) => response.data),

  submitEnquiry: (input: EnquiryInput) =>
    request<{ message: string }>('/enquiries', { method: 'POST', body: JSON.stringify(input) }),
}
