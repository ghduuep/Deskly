import { Navigate, Route, Routes } from 'react-router-dom'
import { useAuth } from './auth'
import InboxPage from './pages/InboxPage'
import LandingPage from './pages/LandingPage'
import LoginPage from './pages/LoginPage'

export default function App() {
  const { user, isLoading } = useAuth()

  if (isLoading) {
    return (
      <div className="grid h-full place-items-center text-sm text-slate-500">
        Carregando…
      </div>
    )
  }

  return (
    <Routes>
      <Route
        path="/login"
        element={user ? <Navigate to="/" replace /> : <LoginPage />}
      />
      <Route path="/" element={user ? <InboxPage /> : <LandingPage />} />
      <Route
        path="/tickets/:ticketId"
        element={user ? <InboxPage /> : <Navigate to="/login" replace />}
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}
