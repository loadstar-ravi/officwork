import { catalogueApi } from '../api/client'
import { ProductGrid } from '../components/ProductGrid'
import { ErrorMessage, Loading } from '../components/Status'
import { useAsync } from '../hooks/useAsync'

export function HomePage() {
  const featured = useAsync('featured', catalogueApi.featuredProducts)

  return (
    <section aria-labelledby="featured-heading">
      <h1 id="featured-heading">Top rated</h1>
      {featured.status === 'loading' && <Loading />}
      {featured.status === 'error' && <ErrorMessage error={featured.error} onRetry={featured.retry} />}
      {featured.status === 'success' && (
        <ProductGrid products={featured.data} emptyMessage="No featured products yet." />
      )}
    </section>
  )
}
