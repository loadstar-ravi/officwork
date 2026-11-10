// Fake Store prices are in US dollars; a real catalogue would send the
// currency with each price.
const currency = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' })

export function formatPrice(amount: number): string {
  return currency.format(amount)
}
