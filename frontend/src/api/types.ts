// Shapes returned by the Laravel API (/api/v1). These mirror the backend's
// API resources, not the Fake Store payload, so changing the product
// source doesn't change the frontend.

export interface Category {
  slug: string
  name: string
}

export interface Product {
  id: number
  title: string
  description: string
  price: number
  image_url: string
  category: Category
  rating: {
    rate: number
    count: number
  }
}

export interface CategoryListing {
  category: Category
  top_rated: Product[]
  other_products: Product[]
}

export interface EnquiryInput {
  product_id: number
  name: string
  email: string
  phone?: string
  message?: string
}
