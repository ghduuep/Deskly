import { Link } from 'react-router-dom'
import { formatRelative, statusLabels } from '../lib/labels'
import type { Ticket, TicketStatus } from '../types'
import { Plus, Search } from 'lucide-react'
import { Avatar, PriorityTag, StatusBadge } from './ui'

export type StatusFilter = TicketStatus | 'all'

const filters: StatusFilter[] = ['all', 'open', 'pending', 'resolved', 'closed']

interface TicketListProps {
  tickets: Ticket[]
  selectedId: number | null
  statusFilter: StatusFilter
  onStatusFilterChange: (filter: StatusFilter) => void
  search: string
  onSearchChange: (search: string) => void
  isLoading: boolean
  hasMore: boolean
  onLoadMore: () => void
  onNewTicket?: () => void
}

export default function TicketList(props: TicketListProps) {
  const { tickets, selectedId, statusFilter, search, isLoading } = props

  const visibleTickets = tickets.filter(
    (ticket) =>
      (statusFilter === 'all' || ticket.status === statusFilter) &&
      `${ticket.title} ${ticket.requester?.name ?? ''} #${ticket.id}`.toLowerCase().includes(search.toLowerCase()),
  )

  const countFor = (filter: StatusFilter) =>
    filter === 'all' ? tickets.length : tickets.filter((ticket) => ticket.status === filter).length

  return (
    <section className="flex h-full w-full flex-col border-r border-slate-200 bg-white md:w-80 lg:w-96">
      <header className="space-y-3 border-b border-slate-200 p-4">
        <div className="flex items-center justify-between">
          <h1 className="text-lg font-semibold text-slate-900">Conversas</h1>
          {props.onNewTicket && (
            <button
              onClick={props.onNewTicket}
              className="inline-grid size-8 place-items-center rounded-lg bg-brand-600 text-white hover:bg-brand-700"
              title="Novo ticket"
            >
              <Plus className="size-4" />
            </button>
          )}
        </div>
        <label className="relative block">
          <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
          <input
            value={search}
            onChange={(event) => props.onSearchChange(event.target.value)}
            placeholder="Buscar por assunto, contato ou #id"
            className="w-full rounded-lg bg-slate-100 py-2 pr-3 pl-9 text-sm placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none"
          />
        </label>
        <div className="-mx-1 flex flex-wrap gap-1">
          {filters.map((filter) => (
            <button
              key={filter}
              onClick={() => props.onStatusFilterChange(filter)}
              className={`shrink-0 rounded-full px-3 py-1 text-xs font-medium transition ${
                statusFilter === filter ? 'bg-brand-600 text-white' : 'text-slate-600 hover:bg-slate-100'
              }`}
            >
              {filter === 'all' ? 'Todas' : statusLabels[filter]}
              <span className="ml-1 opacity-70">{countFor(filter)}</span>
            </button>
          ))}
        </div>
      </header>

      <ul className="flex-1 divide-y divide-slate-100 overflow-y-auto">
        {visibleTickets.map((ticket) => (
          <li key={ticket.id}>
            <Link
              to={`/tickets/${ticket.id}`}
              className={`flex gap-3 border-l-[3px] px-4 py-3 transition ${
                ticket.id === selectedId ? 'border-brand-600 bg-brand-50' : 'border-transparent hover:bg-slate-50'
              }`}
            >
              <Avatar name={ticket.requester?.name ?? '?'} />
              <div className="min-w-0 flex-1">
                <div className="flex items-baseline justify-between gap-2">
                  <p className="truncate text-sm font-semibold text-slate-900">{ticket.requester?.name ?? 'Sem contato'}</p>
                  <span className="shrink-0 text-[11px] text-slate-400">{formatRelative(ticket.updated_at)}</span>
                </div>
                <p className="truncate text-sm text-slate-600">{ticket.title}</p>
                <div className="mt-1.5 flex items-center gap-2">
                  <StatusBadge status={ticket.status} />
                  <PriorityTag priority={ticket.priority} />
                  <span className="ml-auto text-[11px] text-slate-400">#{ticket.id}</span>
                </div>
              </div>
            </Link>
          </li>
        ))}

        {!isLoading && visibleTickets.length === 0 && (
          <li className="px-6 py-16 text-center text-sm text-slate-500">Nenhuma conversa por aqui.</li>
        )}
        {isLoading && <li className="px-6 py-6 text-center text-sm text-slate-400">Carregando…</li>}
        {props.hasMore && !isLoading && (
          <li className="p-3">
            <button onClick={props.onLoadMore} className="w-full rounded-lg py-2 text-sm text-brand-600 hover:bg-brand-50">
              Carregar mais
            </button>
          </li>
        )}
      </ul>
    </section>
  )
}
