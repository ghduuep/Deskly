import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import Reveal from '../components/Reveal'
import { BookOpen, Check, Flag, Inbox, Mail, MessageCircle, MessagesSquare, ShieldCheck, StickyNote, Users } from 'lucide-react'
import { Avatar, StatusBadge } from '../components/ui'
import type { TicketStatus } from '../types'

const repositoryUrl = 'https://github.com/ghduuep/Deskly'

const channels = [
  { name: 'WhatsApp', color: 'bg-emerald-500', icon: MessageCircle },
  { name: 'E-mail', color: 'bg-sky-500', icon: Mail },
  { name: 'Chat no site', color: 'bg-brand-600', icon: MessagesSquare },
]

const features = [
  { icon: Inbox, title: 'Caixa de entrada única', text: 'Mensagens de todos os canais viram tickets em uma só fila, com busca e filtros por status.' },
  { icon: StickyNote, title: 'Notas internas', text: 'Combine o próximo passo com o time dentro do ticket, sem o cliente ver.' },
  { icon: Flag, title: 'Prioridades', text: 'Classifique em baixa, normal, alta ou urgente e atenda primeiro o que importa.' },
  { icon: Users, title: 'Times e atribuição', text: 'Organize agentes em grupos e atribua cada conversa a quem vai resolver.' },
  { icon: BookOpen, title: 'Base de conhecimento', text: 'Artigos por categoria para o cliente resolver sozinho. Em construção.' },
  { icon: ShieldCheck, title: 'Seguro por padrão', text: 'Permissões por papel, HTML sanitizado e sessão protegida com Laravel Sanctum.' },
]

const steps = [
  { title: 'Conecte seus canais', text: 'WhatsApp, e-mail e o chat do seu site chegam no mesmo lugar.' },
  { title: 'Organize em tickets', text: 'Cada conversa vira um ticket com contato, prioridade e responsável.' },
  { title: 'Resolva em equipe', text: 'Responda, deixe notas internas e acompanhe até o ticket ser fechado.' },
]

const lifecycle: { status: TicketStatus; text: string }[] = [
  { status: 'open', text: 'O cliente mandou mensagem. O ticket entra na fila.' },
  { status: 'pending', text: 'O agente respondeu. Aguardando o retorno do cliente.' },
  { status: 'resolved', text: 'Problema resolvido. Se o cliente responder, o ticket reabre.' },
  { status: 'closed', text: 'Ticket encerrado. Pode ser reaberto se precisar.' },
]

/** Runs a looping sequence of steps, each with its own duration in ms. */
function useLoopingStep(durations: number[]): number {
  const [step, setStep] = useState(0)

  useEffect(() => {
    const timeout = setTimeout(() => setStep((current) => (current + 1) % durations.length), durations[step])

    return () => clearTimeout(timeout)
  }, [step, durations])

  return step
}

