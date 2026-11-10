import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react'
import { ApiError, catalogueApi, type FieldErrors } from '../api/client'
import type { Product } from '../api/types'

interface EnquiryFormProps {
  product: Product
  onSubmitted: (thankYouMessage: string) => void
}

type FormStatus = { kind: 'idle' } | { kind: 'submitting' } | { kind: 'error'; message: string }

export function EnquiryForm({ product, onSubmitted }: EnquiryFormProps) {
  const [status, setStatus] = useState<FormStatus>({ kind: 'idle' })
  const [fieldErrors, setFieldErrors] = useState<FieldErrors>({})
  const nameInput = useRef<HTMLInputElement>(null)

  // The form appears after clicking "Enquire"; move focus into it.
  useEffect(() => {
    nameInput.current?.focus()
  }, [])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const value = (field: string) => String(form.get(field) ?? '').trim()

    setStatus({ kind: 'submitting' })
    setFieldErrors({})

    try {
      const response = await catalogueApi.submitEnquiry({
        product_id: product.id,
        name: value('name'),
        email: value('email'),
        phone: value('phone') || undefined,
        message: value('message') || undefined,
      })
      onSubmitted(response.message)
    } catch (error) {
      if (error instanceof ApiError && error.status === 422) {
        setFieldErrors(error.fieldErrors)
        setStatus({ kind: 'error', message: 'Please check the highlighted fields.' })
      } else if (error instanceof ApiError && error.status === 429) {
        setStatus({ kind: 'error', message: 'Too many enquiries. Please wait a minute and try again.' })
      } else {
        const message = error instanceof ApiError ? error.message : 'Something went wrong.'
        setStatus({ kind: 'error', message })
      }
    }
  }

  const isSubmitting = status.kind === 'submitting'

  return (
    <form className="enquiry-form" onSubmit={handleSubmit} aria-labelledby="enquiry-heading">
      <h2 id="enquiry-heading">Enquire about this product</h2>

      {status.kind === 'error' && (
        <p role="alert" className="notice notice--error">
          {status.message}
        </p>
      )}

      <Field label="Name" name="name" errors={fieldErrors.name}>
        {(props) => (
          <input {...props} ref={nameInput} type="text" autoComplete="name" required maxLength={100} />
        )}
      </Field>

      <Field label="Email" name="email" errors={fieldErrors.email}>
        {(props) => <input {...props} type="email" autoComplete="email" required maxLength={255} />}
      </Field>

      <Field label="Phone (optional)" name="phone" errors={fieldErrors.phone}>
        {(props) => <input {...props} type="tel" autoComplete="tel" maxLength={30} />}
      </Field>

      <Field label="Message (optional)" name="message" errors={fieldErrors.message}>
        {(props) => <textarea {...props} rows={4} maxLength={2000} />}
      </Field>

      {fieldErrors.product_id && (
        <p role="alert" className="notice notice--error">
          {fieldErrors.product_id[0]}
        </p>
      )}

      <button type="submit" className="button" disabled={isSubmitting}>
        {isSubmitting ? 'Sending…' : 'Send enquiry'}
      </button>
    </form>
  )
}

interface FieldInputProps {
  id: string
  name: string
  className: string
  'aria-invalid': boolean
  'aria-describedby'?: string
}

function Field({
  label,
  name,
  errors,
  children,
}: {
  label: string
  name: string
  errors?: string[]
  children: (props: FieldInputProps) => ReactNode
}) {
  const id = `enquiry-${name}`
  const errorId = `${id}-error`
  const hasError = Boolean(errors?.length)

  return (
    <div className="field">
      <label htmlFor={id} className="field__label">
        {label}
      </label>
      {children({
        id,
        name,
        className: 'field__input',
        'aria-invalid': hasError,
        'aria-describedby': hasError ? errorId : undefined,
      })}
      {hasError && (
        <p id={errorId} className="field__error">
          {errors?.[0]}
        </p>
      )}
    </div>
  )
}
