import { useState } from 'react'
import { api, ApiError } from '../lib/api'
import { formatDateTime, priorityLabels, statusLabels } from '../lib/labels'
import type { Ticket, TicketPriority, User } from '../types'
import { X } from 'lucide-react'
import { Avatar, Button, PriorityTag } from './ui'

interface TicketDetailsProps {
  ticket: Ticket
  user: User
  onTicketChange: () => void
  onClose: () => void
}

export default function TicketDetails({ ticket, user, onTicketChange, onClose }: TicketDetailsProps) {
  const isAgent = user.role !== 'customer'
  const [error, setError] = useState<string | null>(null)

  const run = async (action: () => Promise<unknown>) => {
    setError(null)

    try {
      await action()
      onTicketChange()
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Algo deu errado.')
    }
  }

  const changePriority = (priority: TicketPriority) =>
    run(() => api(`/api/tickets/${ticket.id}/priority`, { method: 'PATCH', body: { priority } }))

  const assignToMe = () =>
    run(() => api(`/api/tickets/${ticket.id}/assigment`, { method: 'PATCH', body: { assigned_id: user.id } }))

  return (
    <aside className="flex h-full w-full flex-col overflow-y-auto border-l border-slate-200 bg-white sm:w-80">
      <div className="flex justify-end p-2 xl:hidden">
        <button onClick={onClose} className="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100" aria-label="Fechar detalhes">
          <X className="size-4" />
        </button>
      </div>

      <div className="flex flex-col items-center border-b border-slate-100 px-6 pt-4 pb-6 text-center xl:pt-8">
        <Avatar name={ticket.requester?.name ?? '?'} size="lg" />
        <p className="mt-3 font-semibold text-slate-900">{ticket.requester?.name ?? 'Sem contato'}</p>
        {ticket.company && <p className="text-sm text-slate-500">{ticket.company.name}</p>}
      </div>

      <dl className="space-y-5 px-6 py-6 text-sm">
        <Detail label="Status">{statusLabels[ticket.status]}</Detail>

        <Detail label="Prioridade">
          {isAgent ? (
            <select
              value={ticket.priority}
              onChange={(event) => changePriority(event.target.value as TicketPriority)}
              className="w-full rounded-lg border-0 bg-slate-50 px-2 py-1.5 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-brand-500 focus:outline-none"
            >
              {Object.entries(priorityLabels).map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
          ) : (
            <PriorityTag priority={ticket.priority} />
          )}
        </Detail>

        <Detail label="Responsável">
          {ticket.assignee ? (
            <span className="flex items-center gap-2">
              <Avatar name={ticket.assignee.name} size="sm" /> {ticket.assignee.name}
            </span>
          ) : (
            <span className="text-slate-400">Ninguém ainda</span>
          )}
          {isAgent && ticket.assignee?.id !== user.id && (
            <Button variant="secondary" className="mt-2 w-full" onClick={assignToMe}>
              Atribuir a mim
            </Button>
          )}
        </Detail>

        {ticket.tags && ticket.tags.length > 0 && (
          <Detail label="Tags">
            <div className="flex flex-wrap gap-1.5">
              {ticket.tags.map((tag) => (
                <span key={tag.id} className="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                  {tag.name}
                </span>
              ))}
            </div>
          </Detail>
        )}

        <Detail label="Criado em">{formatDateTime(ticket.created_at)}</Detail>
        <Detail label="Atualizado em">{formatDateTime(ticket.updated_at)}</Detail>
      </dl>

      {error && <p className="mx-6 mb-6 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{error}</p>}
    </aside>
  )
}

function Detail({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <dt className="mb-1 text-[11px] font-semibold tracking-wide text-slate-400 uppercase">{label}</dt>
      <dd className="text-slate-700">{children}</dd>
    </div>
  )
}
