import { useState, type FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useAuth } from '../auth'
import { Button } from '../components/ui'
import { ApiError } from '../lib/api'

const demoAccounts = [
  { label: 'Agente', email: 'agent@deskly.test' },
  { label: 'Admin', email: 'admin@deskly.test' },
  { label: 'Cliente', email: 'customer@deskly.test' },
]

export default function LoginPage() {
  const { login, register } = useAuth()
  const [searchParams] = useSearchParams()
  const [mode, setMode] = useState<'login' | 'register'>(searchParams.has('cadastro') ? 'register' : 'login')
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const update = (field: keyof typeof form) => (value: string) => setForm((current) => ({ ...current, [field]: value }))

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)

    try {
      if (mode === 'login') {
        await login(form.email, form.password)
      } else {
        await register(form)
      }
    } catch (exception) {
      const apiError = exception instanceof ApiError ? exception : null
      setError(Object.values(apiError?.errors ?? {})[0]?.[0] ?? apiError?.message ?? 'Não foi possível entrar.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="grid min-h-full lg:grid-cols-2">
      <aside className="relative hidden overflow-hidden bg-brand-900 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div className="absolute -top-32 -right-32 size-96 rounded-full bg-brand-600/40 blur-3xl" />
        <div className="absolute -bottom-40 -left-20 size-96 rounded-full bg-brand-500/30 blur-3xl" />
        <Link to="/" className="relative flex items-center gap-2 text-lg font-semibold">
          <img src="/favicon.svg" alt="" className="size-8" /> Deskly
        </Link>
        <div className="relative max-w-md">
          <h1 className="text-4xl leading-tight font-semibold">Todos os seus atendimentos em uma só caixa de entrada.</h1>
          <p className="mt-4 text-brand-200">
            WhatsApp, e-mail e chat organizados por ticket, com notas internas, prioridades e times.
          </p>
        </div>
        <p className="relative text-xs text-brand-200">Projeto open source · Laravel + React</p>
      </aside>

      <main className="flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-sm">
          <h2 className="text-2xl font-semibold text-slate-900">{mode === 'login' ? 'Entrar' : 'Criar conta'}</h2>
          <p className="mt-1 text-sm text-slate-500">
            {mode === 'login' ? 'Acesse sua caixa de entrada.' : 'Abra chamados como cliente.'}
          </p>

          <form onSubmit={handleSubmit} className="mt-8 space-y-4">
            {mode === 'register' && <Field label="Nome" value={form.name} onChange={update('name')} />}
            <Field label="E-mail" type="email" value={form.email} onChange={update('email')} />
            <Field label="Senha" type="password" value={form.password} onChange={update('password')} />
            {mode === 'register' && (
              <Field label="Confirmar senha" type="password" value={form.password_confirmation} onChange={update('password_confirmation')} />
            )}

            {error && <p className="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700">{error}</p>}

            <Button type="submit" className="w-full py-2.5" disabled={isSubmitting}>
              {isSubmitting ? 'Aguarde…' : mode === 'login' ? 'Entrar' : 'Criar conta'}
            </Button>
          </form>

          <button
            type="button"
            onClick={() => setMode(mode === 'login' ? 'register' : 'login')}
            className="mt-4 w-full text-center text-sm text-brand-600 hover:underline"
          >
            {mode === 'login' ? 'Não tem conta? Cadastre-se' : 'Já tem conta? Entrar'}
          </button>

          {mode === 'login' && (
            <div className="mt-10 rounded-xl border border-dashed border-slate-300 p-4">
              <p className="text-xs font-medium tracking-wide text-slate-500 uppercase">Contas de demonstração</p>
              <p className="mt-1 text-xs text-slate-400">Senha: password</p>
              <div className="mt-3 flex flex-wrap gap-2">
                {demoAccounts.map((account) => (
                  <button
                    key={account.email}
                    type="button"
                    onClick={() => setForm((current) => ({ ...current, email: account.email, password: 'password' }))}
                    className="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200 hover:ring-brand-500"
                  >
                    {account.label}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </main>
    </div>
  )
}

function Field({ label, type = 'text', value, onChange }: { label: string; type?: string; value: string; onChange: (value: string) => void }) {
  return (
    <label className="block">
      <span className="text-sm font-medium text-slate-700">{label}</span>
      <input
        type={type}
        value={value}
        required
        onChange={(event) => onChange(event.target.value)}
        className="mt-1.5 block w-full rounded-lg border-0 bg-white px-3 py-2.5 text-sm ring-1 ring-slate-200 ring-inset placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500 focus:outline-none"
      />
    </label>
  )
}
