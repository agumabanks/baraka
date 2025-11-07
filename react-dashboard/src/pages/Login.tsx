import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Login: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [capsLockOn, setCapsLockOn] = useState(false);

  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (password.length < 6) {
      setError('Password must be at least 6 characters.');
      return;
    }

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

  const handlePasswordKey = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (typeof event.getModifierState === 'function') {
      setCapsLockOn(event.getModifierState('CapsLock'));
    }
  };

  return (
    <div className="relative min-h-screen overflow-hidden bg-mono-black text-mono-white">
      <div className="pointer-events-none absolute inset-0 -z-10">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.18),transparent_55%)]" />
        <div className="absolute left-1/2 top-1/2 h-[28rem] w-[28rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(circle,rgba(255,255,255,0.12),transparent_60%)] blur-3xl" />
      </div>

      <div className="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-10 lg:px-12 xl:px-16">
        <header className="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.3em]">
          <a href="https://baraka.sanaa.ug" className="text-mono-gray-400 transition-colors hover:text-mono-white">
            ← baraka.sanaa.ug
          </a>
          <nav className="flex items-center gap-6 text-mono-gray-400">
            <Link to="/register" className="transition-colors hover:text-mono-white">
              Request access
            </Link>
            <Link to="/login" className="transition-colors hover:text-mono-white">
              Sign in
            </Link>
          </nav>
        </header>

        <main className="relative mt-20 flex flex-1 flex-col justify-center">
          <div className="grid gap-16 lg:grid-cols-[1.1fr_1fr] lg:items-center">
            <section className="space-y-10">
              <div className="space-y-6">
                <p className="text-xs font-semibold uppercase tracking-[0.4em] text-mono-gray-400">Baraka Control Centre</p>
                <h1 className="text-4xl font-semibold leading-tight text-mono-white sm:text-5xl">
                  Logistics with an obsession for detail.
                </h1>
                <p className="max-w-xl text-sm leading-relaxed text-mono-gray-300">
                  Sign in to a command console built for clarity, velocity, and deliberate decisions across every branch and courier.
                </p>
              </div>
              <blockquote className="max-w-xl border-l-2 border-mono-gray-600/60 pl-6 text-sm text-mono-gray-300">
                “Simplicity is the ultimate sophistication.” — Steve Jobs
              </blockquote>
            </section>

            <section className="relative overflow-hidden rounded-3xl border border-white/15 bg-white/5 p-10 shadow-[0_40px_120px_rgba(0,0,0,0.55)] backdrop-blur-xl">
              <div className="pointer-events-none absolute inset-x-8 -top-px h-px bg-gradient-to-r from-transparent via-white/70 to-transparent" />
              <div className="space-y-6">
                <div className="space-y-2">
                  <h2 className="text-2xl font-semibold text-mono-white">Sign in</h2>
                  <p className="text-sm text-mono-gray-300">
                    Use your Baraka admin credentials. Multi-factor prompts will appear as configured.
                  </p>
                </div>

                {error && (
                  <div
                    className="rounded-2xl border border-red-500/25 bg-red-500/15 px-4 py-3 text-sm text-red-100"
                    role="alert"
                  >
                    {error}
                  </div>
                )}

                <form className="space-y-6" onSubmit={handleSubmit}>
                  <div className="space-y-3">
                    <label
                      htmlFor="email"
                      className="text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-400"
                    >
                      Email
                    </label>
                    <input
                      id="email"
                      name="email"
                      type="email"
                      autoComplete="email"
                      required
                      className="w-full rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm text-mono-white placeholder-mono-gray-500 focus:border-white focus:outline-none focus:ring-2 focus:ring-white/70"
                      placeholder="name@company.com"
                      value={email}
                      onChange={(event) => setEmail(event.target.value)}
                    />
                  </div>

                  <div className="space-y-3">
                    <div className="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-400">
                      <label htmlFor="password">Password</label>
                      <a href="/password/reset" className="text-mono-gray-500 transition-colors hover:text-mono-white">
                        Forgot?
                      </a>
                    </div>
                    <input
                      id="password"
                      name="password"
                      type="password"
                      autoComplete="current-password"
                      required
                      minLength={6}
                      className="w-full rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm text-mono-white placeholder-mono-gray-500 focus:border-white focus:outline-none focus:ring-2 focus:ring-white/70"
                      placeholder="••••••••"
                      value={password}
                      onChange={(event) => setPassword(event.target.value)}
                      onKeyUp={handlePasswordKey}
                      onKeyDown={handlePasswordKey}
                      onBlur={() => setCapsLockOn(false)}
                    />
                    {capsLockOn && (
                      <p className="text-xs uppercase tracking-[0.25em] text-amber-300">Caps lock is on</p>
                    )}
                  </div>

                  <button
                    type="submit"
                    disabled={isLoading}
                    className="group relative flex w-full items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-mono-black transition-all duration-200 hover:shadow-[0_12px_35px_rgba(255,255,255,0.25)] focus:outline-none focus:ring-2 focus:ring-white/80 focus:ring-offset-2 focus:ring-offset-mono-black disabled:cursor-not-allowed disabled:opacity-60"
                  >
                    <span>{isLoading ? 'Signing in…' : 'Sign In'}</span>
                    <span className="absolute inset-y-0 right-5 flex items-center opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                      <i className="fas fa-arrow-right" aria-hidden="true" />
                    </span>
                  </button>
                </form>

                <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-400">
                  Need to register?{' '}
                  <Link to="/register" className="text-mono-white transition-colors hover:text-mono-gray-200">
                    Request an account
                  </Link>
                </p>
              </div>
            </section>
          </div>
        </main>
      </div>
    </div>
  );
};

export default Login;
