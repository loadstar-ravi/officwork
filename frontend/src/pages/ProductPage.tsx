import { useEffect, useRef, useState } from 'react'
import { Link, useParams } from 'react-router'
import { catalogueApi } from '../api/client'
import type { Product } from '../api/types'
import { EnquiryForm } from '../components/EnquiryForm'
import { ErrorMessage, Loading } from '../components/Status'
import { useAsync } from '../hooks/useAsync'
import { formatPrice } from '../lib/format'
import { NotFoundPage } from './NotFoundPage'

const loadProduct = (id: string) => catalogueApi.product(Number(id))

export function ProductPage() {
  const { id = '' } = useParams()
  const product = useAsync(id, loadProduct)

  if (product.status === 'loading') return <Loading />
  if (product.status === 'error') {
    return product.error.isNotFound ? (
      <NotFoundPage message="We couldn't find that product." />
    ) : (
      <ErrorMessage error={product.error} onRetry={product.retry} />
    )
  }

  // Keyed by id so enquiry state resets when moving between products.
  return <ProductDetails key={product.data.id} product={product.data} />
}

type EnquiryStep = { kind: 'closed' } | { kind: 'open' } | { kind: 'sent'; message: string }

function ProductDetails({ product }: { product: Product }) {
  const [enquiry, setEnquiry] = useState<EnquiryStep>({ kind: 'closed' })

  return (
    <article className="product-detail">
      <div className="product-detail__image-wrap">
        <img src={product.image_url} alt={product.title} className="product-detail__image" />
      </div>

      <div className="product-detail__info">
        <Link to={`/categories/${product.category.slug}`} className="product-detail__category">
          {product.category.name}
        </Link>
        <h1>{product.title}</h1>
        <p className="product-detail__price">{formatPrice(product.price)}</p>
        <p className="muted">
          ★ {product.rating.rate} ({product.rating.count} reviews)
        </p>
        <p>{product.description}</p>

        {enquiry.kind === 'closed' && (
          <button type="button" className="button" onClick={() => setEnquiry({ kind: 'open' })}>
            Enquire
          </button>
        )}
        {enquiry.kind === 'open' && (
          <EnquiryForm
            product={product}
            onSubmitted={(message) => setEnquiry({ kind: 'sent', message })}
          />
        )}
        {enquiry.kind === 'sent' && <ThankYou message={enquiry.message} />}
      </div>
    </article>
  )
}

function ThankYou({ message }: { message: string }) {
  const heading = useRef<HTMLHeadingElement>(null)

  // The form disappears on success; move focus so screen readers announce this.
  useEffect(() => {
    heading.current?.focus()
  }, [])

  return (
    <div role="status" className="notice notice--success">
      <h2 ref={heading} tabIndex={-1}>
        Thank you
      </h2>
      <p>{message}</p>
    </div>
  )
}
