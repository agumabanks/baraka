import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Login: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');

    try {
      await login(email, password);
      navigate('/dashboard');
    } catch (err: any) {
      setError(err.response?.data?.message || err.message || 'Login failed');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-mono-black text-mono-white">
      <div className="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-10 lg:px-12 xl:px-16">
        <header className="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.3em]">
          <a
            href="https://baraka.sanaa.ug"
            className="text-mono-gray-300 transition-colors hover:text-mono-white"
          >
            ← baraka.sanaa.ug
          </a>
          <nav className="flex items-center gap-5 text-mono-gray-400">
            <Link to="/register" className="transition-colors hover:text-mono-white">
              Request access
            </Link>
            <Link to="/login" className="transition-colors hover:text-mono-white">
              Sign in
            </Link>
          </nav>
        </header>

        <main className="mt-16 grid flex-1 gap-12 lg:grid-cols-[1.1fr_1fr] lg:items-center">
          <section className="space-y-10">
            <div className="space-y-6">
              <p className="text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-500">
                Baraka Control Centre
              </p>
              <h1 className="text-4xl font-semibold leading-tight text-mono-white sm:text-5xl">
                Precision logistics. Monochrome clarity.
              </h1>
              <p className="max-w-xl text-sm leading-relaxed text-mono-gray-400">
                Authenticate to orchestrate deliveries, finance, and support from a single,
                minimalist cockpit. Every decision here is measured, audit-ready, and intentionally
                monochrome.
              </p>
            </div>

            <div className="space-y-3">
              <div className="flex items-start gap-3 text-sm text-mono-gray-300">
                <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-white" aria-hidden="true" />
                End-to-end visibility across parcels, hubs, and riders.
              </div>
              <div className="flex items-start gap-3 text-sm text-mono-gray-300">
                <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-white" aria-hidden="true" />
                Financial controls with real-time SLA intelligence.
              </div>
              <div className="flex items-start gap-3 text-sm text-mono-gray-300">
                <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-white" aria-hidden="true" />
                Minimalist UI engineered for focused executive decisions.
              </div>
            </div>
          </section>

          <section className="rounded-3xl border border-mono-gray-200 bg-mono-white p-10 shadow-2xl">
            <div className="space-y-6">
              <div className="space-y-2">
                <h2 className="text-2xl font-semibold text-mono-black">Sign in</h2>
                <p className="text-sm text-mono-gray-600">
                  Use your Baraka admin credentials. Multi-factor prompts will follow company policy.
                </p>
              </div>

              {error && (
                <div className="rounded-2xl border border-mono-gray-300 bg-mono-gray-100 px-4 py-3 text-sm text-mono-black">
                  {error}
                </div>
              )}

              <form className="space-y-5" onSubmit={handleSubmit}>
                <div className="space-y-2">
                  <label htmlFor="email" className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Email
                  </label>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    required
                    className="w-full rounded-2xl border border-mono-gray-300 bg-mono-white px-4 py-3 text-mono-black placeholder-mono-gray-400 focus:outline-none focus:ring-2 focus:ring-mono-black"
                    placeholder="name@company.com"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                  />
                </div>

                <div className="space-y-2">
                  <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    <label htmlFor="password">Password</label>
                    <a
                      href="/password/reset"
                      className="text-mono-gray-400 transition-colors hover:text-mono-black"
                    >
                      Forgot?
                    </a>
                  </div>
                  <input
                    id="password"
                    name="password"
                    type="password"
                    autoComplete="current-password"
                    required
                    className="w-full rounded-2xl border border-mono-gray-300 bg-mono-white px-4 py-3 text-mono-black placeholder-mono-gray-400 focus:outline-none focus:ring-2 focus:ring-mono-black"
                    placeholder="Enter password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                  />
                </div>

                <button
                  type="submit"
                  disabled={isLoading}
                  className="w-full rounded-full border border-mono-black bg-mono-black py-3 text-sm font-semibold uppercase tracking-[0.35em] text-mono-white transition-colors hover:bg-mono-gray-900 focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 focus:ring-offset-mono-white disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {isLoading ? 'Signing in...' : 'Sign In'}
                </button>
              </form>

              <p className="text-xs text-mono-gray-500 uppercase tracking-[0.25em]">
                Need to register?{' '}
                <Link to="/register" className="text-mono-black hover:text-mono-gray-700">
                  Request an account
                </Link>
              </p>
            </div>
          </section>
        </main>
      </div>
    </div>
  );
};

export default Login;
