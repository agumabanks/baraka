import { BrowserRouter as Router, Routes, Route, useLocation } from 'react-router-dom'
import { useState } from 'react'
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
import {
  mockUser,
  mockCurrentLanguage,
  mockLanguages,
  mockNotifications,
  mockBreadcrumbs
} from './data/mockHeaderData'
import { mockFooterData } from './data/mockFooterData'
import type { Language, Notification } from './types/header'

// App Content Component (needs to be inside Router for useLocation)
function AppContent() {
  const location = useLocation()
  const [sidebarOpen, setSidebarOpen] = useState(false)

  const { user, logout } = useAuth()

  const handleNavigate = (path: string) => {
    // In a real app, this would use React Router's navigate
    console.log('Navigate to:', path)
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
          breadcrumbs={mockBreadcrumbs}
          onLanguageChange={handleLanguageChange}
          onNotificationClick={handleNotificationClick}
          onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
          onLogout={handleLogout}
          logoUrl="/images/default/logo1.png"
          appName="Dashboard"
        />

        {/* Page Content */}
        <main className="p-6">
          <Routes>
            <Route index element={<Dashboard />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="analytics" element={<div className="p-6"><h1 className="text-2xl font-bold">Analytics</h1></div>} />
            <Route path="reports" element={<div className="p-6"><h1 className="text-2xl font-bold">Reports</h1></div>} />
            <Route path="settings" element={<div className="p-6"><h1 className="text-2xl font-bold">Settings</h1></div>} />
          </Routes>
        </main>
      </div>
      {/* Footer */}
      <Footer {...mockFooterData} />
    </div>
  )
}

function App() {
  return (
    <ErrorBoundary>
      <AuthProvider>
        <Router>
          <Routes>
            {/* Public Routes */}
            <Route path="/" element={<Home />} />
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
          </Routes>
        </Router>
      </AuthProvider>
    </ErrorBoundary>
  )
}

export default App
