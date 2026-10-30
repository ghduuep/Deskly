import { useEffect, useRef, useState, type KeyboardEvent } from 'react'
import { Link } from 'react-router-dom'
import { api, ApiError } from '../lib/api'
import { formatDateTime } from '../lib/labels'
import type { Message, Paginated, Ticket, User } from '../types'
import { ChevronLeft, Info, Lock, MessageSquare, RotateCcw, SendHorizontal, Check, StickyNote } from 'lucide-react'
import { Avatar, Button, StatusBadge } from './ui'

interface ConversationProps {
  ticket: Ticket
  user: User
  onTicketChange: () => void
  onToggleDetails: () => void
}

type ComposerMode = 'reply' | 'note'

async function fetchAllMessages(ticketId: number): Promise<Message[]> {
  const messages: Message[] = []
  let page = 1
  let lastPage = 1

  do {
    const response = await api<Paginated<Message>>(`/api/tickets/${ticketId}/messages?page=${page}`)
    messages.push(...response.data)
    lastPage = response.meta.last_page
    page++
  } while (page <= lastPage)

  return messages
}

export default function Conversation({ ticket, user, onTicketChange, onToggleDetails }: ConversationProps) {
  const isAgent = user.role !== 'customer'
  const [messages, setMessages] = useState<Message[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [draft, setDraft] = useState('')
  const [mode, setMode] = useState<ComposerMode>('reply')
  const [error, setError] = useState<string | null>(null)
  const [isBusy, setIsBusy] = useState(false)
  const bottomRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    let isCurrent = true
    setIsLoading(true)
    setError(null)

    fetchAllMessages(ticket.id)
      .then((loaded) => isCurrent && setMessages(loaded))
      .catch(() => isCurrent && setError('Não foi possível carregar as mensagens.'))
      .finally(() => isCurrent && setIsLoading(false))

    return () => {
      isCurrent = false
    }
  }, [ticket.id])

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ block: 'end' })
  }, [messages])

  const run = async (action: () => Promise<void>) => {
    setIsBusy(true)
    setError(null)

    try {
      await action()
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Algo deu errado.')
    } finally {
      setIsBusy(false)
    }
  }

  const send = () =>
    run(async () => {
      const content = draft.trim()
      if (!content) return

      const path = mode === 'note' ? 'notes' : 'messages'
      const response = await api<{ data: Message }>(`/api/tickets/${ticket.id}/${path}`, {
        method: 'POST',
        body: { content },
      })

      setMessages((current) => [...current, response.data])
      setDraft('')
      onTicketChange()
    })

  const changeStatus = (action: 'resolve' | 'close' | 'reopen') =>
    run(async () => {
      await api(`/api/tickets/${ticket.id}/${action}`, { method: 'POST' })
      onTicketChange()
    })

  const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault()
      send()
    }
  }

  const isClosed = ticket.status === 'closed'

  return (
    <section className="flex h-full min-w-0 flex-1 flex-col bg-surface">
      <header className="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
        <Link to="/" className="rounded-lg p-1 text-slate-500 hover:bg-slate-100 md:hidden" aria-label="Voltar">
          <ChevronLeft className="size-5" />
        </Link>
        <Avatar name={ticket.requester?.name ?? '?'} />
        <div className="min-w-0 flex-1">
          <div className="flex items-center gap-2">
            <h2 className="truncate font-semibold text-slate-900">{ticket.title}</h2>
            <StatusBadge status={ticket.status} />
          </div>
          <p className="truncate text-xs text-slate-500">
            {ticket.requester?.name} · #{ticket.id}
          </p>
        </div>
        <div className="flex items-center gap-2">
          {isAgent && (ticket.status === 'open' || ticket.status === 'pending') && (
            <Button onClick={() => changeStatus('resolve')} disabled={isBusy}>
              <Check className="size-4" /> Resolver
            </Button>
          )}
          {isAgent && ticket.status === 'resolved' && (
            <Button variant="secondary" onClick={() => changeStatus('close')} disabled={isBusy}>
              <Lock className="size-4" /> Fechar
            </Button>
          )}
          {isAgent && (ticket.status === 'resolved' || isClosed) && (
            <Button variant="secondary" onClick={() => changeStatus('reopen')} disabled={isBusy}>
              <RotateCcw className="size-4" /> Reabrir
            </Button>
          )}
          <Button variant="ghost" onClick={onToggleDetails} className="px-2 xl:hidden" aria-label="Detalhes">
            <Info className="size-5" />
          </Button>
        </div>
      </header>

      <div className="flex-1 overflow-y-auto px-4 py-6 sm:px-8">
        <div className="mx-auto max-w-3xl space-y-4">
          {isLoading && <p className="text-center text-sm text-slate-400">Carregando mensagens…</p>}
          {!isLoading && messages.length === 0 && (
            <p className="text-center text-sm text-slate-400">Nenhuma mensagem ainda. Comece a conversa.</p>
          )}
          {messages.map((message) => (
            <MessageBubble key={message.id} message={message} />
          ))}
          <div ref={bottomRef} />
        </div>
      </div>

      <footer className="border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
        {error && <p className="mb-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{error}</p>}
        {isClosed ? (
          <p className="py-3 text-center text-sm text-slate-500">Este ticket está fechado. Reabra para responder.</p>
        ) : (
          <div className={`rounded-xl ring-1 transition ${mode === 'note' ? 'bg-amber-50 ring-amber-200' : 'bg-white ring-slate-200'} focus-within:ring-2 focus-within:ring-brand-500`}>
            {isAgent && (
              <div className="flex gap-1 border-b border-slate-100 px-2 pt-2">
                {(['reply', 'note'] as const).map((option) => (
                  <button
                    key={option}
                    onClick={() => setMode(option)}
                    className={`inline-flex items-center gap-1.5 rounded-t-md px-3 py-1.5 text-xs font-medium ${
                      mode === option ? 'text-brand-700 shadow-[inset_0_-2px_0] shadow-brand-600' : 'text-slate-500 hover:text-slate-700'
                    }`}
                  >
                    {option === 'reply' ? <MessageSquare className="size-3.5" /> : <StickyNote className="size-3.5" />}
                    {option === 'reply' ? 'Responder' : 'Nota interna'}
                  </button>
                ))}
              </div>
            )}
            <textarea
              value={draft}
              onChange={(event) => setDraft(event.target.value)}
              onKeyDown={handleKeyDown}
              rows={3}
              placeholder={mode === 'note' ? 'Nota visível só para a equipe…' : 'Escreva uma mensagem… (Enter envia, Shift+Enter quebra linha)'}
              className="block w-full resize-none bg-transparent px-3 py-2 text-sm placeholder:text-slate-400 focus:outline-none"
            />
            <div className="flex justify-end px-2 pb-2">
              <Button onClick={send} disabled={isBusy || !draft.trim()}>
                <SendHorizontal className="size-4" /> {mode === 'note' ? 'Salvar nota' : 'Enviar'}
              </Button>
            </div>
          </div>
        )}
      </footer>
    </section>
  )
}

