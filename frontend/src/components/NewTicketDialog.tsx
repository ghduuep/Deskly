import { useState, type FormEvent } from 'react'
import { api, ApiError } from '../lib/api'
import type { Ticket } from '../types'
import { Button } from './ui'

interface NewTicketDialogProps {
  onClose: () => void
  onCreated: (ticket: Ticket) => void
}

export default function NewTicketDialog({ onClose, onCreated }: NewTicketDialogProps) {
  const [title, setTitle] = useState('')
  const [message, setMessage] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault()
    setIsSubmitting(true)
    setError(null)

    try {
      const response = await api<{ data: Ticket }>('/api/tickets', { method: 'POST', body: { title } })

      if (message.trim()) {
        await api(`/api/tickets/${response.data.id}/messages`, { method: 'POST', body: { content: message } })
      }

      onCreated(response.data)
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Não foi possível abrir o ticket.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4" onClick={onClose}>
      <form
        onSubmit={handleSubmit}
        onClick={(event) => event.stopPropagation()}
        className="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl"
      >
        <h2 className="text-lg font-semibold text-slate-900">Novo ticket</h2>
        <label className="block">
          <span className="text-sm font-medium text-slate-700">Assunto</span>
          <input
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            required
            maxLength={255}
            className="mt-1.5 w-full rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none"
          />
        </label>
        <label className="block">
          <span className="text-sm font-medium text-slate-700">Descreva o problema</span>
          <textarea
            value={message}
            onChange={(event) => setMessage(event.target.value)}
            rows={5}
            className="mt-1.5 w-full resize-none rounded-lg px-3 py-2 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none"
          />
        </label>
        {error && <p className="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" disabled={isSubmitting}>
            {isSubmitting ? 'Abrindo…' : 'Abrir ticket'}
          </Button>
        </div>
      </form>
    </div>
  )
}
