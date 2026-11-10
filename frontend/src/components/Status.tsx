import type { ApiError } from '../api/client'

export function Loading({ label = 'Loading…' }: { label?: string }) {
  return (
    <p role="status" className="muted">
      {label}
    </p>
  )
}

export function ErrorMessage({ error, onRetry }: { error: ApiError; onRetry?: () => void }) {
  return (
    <div role="alert" className="notice notice--error">
      <p>{error.message}</p>
      {onRetry && (
        <button type="button" className="button button--secondary" onClick={onRetry}>
          Try again
        </button>
      )}
    </div>
  )
}
