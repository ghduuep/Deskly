export type UserRole = 'customer' | 'agent' | 'admin'
export type TicketStatus = 'open' | 'pending' | 'resolved' | 'closed'
export type TicketPriority = 'low' | 'normal' | 'high' | 'urgent'
export type MessageDirection = 'inbound' | 'outbound'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
}

export interface Contact {
  id: number
  name: string
}

export interface Ticket {
  id: number
  title: string
  status: TicketStatus
  priority: TicketPriority
  requester?: Contact | null
  assignee?: User | null
  company?: { id: number; name: string } | null
  tags?: { id: number; name: string }[]
  created_at: string
  updated_at: string
}

export interface Message {
  id: number
  content: string
  is_internal: boolean
  direction: MessageDirection | null
  author?: Contact | null
  created_at: string
}

export interface Paginated<T> {
  data: T[]
  links: { next: string | null }
  meta: { current_page: number; last_page: number; total: number }
}
