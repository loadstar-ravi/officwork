import { screen } from '@testing-library/react'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../api/client'
import { api, categories, makeProduct, renderApp } from '../test/utils'

// vi.mock is hoisted above imports, so the helper is imported inside the factory.
vi.mock('../api/client', async (importOriginal) => {
  const { mockCatalogueApi } = await import('../test/mockApi')
  return mockCatalogueApi(importOriginal)
})

const product = makeProduct({ id: 3, title: 'Mens Cotton Jacket' })

async function openEnquiryForm() {
  const app = renderApp('/products/3')
  await app.user.click(await screen.findByRole('button', { name: 'Enquire' }))
  return app
}

describe('Enquiry', () => {
  beforeEach(() => {
    api.categories.mockResolvedValue(categories)
    api.product.mockResolvedValue(product)
  })

  it('submits contact details and shows a thank you message', async () => {
    api.submitEnquiry.mockResolvedValue({
      message: 'Thank you for your enquiry. Our sales team will be in touch soon.',
    })

    const { user } = await openEnquiryForm()
    expect(screen.getByLabelText('Name')).toHaveFocus()

    await user.type(screen.getByLabelText('Name'), 'Ada Lovelace')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.type(screen.getByLabelText('Phone (optional)'), '+44 7700 900123')
    await user.type(screen.getByLabelText('Message (optional)'), 'Do you have this in large?')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    expect(api.submitEnquiry).toHaveBeenCalledWith({
      product_id: 3,
      name: 'Ada Lovelace',
      email: 'ada@example.com',
      phone: '+44 7700 900123',
      message: 'Do you have this in large?',
    })
    expect(await screen.findByRole('heading', { name: 'Thank you' })).toHaveFocus()
    expect(screen.getByText(/Our sales team will be in touch soon/)).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Send enquiry' })).not.toBeInTheDocument()
  })

  it('leaves optional fields out of the request when empty', async () => {
    api.submitEnquiry.mockResolvedValue({ message: 'Thanks' })

    const { user } = await openEnquiryForm()
    await user.type(screen.getByLabelText('Name'), 'Ada')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    expect(api.submitEnquiry).toHaveBeenCalledWith({
      product_id: 3,
      name: 'Ada',
      email: 'ada@example.com',
      phone: undefined,
      message: undefined,
    })
  })

  it('shows server validation errors next to the right field', async () => {
    api.submitEnquiry.mockRejectedValue(
      new ApiError('The email field must be a valid email address.', 422, {
        email: ['The email field must be a valid email address.'],
      }),
    )

    const { user } = await openEnquiryForm()
    await user.type(screen.getByLabelText('Name'), 'Ada')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    expect(await screen.findByText('The email field must be a valid email address.')).toBeInTheDocument()
    expect(screen.getByLabelText('Email')).toHaveAttribute('aria-invalid', 'true')
    expect(screen.getByLabelText('Email')).toHaveAccessibleDescription(
      'The email field must be a valid email address.',
    )
    expect(screen.queryByRole('heading', { name: 'Thank you' })).not.toBeInTheDocument()
  })

  it('explains when the customer is sending too many enquiries', async () => {
    api.submitEnquiry.mockRejectedValue(new ApiError('Too Many Attempts.', 429))

    const { user } = await openEnquiryForm()
    await user.type(screen.getByLabelText('Name'), 'Ada')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('Too many enquiries')
  })
})