export default function LandingPage() {
  return (
    <div className="min-h-full bg-white">
      <Header />

      <main>
        <section className="relative overflow-hidden">
          <div className="absolute inset-x-0 top-0 h-[560px] bg-gradient-to-b from-brand-50 to-white" />
          <div className="absolute top-10 -left-24 size-80 animate-blob rounded-full bg-brand-200/50 blur-3xl" />
          <div className="absolute top-40 right-0 size-96 animate-blob rounded-full bg-sky-200/40 blur-3xl [animation-delay:-6s]" />

          <div className="relative mx-auto grid max-w-6xl items-center gap-16 px-4 pt-14 pb-24 sm:px-6 lg:grid-cols-2 lg:pt-20">
            <div>
              <span className="inline-flex animate-pop-in items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-brand-700 ring-1 ring-brand-200">
                <span className="relative flex size-1.5">
                  <span className="absolute inline-flex size-full animate-ping rounded-full bg-brand-500 opacity-75" />
                  <span className="relative inline-flex size-1.5 rounded-full bg-brand-600" />
                </span>
                Open source · Laravel + React
              </span>
              <h1 className="mt-5 animate-pop-in text-4xl leading-[1.1] font-semibold tracking-tight text-slate-900 [animation-delay:100ms] sm:text-5xl">
                Atendimento omnichannel <RotatingWord />
              </h1>
              <p className="mt-5 max-w-lg animate-pop-in text-lg text-slate-600 [animation-delay:200ms]">
                O Deskly junta WhatsApp, e-mail e chat em uma só caixa de entrada para o seu time responder mais rápido e não perder nenhuma conversa.
              </p>
              <div className="mt-8 flex animate-pop-in flex-wrap gap-3 [animation-delay:300ms]">
                <Link to="/login?cadastro=1" className="rounded-lg bg-brand-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:bg-brand-700">
                  Criar conta
                </Link>
                <Link to="/login" className="rounded-lg bg-white px-5 py-3 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-50">
                  Ver demonstração
                </Link>
              </div>
              <p className="mt-4 text-xs text-slate-500">Contas de demonstração prontas para testar, sem instalar nada.</p>
            </div>

            <ProductPreview />
          </div>
        </section>

        <section className="border-y border-slate-100 bg-surface">
          <div className="mx-auto flex max-w-6xl flex-col items-center gap-6 px-4 py-10 sm:flex-row sm:justify-between sm:px-6">
            <p className="text-sm font-medium text-slate-500">Todos os canais em um só lugar</p>
            <div className="flex flex-wrap justify-center gap-6">
              {channels.map((channel, index) => (
                <span key={channel.name} className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                  <span className={`grid size-8 animate-float place-items-center rounded-lg text-white shadow-md ${channel.color}`} style={{ animationDelay: `${index * -2}s` }}>
                    <channel.icon className="size-4" />
                  </span>
                  {channel.name}
                </span>
              ))}
            </div>
          </div>
        </section>

        <section id="recursos" className="mx-auto max-w-6xl scroll-mt-20 px-4 py-24 sm:px-6">
          <Reveal>
            <SectionHeading eyebrow="Recursos" title="Tudo que o seu time precisa para atender bem" />
          </Reveal>
          <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {features.map((feature, index) => (
              <Reveal key={feature.title} delay={(index % 3) * 100}>
                <article className="group h-full rounded-2xl border border-slate-100 p-6 transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-100/60">
                  <span className="grid size-11 place-items-center rounded-xl bg-brand-50 text-brand-600 transition duration-300 group-hover:scale-110 group-hover:rotate-[-6deg] group-hover:bg-brand-600 group-hover:text-white">
                    <feature.icon className="size-5" />
                  </span>
                  <h3 className="mt-5 font-semibold text-slate-900">{feature.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-slate-600">{feature.text}</p>
                </article>
              </Reveal>
            ))}
          </div>
        </section>

        <section id="como-funciona" className="scroll-mt-20 bg-surface">
          <div className="mx-auto max-w-6xl px-4 py-24 sm:px-6">
            <Reveal>
              <SectionHeading eyebrow="Como funciona" title="Da primeira mensagem ao ticket fechado" />
            </Reveal>
            <div className="relative mt-14 grid gap-8 md:grid-cols-3">
              <div className="absolute top-5 right-[16%] left-[16%] hidden h-px bg-gradient-to-r from-brand-200 via-brand-500 to-brand-200 md:block" />
              {steps.map((step, index) => (
                <Reveal key={step.title} delay={index * 150}>
                  <div className="relative text-center">
                    <span className="relative mx-auto grid size-10 place-items-center rounded-full bg-brand-600 text-sm font-semibold text-white ring-8 ring-surface">{index + 1}</span>
                    <h3 className="mt-4 font-semibold text-slate-900">{step.title}</h3>
                    <p className="mt-2 text-sm text-slate-600">{step.text}</p>
                  </div>
                </Reveal>
              ))}
            </div>

            <Reveal>
              <TicketLifecycle />
            </Reveal>
          </div>
        </section>

        <section id="open-source" className="scroll-mt-20 px-4 py-24 sm:px-6">
          <Reveal>
            <div className="relative mx-auto max-w-6xl overflow-hidden rounded-3xl bg-brand-900 px-6 py-14 text-center sm:px-12">
              <div className="absolute -top-24 -left-24 size-72 animate-blob rounded-full bg-brand-600/40 blur-3xl" />
              <div className="absolute -right-24 -bottom-24 size-72 animate-blob rounded-full bg-brand-500/30 blur-3xl [animation-delay:-8s]" />
              <div className="relative">
                <h2 className="text-3xl font-semibold text-white sm:text-4xl">Feito em código aberto</h2>
                <p className="mx-auto mt-4 max-w-xl text-brand-200">
                  O Deskly é open source. Leia o código, rode na sua máquina e ajude a construir as próximas funcionalidades.
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                  <a href={repositoryUrl} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-brand-900 transition hover:-translate-y-0.5 hover:bg-brand-50">
                    <GitHubIcon /> Ver no GitHub
                  </a>
                  <Link to="/login" className="rounded-lg px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:-translate-y-0.5 hover:bg-white/10">
                    Testar a demonstração
                  </Link>
                </div>
              </div>
            </div>
          </Reveal>
        </section>
      </main>

      <footer className="border-t border-slate-100">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:px-6">
          <span className="flex items-center gap-2 font-semibold text-slate-700">
            <img src="/favicon.svg" alt="" className="size-6" /> Deskly
          </span>
          <span>Licença MIT · Projeto da comunidade</span>
          <a href={repositoryUrl} target="_blank" rel="noreferrer" className="hover:text-slate-700">GitHub</a>
        </div>
      </footer>
    </div>
  )
}

function Header() {
  return (
    <header className="sticky top-0 z-30 border-b border-slate-100 bg-white/80 backdrop-blur">
      <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <Link to="/" className="flex items-center gap-2 font-semibold text-slate-900">
          <img src="/favicon.svg" alt="" className="size-8" /> Deskly
        </Link>
        <nav className="hidden gap-8 text-sm text-slate-600 md:flex">
          <a href="#recursos" className="hover:text-slate-900">Recursos</a>
          <a href="#como-funciona" className="hover:text-slate-900">Como funciona</a>
          <a href="#open-source" className="hover:text-slate-900">Open source</a>
        </nav>
        <div className="flex items-center gap-2">
          <Link to="/login" className="rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Entrar</Link>
          <Link to="/login?cadastro=1" className="hidden rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 sm:inline-block">
            Criar conta
          </Link>
        </div>
      </div>
    </header>
  )
}

const rotatingWords = ['sem complicação.', 'em um só lugar.', 'feito em equipe.']
const rotatingWordDurations = rotatingWords.map(() => 2600)

function RotatingWord() {
  const index = useLoopingStep(rotatingWordDurations)

  return (
    <span className="block text-brand-600">
      <span key={index} className="inline-block animate-pop-in">
        {rotatingWords[index]}
      </span>
    </span>
  )
}

function SectionHeading({ eyebrow, title }: { eyebrow: string; title: string }) {
  return (
    <div className="mx-auto max-w-2xl text-center">
      <p className="text-sm font-semibold text-brand-600">{eyebrow}</p>
      <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">{title}</h2>
    </div>
  )
}

/**
 * Animated, illustrative mini inbox; not wired to the API. The script follows
 * the real domain rule: an agent reply moves an open ticket to pending.
 */
const chatScript = {
  customerTyping: 0,
  customerMessage: 1,
  agentTyping: 2,
  agentMessage: 3,
  internalNote: 4,
  hold: 5,
}
const chatDurations = [1400, 1600, 1500, 1500, 1400, 3000]

function ProductPreview() {
  const step = useLoopingStep(chatDurations)
  const hasAgentReplied = step >= chatScript.agentMessage

  const conversations = [
    { name: 'Marina Souza', title: 'Pedido chegou com defeito', status: hasAgentReplied ? 'pending' : 'open', channel: channels[0] },
    { name: 'João Pereira', title: 'Dúvida sobre o plano anual', status: 'pending', channel: channels[1] },
    { name: 'Lia Costa', title: 'Como exportar relatórios?', status: 'resolved', channel: channels[2] },
  ] as const

  return (
    <div className="relative animate-pop-in [animation-delay:250ms]" aria-hidden>
      <div className="absolute -inset-6 rounded-[2rem] bg-brand-200/40 blur-2xl" />

      <div className="relative overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 shadow-brand-900/10 ring-slate-200">
        <div className="flex items-center gap-1.5 border-b border-slate-100 bg-slate-50 px-4 py-2.5">
          <span className="size-2.5 rounded-full bg-rose-300" />
          <span className="size-2.5 rounded-full bg-amber-300" />
          <span className="size-2.5 rounded-full bg-emerald-300" />
          <span className="ml-3 text-[11px] text-slate-400">Deskly · Conversas</span>
        </div>
        <div className="grid h-72 grid-cols-5 text-left">
          <ul className="col-span-2 divide-y divide-slate-100 border-r border-slate-100">
            {conversations.map((conversation, index) => (
              <li key={conversation.name} className={`flex gap-2 p-3 ${index === 0 ? 'bg-brand-50' : ''}`}>
                <div className="relative flex h-fit shrink-0">
                  <Avatar name={conversation.name} size="sm" />
                  <span className={`absolute -right-0.5 -bottom-0.5 size-2.5 rounded-full ring-2 ring-white ${conversation.channel.color}`} />
                </div>
                <div className="min-w-0">
                  <p className="truncate text-xs font-semibold text-slate-900">{conversation.name}</p>
                  <p className="truncate text-[11px] text-slate-500">{conversation.title}</p>
                  <div key={conversation.status} className="mt-1 animate-pop-in">
                    <StatusBadge status={conversation.status} />
                  </div>
                </div>
              </li>
            ))}
          </ul>
          <div className="col-span-3 flex flex-col gap-3 bg-surface p-4">
            {step === chatScript.customerTyping && <TypingBubble />}
            {step >= chatScript.customerMessage && (
              <div className="max-w-[85%] animate-pop-in rounded-2xl rounded-bl-sm bg-white px-3 py-2 text-xs text-slate-700 shadow-sm">
                Oi! Meu pedido chegou com a tampa quebrada 😕
              </div>
            )}
            {step === chatScript.agentTyping && <TypingBubble isOutbound />}
            {hasAgentReplied && (
              <div className="ml-auto max-w-[85%] animate-pop-in rounded-2xl rounded-br-sm bg-brand-600 px-3 py-2 text-xs text-white shadow-sm">
                Poxa, sinto muito! Já estou enviando uma nova pra você.
              </div>
            )}
            {step >= chatScript.internalNote && (
              <div className="animate-pop-in rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] text-amber-800">
                <span className="font-semibold">Nota interna:</span> liberar troca sem custo.
              </div>
            )}
            <div className="mt-auto rounded-lg bg-white px-3 py-2 text-[11px] text-slate-400 ring-1 ring-slate-200">Escreva uma mensagem…</div>
          </div>
        </div>
      </div>

      <div className="absolute -top-6 -left-4 hidden animate-float items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-xl ring-1 ring-slate-100 sm:flex">
        <span className={`grid size-7 place-items-center rounded-lg text-white ${channels[0].color}`}>
          <MessageCircle className="size-3.5" />
        </span>
        <div>
          <p className="text-[11px] font-semibold text-slate-900">Nova mensagem</p>
          <p className="text-[10px] text-slate-500">via WhatsApp</p>
        </div>
      </div>

      <div className="absolute -right-4 -bottom-6 hidden animate-float-slow items-center gap-2 rounded-xl bg-white px-3 py-2 shadow-xl ring-1 ring-slate-100 [animation-delay:-3s] sm:flex">
        <span className="grid size-7 place-items-center rounded-full bg-emerald-100 text-emerald-600">
          <Check className="size-4" />
        </span>
        <div>
          <p className="text-[11px] font-semibold text-slate-900">Ticket resolvido</p>
          <p className="text-[10px] text-slate-500">Lia Costa · E-mail</p>
        </div>
      </div>
    </div>
  )
}

function TypingBubble({ isOutbound = false }: { isOutbound?: boolean }) {
  return (
    <div className={`flex w-fit animate-pop-in gap-1 rounded-2xl px-3 py-2.5 shadow-sm ${isOutbound ? 'ml-auto rounded-br-sm bg-brand-600' : 'rounded-bl-sm bg-white'}`}>
      {[0, 150, 300].map((delay) => (
        <span key={delay} className={`size-1.5 animate-typing rounded-full ${isOutbound ? 'bg-white' : 'bg-slate-400'}`} style={{ animationDelay: `${delay}ms` }} />
      ))}
    </div>
  )
}

const lifecycleDurations = lifecycle.map(() => 2200)

function TicketLifecycle() {
  const activeIndex = useLoopingStep(lifecycleDurations)

  return (
    <div className="mt-16 rounded-2xl bg-white p-6 ring-1 ring-slate-100 sm:p-8">
      <p className="text-sm font-semibold text-slate-900">Ciclo de vida de um ticket</p>
      <p className="mt-1 text-sm text-slate-500">Regras claras de status, aplicadas pelo próprio sistema.</p>

      <div className="relative mt-8">
        <div className="absolute top-1/2 right-0 left-0 hidden h-1 -translate-y-1/2 rounded-full bg-slate-100 sm:block" />
        <div
          className="absolute top-1/2 left-0 hidden h-1 -translate-y-1/2 rounded-full bg-brand-500 transition-all duration-700 sm:block"
          style={{ width: `${(activeIndex / (lifecycle.length - 1)) * 100}%` }}
        />
        <div className="relative flex flex-wrap justify-between gap-3">
          {lifecycle.map((item, index) => (
            <span
              key={item.status}
              className={`rounded-full bg-white p-1 transition duration-500 ${index === activeIndex ? 'scale-125 shadow-lg shadow-brand-200' : index < activeIndex ? '' : 'opacity-50'}`}
            >
              <StatusBadge status={item.status} />
            </span>
          ))}
        </div>
      </div>

      <p key={activeIndex} className="mt-6 animate-pop-in text-sm text-slate-600">
        {lifecycle[activeIndex].text}
      </p>
    </div>
  )
}

function GitHubIcon() {
  return (
    <svg viewBox="0 0 24 24" className="size-4" fill="currentColor" aria-hidden>
      <path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.45-1.16-1.11-1.47-1.11-1.47-.9-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.9 1.52 2.34 1.08 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02a9.6 9.6 0 0 1 5 0c1.91-1.3 2.75-1.02 2.75-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.85v2.75c0 .27.18.58.69.48A10 10 0 0 0 12 2" />
    </svg>
  )
}
