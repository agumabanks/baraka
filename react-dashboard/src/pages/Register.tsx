import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const Register: React.FC = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');

  const { register } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');

    if (password !== passwordConfirmation) {
      setError('Passwords do not match');
      setIsLoading(false);
      return;
    }

    try {
      await register(name, email, password, passwordConfirmation);
      navigate('/admin/dashboard');
    } catch (err: any) {
      setError(err.response?.data?.message || err.message || 'Registration failed');
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
            <Link to="/login" className="transition-colors hover:text-mono-white">
              Sign in
            </Link>
            <Link to="/register" className="transition-colors hover:text-mono-white">
              Request access
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
                Access reserved for operational leaders.
              </h1>
              <p className="max-w-xl text-sm leading-relaxed text-mono-gray-400">
                Request secure credentials for the executive dashboard. Once approved, you&apos;ll receive
                a monochrome-first workspace tuned for logistics, finance, and customer experience teams.
              </p>
            </div>

            <div className="space-y-3">
              <div className="flex items-start gap-3 text-sm text-mono-gray-300">
                <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-white" aria-hidden="true" />
                Role-based access with granular permissions.
              </div>
              <div className="flex items-start gap-3 text-sm text-mono-gray-300">
                <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-white" aria-hidden="true" />
                Full telemetry across deliveries, finance, and customer care.
              </div>
              <div className="flex items-start gap-3 text-sm text-mono-gray-300">
                <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-white" aria-hidden="true" />
                Minimal visuals. Maximum operational clarity.
              </div>
            </div>
          </section>

          <section className="rounded-3xl border border-mono-gray-200 bg-mono-white p-10 shadow-2xl">
            <div className="space-y-6">
              <div className="space-y-2">
                <h2 className="text-2xl font-semibold text-mono-black">Request access</h2>
                <p className="text-sm text-mono-gray-600">
                  Complete the form. An administrator will verify your details before enabling access.
                </p>
              </div>

              {error && (
                <div className="rounded-2xl border border-mono-gray-300 bg-mono-gray-100 px-4 py-3 text-sm text-mono-black">
                  {error}
                </div>
              )}

              <form className="space-y-5" onSubmit={handleSubmit}>
                <div className="space-y-2">
                  <label htmlFor="name" className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Full name
                  </label>
                  <input
                    id="name"
                    name="name"
                    type="text"
                    autoComplete="name"
                    required
                    className="w-full rounded-2xl border border-mono-gray-300 bg-mono-white px-4 py-3 text-mono-black placeholder-mono-gray-400 focus:outline-none focus:ring-2 focus:ring-mono-black"
                    placeholder="Jane Doe"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                  />
                </div>

                <div className="space-y-2">
                  <label htmlFor="register-email" className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Work email
                  </label>
                  <input
                    id="register-email"
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
                  <label htmlFor="password" className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Password
                  </label>
                  <input
                    id="password"
                    name="password"
                    type="password"
                    autoComplete="new-password"
                    required
                    className="w-full rounded-2xl border border-mono-gray-300 bg-mono-white px-4 py-3 text-mono-black placeholder-mono-gray-400 focus:outline-none focus:ring-2 focus:ring-mono-black"
                    placeholder="Create a password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                  />
                </div>

                <div className="space-y-2">
                  <label htmlFor="password_confirmation" className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                    Confirm password
                  </label>
                  <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autoComplete="new-password"
                    required
                    className="w-full rounded-2xl border border-mono-gray-300 bg-mono-white px-4 py-3 text-mono-black placeholder-mono-gray-400 focus:outline-none focus:ring-2 focus:ring-mono-black"
                    placeholder="Repeat password"
                    value={passwordConfirmation}
                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                  />
                </div>

                <button
                  type="submit"
                  disabled={isLoading}
                  className="w-full rounded-full border border-mono-black bg-mono-black py-3 text-sm font-semibold uppercase tracking-[0.35em] text-mono-white transition-colors hover:bg-mono-gray-900 focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 focus:ring-offset-mono-white disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {isLoading ? 'Creating account...' : 'Submit request'}
                </button>
              </form>

              <p className="text-xs text-mono-gray-500 uppercase tracking-[0.25em]">
                Already onboarded?{' '}
                <Link to="/login" className="text-mono-black hover:text-mono-gray-700">
                  Return to sign in
                </Link>
              </p>
            </div>
          </section>
        </main>
      </div>
    </div>
  );
};

export default Register;
