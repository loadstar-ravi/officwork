import { Link } from 'react-router'
import type { Product } from '../api/types'
import { formatPrice } from '../lib/format'

export function ProductCard({ product }: { product: Product }) {
  return (
    <Link to={`/products/${product.id}`} className="product-card">
      <div className="product-card__image-wrap">
        <img src={product.image_url} alt="" className="product-card__image" loading="lazy" />
      </div>
      <div className="product-card__body">
        <h3 className="product-card__title">{product.title}</h3>
        <p className="product-card__meta">
          <span className="product-card__price">{formatPrice(product.price)}</span>
          <span aria-label={`Rated ${product.rating.rate} out of 5`}>★ {product.rating.rate}</span>
        </p>
      </div>
    </Link>
  )
}
