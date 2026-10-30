import { useCallback, useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { useAuth } from '../auth'
import Conversation from '../components/Conversation'
import NewTicketDialog from '../components/NewTicketDialog'
import TicketDetails from '../components/TicketDetails'
import TicketList, { type StatusFilter } from '../components/TicketList'
import { BookOpen, ChartColumn, Inbox, LogOut, MessagesSquare, Users, type LucideIcon } from 'lucide-react'
import { Avatar } from '../components/ui'
import { api } from '../lib/api'
import { roleLabels } from '../lib/labels'
import type { Paginated, Ticket } from '../types'

export default function InboxPage() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const { ticketId } = useParams()
  const selectedId = ticketId ? Number(ticketId) : null

  const [tickets, setTickets] = useState<Ticket[]>([])
  const [lastPage, setLastPage] = useState(1)
  const [page, setPage] = useState(1)
  const [isLoadingList, setIsLoadingList] = useState(true)
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all')
  const [search, setSearch] = useState('')
  const [selectedTicket, setSelectedTicket] = useState<Ticket | null>(null)
  const [isDetailsOpen, setIsDetailsOpen] = useState(false)
  const [isCreating, setIsCreating] = useState(false)

  const loadTickets = useCallback(async (upToPage: number) => {
    setIsLoadingList(true)

    try {
      const pages = await Promise.all(
        Array.from({ length: upToPage }, (_, index) => api<Paginated<Ticket>>(`/api/tickets?page=${index + 1}`)),
      )
      setTickets(pages.flatMap((response) => response.data))
      setLastPage(pages[0]?.meta.last_page ?? 1)
    } finally {
      setIsLoadingList(false)
    }
  }, [])

  const loadSelectedTicket = useCallback(async () => {
    if (selectedId === null) {
      setSelectedTicket(null)
      return
    }

    try {
      const response = await api<{ data: Ticket }>(`/api/tickets/${selectedId}`)
      setSelectedTicket(response.data)
    } catch {
      navigate('/', { replace: true })
    }
  }, [selectedId, navigate])

  useEffect(() => {
    loadTickets(page)
  }, [loadTickets, page])

  useEffect(() => {
    loadSelectedTicket()
  }, [loadSelectedTicket])

  const refresh = () => {
    loadSelectedTicket()
    loadTickets(page)
  }

  if (!user) return null

  return (
    <div className="flex h-full">
      <nav className="hidden w-16 shrink-0 flex-col items-center gap-2 bg-brand-900 py-4 sm:flex">
        <img src="/favicon.svg" alt="Deskly" className="mb-4 size-9" />
        <RailButton icon={Inbox} label="Conversas" isActive />
        <RailButton icon={Users} label="Contatos (em breve)" />
        <RailButton icon={BookOpen} label="Base de conhecimento (em breve)" />
        <RailButton icon={ChartColumn} label="Relatórios (em breve)" />
        <div className="mt-auto flex flex-col items-center gap-3">
          <div title={`${user.name} · ${roleLabels[user.role]}`}>
            <Avatar name={user.name} />
          </div>
          <RailButton icon={LogOut} label="Sair" onClick={logout} />
        </div>
      </nav>

      <div className={`${selectedId ? 'hidden md:flex' : 'flex'} h-full w-full md:w-auto`}>
        <TicketList
          tickets={tickets}
          selectedId={selectedId}
          statusFilter={statusFilter}
          onStatusFilterChange={setStatusFilter}
          search={search}
          onSearchChange={setSearch}
          isLoading={isLoadingList}
          hasMore={page < lastPage}
          onLoadMore={() => setPage((current) => current + 1)}
          onNewTicket={user.role === 'customer' ? () => setIsCreating(true) : undefined}
        />
      </div>

      {selectedTicket ? (
        <>
          <Conversation
            key={selectedTicket.id}
            ticket={selectedTicket}
            user={user}
            onTicketChange={refresh}
            onToggleDetails={() => setIsDetailsOpen((current) => !current)}
          />
          <div
            className={`${isDetailsOpen ? 'fixed inset-0 z-40 flex justify-end bg-slate-900/30' : 'hidden'} xl:static xl:flex xl:bg-transparent`}
            onClick={() => setIsDetailsOpen(false)}
          >
            <div className="h-full w-full sm:w-auto" onClick={(event) => event.stopPropagation()}>
              <TicketDetails
                ticket={selectedTicket}
                user={user}
                onTicketChange={refresh}
                onClose={() => setIsDetailsOpen(false)}
              />
            </div>
          </div>
        </>
      ) : (
        <div className="hidden flex-1 flex-col items-center justify-center gap-3 text-center md:flex">
          <span className="grid size-16 place-items-center rounded-2xl bg-brand-100 text-brand-600">
            <MessagesSquare className="size-8" />
          </span>
          <p className="font-medium text-slate-700">Selecione uma conversa</p>
          <p className="max-w-xs text-sm text-slate-500">
            Olá, {user.name.split(' ')[0]}! Escolha um ticket na lista para ver o histórico e responder.
          </p>
        </div>
      )}

      {isCreating && (
        <NewTicketDialog
          onClose={() => setIsCreating(false)}
          onCreated={(ticket) => {
            setIsCreating(false)
            loadTickets(page)
            navigate(`/tickets/${ticket.id}`)
          }}
        />
      )}
    </div>
  )
}

function RailButton({ icon: IconComponent, label, isActive = false, onClick }: { icon: LucideIcon; label: string; isActive?: boolean; onClick?: () => void }) {
  return (
    <button
      title={label}
      aria-label={label}
      onClick={onClick}
      className={`grid size-10 place-items-center rounded-xl transition ${
        isActive ? 'bg-brand-600 text-white' : 'text-brand-200 hover:bg-white/10 hover:text-white'
      }`}
    >
      <IconComponent className="size-5" />
    </button>
  )
}
