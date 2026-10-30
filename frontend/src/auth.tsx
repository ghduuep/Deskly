import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
  type ReactNode,
} from 'react'
import { api } from './lib/api'
import type { User } from './types'

interface AuthContextValue {
  user: User | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (data: {
    name: string
    email: string
    password: string
    password_confirmation: string
  }) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  const loadUser = useCallback(async () => {
    try {
      const response = await api<{ data: User }>('/api/me')
      setUser(response.data)
    } catch {
      setUser(null)
    }
  }, [])

  useEffect(() => {
    loadUser().finally(() => setIsLoading(false))
  }, [loadUser])

  const login = async (email: string, password: string) => {
    await api('/login', { method: 'POST', body: { email, password } })
    await loadUser()
  }

  const register: AuthContextValue['register'] = async (data) => {
    await api('/register', { method: 'POST', body: data })
    await loadUser()
  }

  const logout = async () => {
    await api('/logout', { method: 'POST' })
    setUser(null)
  }

  return (
    <AuthContext.Provider value={{ user, isLoading, login, register, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside AuthProvider')
  }

  return context
}
