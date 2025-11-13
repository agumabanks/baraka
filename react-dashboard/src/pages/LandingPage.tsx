import { useNavigate } from 'react-router-dom'
import { ArrowRight, PackageSearch, Clock3, ShieldCheck, Users, Sparkles, Phone } from 'lucide-react'

const features = [
  {
    icon: PackageSearch,
    title: 'Real-time visibility',
    description: 'Track every shipment from pickup to delivery with live status updates and proactive alerts.',
  },
  {
    icon: Clock3,
    title: 'Ultra-fast fulfillment',
    description: 'Same-day dispatch across major hubs with intelligently optimized routing and dispatch automation.',
  },
  {
    icon: ShieldCheck,
    title: 'Enterprise-grade reliability',
    description: 'Redundant networks, SLA-backed delivery windows, and automated exception workflows keep customers informed.',
  },
]

const stats = [
  { label: 'Cities served daily', value: '120+' },
  { label: 'On-time deliveries', value: '99.2%' },
  { label: 'Active merchants', value: '4.8k' },
  { label: 'EDI transactions / hr', value: '35k+' },
]

const testimonials = [
  {
    quote:
      'Baraka transformed our same-day delivery promise. Customers can self-serve, track orders, and receive proactive notifications without calling support.',
    name: 'Sanyu Nabwire',
    role: 'Logistics Director, Kampala Electronics',
  },
  {
    quote:
      'Integrating our ERP was painless. The analytics dashboard helps us see network load in real time and keeps stakeholders aligned every morning.',
    name: 'Ahmed Mutesa',
    role: 'Head of Operations, EastAfrica Pharma',
  },
]

const steps = [
  {
    title: 'Create your merchant workspace',
    description: 'Configure branches, delivery zones, and preferred service options in minutes.',
  },
  {
    title: 'Connect your stack',
    description: 'Use REST, GraphQL, or EDI connectors to sync orders, invoices, and webhook alerts.',
  },
  {
    title: 'Delight every customer',
    description: 'Leverage predictive routing, exception automation, and branded notifications at scale.',
  },
]

