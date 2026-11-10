import { useSearchParams } from 'react-router'
import { catalogueApi } from '../api/client'
import { ProductGrid } from '../components/ProductGrid'
import { MIN_SEARCH_LENGTH } from '../components/SearchBar'
import { ErrorMessage, Loading } from '../components/Status'
import { useAsync } from '../hooks/useAsync'

export function SearchPage() {
  const [searchParams] = useSearchParams()
  const term = (searchParams.get('q') ?? '').trim()

  if (term.length < MIN_SEARCH_LENGTH) {
    return (
      <>
        <h1>Search</h1>
        <p className="muted">Enter at least {MIN_SEARCH_LENGTH} characters in the search bar.</p>
      </>
    )
  }

  // Keyed by term so a new search remounts and starts fresh.
  return <SearchResults key={term} term={term} />
}

function SearchResults({ term }: { term: string }) {
  const results = useAsync(term, catalogueApi.searchProducts)

  return (
    <section aria-labelledby="results-heading">
      <h1 id="results-heading">Results for “{term}”</h1>
      {results.status === 'loading' && <Loading label="Searching…" />}
      {results.status === 'error' && <ErrorMessage error={results.error} onRetry={results.retry} />}
      {results.status === 'success' && (
        <>
          <p className="muted">
            {results.data.length} {results.data.length === 1 ? 'product' : 'products'} found
          </p>
          <ProductGrid products={results.data} emptyMessage={`No products match “${term}”.`} />
        </>
      )}
    </section>
  )
}
