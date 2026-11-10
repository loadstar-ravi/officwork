import { useParams } from 'react-router'
import { catalogueApi } from '../api/client'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorMessage, Loading } from '../components/Status'
import { useAsync } from '../hooks/useAsync'
import { NotFoundPage } from './NotFoundPage'

export function CategoryPage() {
  const { slug = '' } = useParams()
  const listing = useAsync(slug, catalogueApi.categoryListing)

  if (listing.status === 'loading') return <Loading />
  if (listing.status === 'error') {
    return listing.error.isNotFound ? (
      <NotFoundPage message="We couldn't find that category." />
    ) : (
      <ErrorMessage error={listing.error} onRetry={listing.retry} />
    )
  }

  const { category, top_rated, other_products } = listing.data

  return (
    <>
      <h1 className="capitalize">{category.name}</h1>

      <section aria-labelledby="top-rated-heading">
        <h2 id="top-rated-heading">Top rated</h2>
        <ProductGrid products={top_rated} />
      </section>

      {other_products.length > 0 && (
        <section aria-labelledby="more-heading">
          <h2 id="more-heading">More in {category.name}</h2>
          <ProductGrid products={other_products} />
        </section>
      )}
    </>
  )
}
