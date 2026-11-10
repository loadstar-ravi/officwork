import { Link, NavLink, Outlet } from 'react-router'
import { catalogueApi } from '../api/client'
import { useAsync } from '../hooks/useAsync'
import { SearchBar } from './SearchBar'

export function Layout() {
  const categories = useAsync('categories', catalogueApi.categories)

  return (
    <div className="layout">
      <header className="site-header">
        <div className="container site-header__inner">
          <Link to="/" className="site-header__brand">
            Catalogue
          </Link>
          <SearchBar />
        </div>
        {/* If categories fail to load, hide the nav rather than break the page. */}
        {categories.status === 'success' && (
          <nav aria-label="Categories" className="category-nav">
            <ul className="container category-nav__list">
              {categories.data.map((category) => (
                <li key={category.slug}>
                  <NavLink to={`/categories/${category.slug}`} className="category-nav__link">
                    {category.name}
                  </NavLink>
                </li>
              ))}
            </ul>
          </nav>
        )}
      </header>

      <main className="container site-main">
        <Outlet />
      </main>

      <footer className="site-footer">
        <div className="container">Product data from the Fake Store API.</div>
      </footer>
    </div>
  )
}
