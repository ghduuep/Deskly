import type { TicketPriority, TicketStatus, UserRole } from '../types'

export const statusLabels: Record<TicketStatus, string> = {
  open: 'Aberto',
  pending: 'Pendente',
  resolved: 'Resolvido',
  closed: 'Fechado',
}

export const statusStyles: Record<TicketStatus, string> = {
  open: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  pending: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  resolved: 'bg-sky-50 text-sky-700 ring-sky-600/20',
  closed: 'bg-slate-100 text-slate-600 ring-slate-500/20',
}

export const priorityLabels: Record<TicketPriority, string> = {
  low: 'Baixa',
  normal: 'Normal',
  high: 'Alta',
  urgent: 'Urgente',
}

export const priorityDots: Record<TicketPriority, string> = {
  low: 'bg-slate-300',
  normal: 'bg-sky-400',
  high: 'bg-orange-400',
  urgent: 'bg-rose-500',
}

export const roleLabels: Record<UserRole, string> = {
  customer: 'Cliente',
  agent: 'Agente',
  admin: 'Administrador',
}

export function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('')
}

export function formatRelative(iso: string): string {
  const diffInMinutes = Math.round((Date.now() - new Date(iso).getTime()) / 60000)

  if (diffInMinutes < 1) return 'agora'
  if (diffInMinutes < 60) return `${diffInMinutes} min`
  if (diffInMinutes < 1440) return `${Math.round(diffInMinutes / 60)} h`

  return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' })
}

export function formatDateTime(iso: string): string {
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}