const LandingPage = () => {
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-mono-white text-mono-gray-900">
      <header className="border-b border-mono-gray-200/70 bg-white/70 backdrop-blur">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
          <div className="flex items-center gap-3">
            <img src="/images/default/logo1.png" alt="Baraka" className="h-10 w-auto" />
            <div className="text-sm font-semibold uppercase tracking-[0.35em] text-mono-gray-600">Baraka Courier</div>
          </div>
          <div className="hidden items-center gap-3 md:flex">
            <button
              onClick={() => navigate('/login')}
              className="rounded-full border border-mono-gray-900 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-900 transition hover:bg-mono-gray-900 hover:text-white"
            >
              Sign In
            </button>
            <button
              onClick={() => navigate('/register')}
              className="inline-flex items-center gap-2 rounded-full bg-mono-black px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-white transition hover:bg-mono-gray-900"
            >
              Create Account
              <ArrowRight className="h-4 w-4" />
            </button>
          </div>
        </div>
      </header>

      <main>
        <section className="relative overflow-hidden border-b border-mono-gray-200 bg-gradient-to-b from-mono-gray-50 to-white">
          <div className="mx-auto grid max-w-7xl gap-12 px-6 py-20 lg:grid-cols-[1.3fr,1fr] lg:items-center">
            <div className="space-y-8">
              <span className="inline-flex items-center gap-2 rounded-full border border-mono-gray-300 px-4 py-1 text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-600">
                <Sparkles className="h-4 w-4" />
                Unified logistics operating system
              </span>
              <h1 className="text-4xl font-semibold leading-tight text-mono-black sm:text-5xl">
                Deliver faster, smarter, and with full customer confidence.
              </h1>
              <p className="max-w-xl text-lg text-mono-gray-600">
                Baraka centralizes every shipment workflow—from booking and warehouse handoffs to final proof-of-delivery—so your teams can focus on growth instead of firefighting.
              </p>
              <div className="flex flex-col gap-3 sm:flex-row">
                <button
                  onClick={() => navigate('/register')}
                  className="inline-flex items-center justify-center gap-2 rounded-full bg-mono-black px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-white transition hover:bg-mono-gray-900"
                >
                  Launch Merchant Portal
                  <ArrowRight className="h-5 w-5" />
                </button>
                <button
                  onClick={() => navigate('/login')}
                  className="inline-flex items-center justify-center gap-2 rounded-full border border-mono-gray-300 px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-mono-gray-700 transition hover:border-mono-gray-900 hover:text-mono-gray-900"
                >
                  Explore the dashboard
                </button>
              </div>
              <div className="grid gap-6 sm:grid-cols-2">
                {stats.map((stat) => (
                  <div key={stat.label} className="rounded-3xl border border-mono-gray-200 bg-white/80 px-6 py-5 shadow-sm">
                    <div className="text-3xl font-semibold text-mono-black">{stat.value}</div>
                    <div className="mt-2 text-sm uppercase tracking-[0.3em] text-mono-gray-500">{stat.label}</div>
                  </div>
                ))}
              </div>
            </div>
            <div className="relative">
              <div className="absolute -inset-6 rounded-[3rem] bg-gradient-to-tr from-mono-gray-100 via-white to-mono-gray-50 blur-3xl" />
              <div className="relative rounded-[2.5rem] border border-mono-gray-200 bg-white/90 p-6 shadow-xl backdrop-blur">
                <div className="rounded-3xl border border-mono-gray-200 bg-mono-gray-50 p-6">
                  <div className="text-xs uppercase tracking-[0.35em] text-mono-gray-500">Live fleet snapshot</div>
                  <div className="mt-5 space-y-4">
                    <div className="flex items-center justify-between rounded-2xl border border-mono-gray-200 bg-white px-4 py-3 shadow-sm">
                      <div>
                        <div className="text-sm font-semibold text-mono-gray-800">Pickup hubs</div>
                        <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Nairobi • Kampala • Kigali</div>
                      </div>
                      <div className="text-2xl font-semibold text-mono-black">34</div>
                    </div>
                    <div className="flex items-center justify-between rounded-2xl border border-mono-gray-200 bg-white px-4 py-3 shadow-sm">
                      <div>
                        <div className="text-sm font-semibold text-mono-gray-800">Same-day SLA</div>
                        <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Last updated 4 min ago</div>
                      </div>
                      <div className="text-2xl font-semibold text-emerald-600">96%</div>
                    </div>
                    <div className="flex items-center justify-between rounded-2xl border border-mono-gray-200 bg-white px-4 py-3 shadow-sm">
                      <div>
                        <div className="text-sm font-semibold text-mono-gray-800">Exception queue</div>
                        <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Auto-escalated with alerts</div>
                      </div>
                      <div className="text-2xl font-semibold text-rose-600">7</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section id="services" className="border-b border-mono-gray-200 bg-white">
          <div className="mx-auto max-w-7xl px-6 py-20">
            <div className="mx-auto max-w-3xl text-center">
              <h2 className="text-3xl font-semibold text-mono-black sm:text-4xl">Tools built for high-velocity logistics teams</h2>
              <p className="mt-4 text-lg text-mono-gray-600">
                Automate bookings, orchestrate warehouses, trigger webhooks, and deliver delightful customer experiences from one control center.
              </p>
            </div>
            <div className="mt-12 grid gap-8 lg:grid-cols-3">
              {features.map(({ icon: Icon, title, description }) => (
                <div key={title} className="rounded-[2rem] border border-mono-gray-200 bg-mono-gray-50/60 p-8 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                  <div className="flex h-12 w-12 items-center justify-center rounded-full border border-mono-gray-300 bg-white">
                    <Icon className="h-6 w-6 text-mono-gray-800" />
                  </div>
                  <h3 className="mt-6 text-xl font-semibold text-mono-black">{title}</h3>
                  <p className="mt-3 text-sm text-mono-gray-600">{description}</p>
                </div>
              ))}
            </div>
            <div className="mt-16 grid gap-6 rounded-[2.5rem] border border-mono-gray-200 bg-mono-gray-50/60 p-10 lg:grid-cols-[1.1fr,1fr]">
              <div className="space-y-4">
                <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">How it works</div>
                <h3 className="text-2xl font-semibold text-mono-black">Launch your network in three steps</h3>
                <p className="text-sm text-mono-gray-600">
                  Bring every branch, partner, and delivery mode onto a single platform with enterprise governance baked in from day one.
                </p>
                <div className="space-y-5">
                  {steps.map((step, index) => (
                    <div key={step.title} className="flex items-start gap-4">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full border border-mono-gray-900 text-sm font-semibold text-mono-black">
                        {index + 1}
                      </div>
                      <div>
                        <div className="text-sm font-semibold uppercase tracking-[0.3em] text-mono-gray-500">{step.title}</div>
                        <p className="mt-2 text-sm text-mono-gray-600">{step.description}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
              <div className="rounded-[2rem] border border-mono-gray-200 bg-white p-8 shadow-sm">
                <div className="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                  <Users className="h-5 w-5" />
                  Customer success spotlight
                </div>
                <p className="mt-6 text-lg text-mono-gray-700">
                  “With Baraka we unified 18 partner couriers into one SLA-backed workflow. Failed deliveries dropped 34% within the first month.”
                </p>
                <div className="mt-6 text-sm font-semibold text-mono-black">Grace Wanjiku</div>
                <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Head of Fulfilment, Juba Retail Collective</div>
                <button
                  onClick={() => navigate('/login')}
                  className="mt-8 inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-[0.3em] text-mono-gray-900 hover:underline"
                >
                  View realtime dashboard demo
                  <ArrowRight className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </section>

        <section id="testimonials" className="border-b border-mono-gray-200 bg-mono-gray-50">
          <div className="mx-auto max-w-7xl px-6 py-20">
            <div className="mx-auto max-w-3xl text-center">
              <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Loved by growth teams</div>
              <h2 className="mt-4 text-3xl font-semibold text-mono-black">Trusted by the fastest-moving brands in Africa</h2>
            </div>
            <div className="mt-12 grid gap-8 lg:grid-cols-2">
              {testimonials.map((testimonial) => (
                <div key={testimonial.name} className="h-full rounded-[2rem] border border-mono-gray-200 bg-white p-8 text-left shadow-sm">
                  <p className="text-lg text-mono-gray-700">{testimonial.quote}</p>
                  <div className="mt-6 text-sm font-semibold text-mono-black">{testimonial.name}</div>
                  <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{testimonial.role}</div>
                </div>
              ))}
            </div>
          </div>
        </section>

        <section id="contact" className="bg-mono-black">
          <div className="mx-auto grid max-w-7xl gap-8 px-6 py-16 lg:grid-cols-[1.1fr,1fr]">
            <div>
              <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-400">Ready to launch?</div>
              <h2 className="mt-4 text-3xl font-semibold text-white sm:text-4xl">Book a guided walkthrough with a Baraka specialist.</h2>
              <p className="mt-4 max-w-xl text-sm text-mono-gray-300">
                We will map your current operations, evaluate automation opportunities, and configure a pilot environment tailored to your network.
              </p>
              <div className="mt-8 flex flex-col gap-4 text-mono-gray-100 sm:flex-row">
                <button
                  onClick={() => navigate('/register')}
                  className="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-mono-black transition hover:bg-mono-gray-100"
                >
                  Schedule onboarding
                </button>
                <a
                  href="tel:+256700000000"
                  className="inline-flex items-center justify-center gap-2 rounded-full border border-mono-gray-400 px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-mono-gray-200 transition hover:border-white"
                >
                  <Phone className="h-4 w-4" />
                  Talk to sales
                </a>
              </div>
            </div>
            <div className="rounded-[2rem] border border-mono-gray-700 bg-mono-gray-900 p-8 text-mono-gray-200">
              <div className="text-sm font-semibold uppercase tracking-[0.3em] text-mono-gray-400">What to expect</div>
              <ul className="mt-6 space-y-4 text-sm text-mono-gray-200">
                <li className="flex items-start gap-3">
                  <ShieldCheck className="mt-1 h-5 w-5 text-emerald-400" />
                  <span>Compliance-first rollout with EDI, webhook, and API governance baked in.</span>
                </li>
                <li className="flex items-start gap-3">
                  <Clock3 className="mt-1 h-5 w-5 text-amber-300" />
                  <span>Launch pilot workflows in under 10 business days with dedicated enablement.</span>
                </li>
                <li className="flex items-start gap-3">
                  <Users className="mt-1 h-5 w-5 text-sky-300" />
                  <span>Upskill teams through the Baraka Academy and localized training modules.</span>
                </li>
              </ul>
            </div>
          </div>
        </section>
      </main>

      <footer className="border-t border-mono-gray-200 bg-white">
        <div className="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-10 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="text-sm font-semibold uppercase tracking-[0.35em] text-mono-gray-500">Baraka Courier</div>
            <p className="mt-2 text-sm text-mono-gray-500">Logistics intelligence for modern African commerce.</p>
          </div>
          <div className="flex items-center gap-4 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
            <button onClick={() => navigate('/login')} className="hover:text-mono-black">Sign In</button>
            <button onClick={() => navigate('/register')} className="hover:text-mono-black">Create Account</button>
            <a href="#services" className="hover:text-mono-black">Services</a>
            <a href="#contact" className="hover:text-mono-black">Contact</a>
          </div>
        </div>
        <div className="border-t border-mono-gray-200 bg-mono-gray-50 py-4 text-center text-xs uppercase tracking-[0.3em] text-mono-gray-500">
          © {new Date().getFullYear()} Baraka Courier. All rights reserved.
        </div>
      </footer>
    </div>
  )
}

export default LandingPage
