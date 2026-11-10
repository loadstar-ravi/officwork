import { useState, type FormEvent } from 'react'
import { useNavigate, useSearchParams } from 'react-router'

export const MIN_SEARCH_LENGTH = 2

export function SearchBar() {
  const [searchParams] = useSearchParams()
  const [term, setTerm] = useState(searchParams.get('q') ?? '')
  const [showHint, setShowHint] = useState(false)
  const navigate = useNavigate()

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const trimmed = term.trim()

    if (trimmed.length < MIN_SEARCH_LENGTH) {
      setShowHint(true)
      return
    }

    setShowHint(false)
    navigate(`/search?${new URLSearchParams({ q: trimmed })}`)
  }

  return (
    <form role="search" className="search-bar" onSubmit={handleSubmit} noValidate>
      <label htmlFor="search-input" className="visually-hidden">
        Search products
      </label>
      <input
        id="search-input"
        type="search"
        className="search-bar__input"
        placeholder="Search products"
        value={term}
        maxLength={100}
        onChange={(event) => setTerm(event.target.value)}
        aria-describedby={showHint ? 'search-hint' : undefined}
      />
      <button type="submit" className="button">
        Search
      </button>
      {showHint && (
        <p id="search-hint" className="search-bar__hint" role="alert">
          Enter at least {MIN_SEARCH_LENGTH} characters.
        </p>
      )}
    </form>
  )
}
