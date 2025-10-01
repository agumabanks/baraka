import { BrowserRouter as Router, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useMemo, useState } from 'react'
import { AuthProvider, useAuth } from './contexts/AuthContext'
import ProtectedRoute from './components/ProtectedRoute'
import ErrorBoundary from './components/ErrorBoundary'
import Sidebar from './components/layout/Sidebar'
import Header from './components/layout/Header'
import Footer from './components/layout/Footer'
import Dashboard from './pages/Dashboard'
import Home from './pages/Home'
import Login from './pages/Login'
import Register from './pages/Register'
import { navigationConfig } from './config/navigation'
import { navigationRoutes, findRouteMeta } from './lib/navigation'
import { routeDescriptions } from './config/routeDescriptions'
import ResourcePage from './pages/ResourcePage'
import {
  mockUser,
  mockCurrentLanguage,
  mockLanguages,
  mockNotifications,
} from './data/mockHeaderData'
import { mockFooterData } from './data/mockFooterData'
import type { Language, Notification } from './types/header'

// App Content Component (needs to be inside Router for useLocation)
function AppContent() {
  const location = useLocation()
  const navigate = useNavigate()
  const [sidebarOpen, setSidebarOpen] = useState(false)

  const { user, logout } = useAuth()

  const handleNavigate = (path: string) => {
    navigate(path)
    setSidebarOpen(false)
  }

  const handleCloseSidebar = () => {
    setSidebarOpen(false)
  }

  const handleLanguageChange = (language: Language) => {
    console.log('Language changed to:', language.name)
    // In a real app, this would update the app's locale
  }

  const handleNotificationClick = (notification: Notification) => {
    console.log('Notification clicked:', notification.title)
    // In a real app, this would navigate to the notification's action URL
  }

  const handleLogout = async () => {
    try {
      await logout()
    } catch (error) {
      console.error('Logout error:', error)
    }
  }

  const routeMeta = useMemo(() => findRouteMeta(location.pathname), [location.pathname])

  const breadcrumbs = useMemo(() => {
    const crumbs = [] as { label: string; href?: string; active?: boolean }[]

    if (location.pathname === '/dashboard' || !routeMeta) {
      crumbs.push({ label: 'Dashboard', href: '/dashboard', active: true })
      return crumbs
    }

    crumbs.push({ label: 'Dashboard', href: '/dashboard', active: false })

    routeMeta.parents
      .filter((parent) => parent.label && parent.label !== routeMeta.label)
      .forEach((parent) => {
        crumbs.push({
          label: parent.label,
          href: parent.path && parent.path !== routeMeta.path ? parent.path : undefined,
          active: false,
        })
      })

    crumbs.push({ label: routeMeta.label, active: true })
    return crumbs
  }, [location.pathname, routeMeta])

  return (
    <div className="min-h-screen bg-mono-white text-mono-gray-900 flex">
      {/* Sidebar */}
      <Sidebar
        navigation={navigationConfig}
        currentPath={location.pathname}
        isOpen={sidebarOpen}
        onClose={handleCloseSidebar}
        onNavigate={handleNavigate}
      />

      {/* Main Content */}
      <div className="flex-1 lg:ml-0">
        {/* Header */}
        <Header
          user={user ? {
            id: user.id.toString(),
            name: user.name,
            email: user.email,
            avatar: '/images/default/avatar.png',
            role: 'admin'
          } : mockUser}
          currentLanguage={mockCurrentLanguage}
          languages={mockLanguages}
          notifications={mockNotifications}
          breadcrumbs={breadcrumbs}
          onLanguageChange={handleLanguageChange}
          onNotificationClick={handleNotificationClick}
          onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
          onLogout={handleLogout}
          logoUrl="/images/default/logo1.png"
          appName="Dashboard"
          isSidebarOpen={sidebarOpen}
        />

        {/* Page Content */}
        <main className="p-6">
          <Routes>
            <Route index element={<Dashboard />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="analytics" element={<div className="p-6"><h1 className="text-2xl font-bold">Analytics</h1></div>} />
            <Route path="reports" element={<div className="p-6"><h1 className="text-2xl font-bold">Reports</h1></div>} />
            <Route path="settings" element={<div className="p-6"><h1 className="text-2xl font-bold">Settings</h1></div>} />
            {navigationRoutes
              .filter((meta) => meta.path && meta.path !== '/dashboard')
              .map((meta) => {
                const routePath = meta.path.startsWith('/') ? meta.path : `/${meta.path}`
                return (
                  <Route
                    key={routePath}
                    path={routePath}
                    element={
                      <ResourcePage
                        meta={meta}
                        description={routeDescriptions[meta.path]}
                      />
                    }
                  />
                )
              })}
          </Routes>
        </main>
      </div>
      {/* Footer */}
      <Footer {...mockFooterData} />
    </div>
  )
}

function App() {
  const configuredBase = (import.meta.env.BASE_URL ?? '/').replace(/\/$/, '')

  const resolveBaseName = () => {
    if (typeof window === 'undefined') {
      return configuredBase === '/' ? '' : configuredBase
    }

    const currentPath = window.location.pathname
    return currentPath.startsWith(configuredBase) ? configuredBase : ''
  }

  const routerBase = resolveBaseName()

  return (
    <ErrorBoundary>
      <AuthProvider>
        <Router basename={routerBase}>
          <Routes>
            {/* Public Routes */}
            <Route path="/" element={<Navigate to="/login" replace />} />
            <Route path="/landing" element={<Home />} />
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />

            {/* Protected Dashboard */}
            <Route
              path="/dashboard/*"
              element={(
                <ProtectedRoute>
                  <AppContent />
                </ProtectedRoute>
              )}
            />
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </Router>
      </AuthProvider>
    </ErrorBoundary>
  )
}

export default App
