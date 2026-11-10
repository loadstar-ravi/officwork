import { Link } from 'react-router'

export function NotFoundPage({ message = "We couldn't find that page." }: { message?: string }) {
  return (
    <section>
      <h1>Not found</h1>
      <p className="muted">{message}</p>
      <Link to="/">Back to the home page</Link>
    </section>
  )
}
