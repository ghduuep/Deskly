import type { ButtonHTMLAttributes, ReactNode } from 'react'
import { initials, priorityDots, priorityLabels, statusLabels, statusStyles } from '../lib/labels'
import type { TicketPriority, TicketStatus } from '../types'

const avatarPalette = [
  'bg-brand-100 text-brand-700',
  'bg-emerald-100 text-emerald-700',
  'bg-amber-100 text-amber-700',
  'bg-rose-100 text-rose-700',
  'bg-sky-100 text-sky-700',
]

export function Avatar({ name, size = 'md' }: { name: string; size?: 'sm' | 'md' | 'lg' }) {
  const color = avatarPalette[name.length % avatarPalette.length]
  const dimensions = { sm: 'size-7 text-[11px]', md: 'size-9 text-xs', lg: 'size-14 text-base' }[size]

  return (
    <span className={`inline-grid shrink-0 place-items-center rounded-full font-semibold ${color} ${dimensions}`}>
      {initials(name)}
    </span>
  )
}

export function StatusBadge({ status }: { status: TicketStatus }) {
  return (
    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset ${statusStyles[status]}`}>
      {statusLabels[status]}
    </span>
  )
}

export function PriorityTag({ priority }: { priority: TicketPriority }) {
  return (
    <span className="inline-flex items-center gap-1.5 text-xs text-slate-500">
      <span className={`size-2 rounded-full ${priorityDots[priority]}`} />
      {priorityLabels[priority]}
    </span>
  )
}

type ButtonVariant = 'primary' | 'secondary' | 'ghost'

export function Button({
  variant = 'primary',
  className = '',
  children,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: ButtonVariant; children: ReactNode }) {
  const variants: Record<ButtonVariant, string> = {
    primary: 'bg-brand-600 text-white hover:bg-brand-700 shadow-sm',
    secondary: 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50',
    ghost: 'text-slate-600 hover:bg-slate-100',
  }

  return (
    <button
      className={`inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-50 ${variants[variant]} ${className}`}
      {...props}
    >
      {children}
    </button>
  )
}