function MessageBubble({ message }: { message: Message }) {
  const authorName = message.author?.name ?? 'Desconhecido'

  if (message.is_internal) {
    return (
      <div className="mx-auto max-w-xl rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
        <p className="flex items-center gap-1.5 text-[11px] font-semibold tracking-wide text-amber-700 uppercase">
          <StickyNote className="size-3.5" /> Nota interna · {authorName}
        </p>
        <p className="mt-1 text-sm whitespace-pre-wrap text-amber-900">{message.content}</p>
        <p className="mt-1 text-right text-[11px] text-amber-600">{formatDateTime(message.created_at)}</p>
      </div>
    )
  }

  const isOutbound = message.direction === 'outbound'

  return (
    <div className={`flex items-end gap-2 ${isOutbound ? 'flex-row-reverse' : ''}`}>
      <Avatar name={authorName} size="sm" />
      <div className={`max-w-[75%] rounded-2xl px-4 py-2.5 shadow-sm ${isOutbound ? 'rounded-br-sm bg-brand-600 text-white' : 'rounded-bl-sm bg-white text-slate-800'}`}>
        <p className={`text-[11px] font-semibold ${isOutbound ? 'text-brand-100' : 'text-slate-500'}`}>{authorName}</p>
        <p className="text-sm whitespace-pre-wrap">{message.content}</p>
        <p className={`mt-1 text-right text-[11px] ${isOutbound ? 'text-brand-200' : 'text-slate-400'}`}>{formatDateTime(message.created_at)}</p>
      </div>
    </div>
  )
}
