import { useCallback, useEffect, useState } from 'react'
import { ApiError } from '../api/client'

type Settled<T> = { status: 'error'; error: ApiError } | { status: 'success'; data: T }

export type AsyncState<T> = { status: 'loading' } | Settled<T>

/**
 * Loads data for `key` and reloads whenever `key` changes.
 *
 * `loader` must be stable (a module-level function), so the effect only
 * re-runs when the key changes or the user retries. Each result is tagged
 * with the request it answers; a result for an older key or attempt counts
 * as "still loading", so fast navigation can't show the wrong page's data.
 */
export function useAsync<T>(
  key: string,
  loader: (key: string) => Promise<T>,
): AsyncState<T> & { retry: () => void } {
  const [attempt, setAttempt] = useState(0)
  const [settled, setSettled] = useState<{ requestId: string; result: Settled<T> } | null>(null)
  const requestId = `${attempt}:${key}`

  useEffect(() => {
    let isStale = false

    loader(key).then(
      (data) => {
        if (!isStale) setSettled({ requestId, result: { status: 'success', data } })
      },
      (error: unknown) => {
        if (isStale) return
        const apiError = error instanceof ApiError ? error : new ApiError('Something went wrong.', 0)
        setSettled({ requestId, result: { status: 'error', error: apiError } })
      },
    )

    return () => {
      isStale = true
    }
  }, [key, loader, requestId])

  const retry = useCallback(() => setAttempt((n) => n + 1), [])
  const state: AsyncState<T> =
    settled?.requestId === requestId ? settled.result : { status: 'loading' }

  return { ...state, retry }
}
