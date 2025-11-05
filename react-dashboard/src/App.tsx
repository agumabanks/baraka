import { BrowserRouter as Router, Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useMemo, useState, useEffect, useCallback } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
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
import Bookings from './pages/Bookings'
import Branches from './pages/Branches'
import BranchDetail from './pages/BranchDetail'
import Merchants from './pages/Merchants'
import MerchantDetail from './pages/MerchantDetail'
import MerchantPayments from './pages/MerchantPayments'
import Shipments from './pages/Shipments'
import Todo from './pages/Todo'
import AllCustomers from './pages/sales/AllCustomers'
import CreateCustomer from './pages/sales/CreateCustomer'
import Quotations from './pages/sales/Quotations'
import Contracts from './pages/sales/Contracts'
import AddressBook from './pages/sales/AddressBook'
import AllSupport from './pages/support/AllSupport'
import SupportDetail from './pages/support/SupportDetail'
import SupportForm from './pages/support/SupportForm'
import BranchManagersIndex from './pages/branch-managers/BranchManagersIndex'
import BranchManagerCreate from './pages/branch-managers/BranchManagerCreate'
import BranchManagerShow from './pages/branch-managers/BranchManagerShow'
import BranchManagerEdit from './pages/branch-managers/BranchManagerEdit'
import BranchWorkersIndex from './pages/branch-workers/BranchWorkersIndex'
import BranchWorkerCreate from './pages/branch-workers/BranchWorkerCreate'
import BranchWorkerShow from './pages/branch-workers/BranchWorkerShow'
import BranchWorkerEdit from './pages/branch-workers/BranchWorkerEdit'
import BranchHierarchy from './pages/branches/BranchHierarchy'
import LocalClients from './pages/branches/LocalClients'
import ShipmentsByBranch from './pages/branches/ShipmentsByBranch'
import LiveTracking from './pages/LiveTracking'
import RolesManagement from './pages/settings/RolesManagement'
import UsersManagement from './pages/settings/UsersManagement'
import WorkflowBoard from './pages/operations/WorkflowBoard'

// TODO: These components need to be created for full navigation
// Operations Components
// import DispatchBoard from './pages/operations/DispatchBoard'
// import ExceptionTower from './pages/operations/ExceptionTower'
// import ControlTower from './pages/operations/ControlTower'
// import Parcels from './pages/operations/Parcels'
// import Bags from './pages/operations/Bags'
// import RoutesPage from './pages/operations/Routes'

// Finance Components
// import RateCards from './pages/finance/RateCards'
// import Invoices from './pages/finance/Invoices'
// import CODDashboard from './pages/finance/CODDashboard'
// import Settlements from './pages/finance/Settlements'

// Compliance Components
// import KYCVerification from './pages/compliance/KYCVerification'
// import DangerousGoods from './pages/compliance/DangerousGoods'
// import CustomsDeclarations from './pages/compliance/CustomsDeclarations'
// import FraudDetection from './pages/compliance/FraudDetection'

// Integration Components
// import APIKeys from './pages/integrations/APIKeys'
// import Webhooks from './pages/integrations/Webhooks'
// import IntegrationMonitoring from './pages/integrations/IntegrationMonitoring'

// Asset Components
// import AssetStatus from './pages/assets/AssetStatus'
// import Vehicles from './pages/assets/Vehicles'

// Branch Components
// import BranchAnalytics from './pages/branches/BranchAnalytics'
// import BranchHierarchy from './pages/branches/BranchHierarchy'
// import BranchCapacity from './pages/branches/BranchCapacity'
// import BranchWorkers from './pages/branches/BranchWorkers'

// Workers
// import DeliveryWorkers from './pages/DeliveryWorkers'

// Reports
// import Reports from './pages/Reports'
// import OperationsReports from './pages/reports/OperationsReports'
// import FinancialReports from './pages/reports/FinancialReports'
// import ComplianceReports from './pages/reports/ComplianceReports'

// Settings
// import Users from './pages/settings/Users'
import { navigationConfig } from './config/navigation'
import { buildNavigationRoutes, findRouteMeta } from './lib/navigation'
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
import type { NavigationConfig } from './types/navigation'
import type { RouteMeta as NavigationRouteMeta } from './lib/navigation'
import { navigationApi } from './services/api'
import api from './services/api'
import { setLocale } from './lib/i18n'
import {
  canonicalisePath,
  resolveRoutePath,
  resolveDashboardNavigatePath,
  getCanonicalFromAlias,
} from './lib/spaNavigation'

// App Content Component (needs to be inside Router for useLocation)
function AppContent() {
  const location = useLocation()
  const navigate = useNavigate()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const queryClient = useQueryClient()

  const { user, logout } = useAuth()

  const { data: navigationResponse } = useQuery<{ success: boolean; data: NavigationConfig }>({
    queryKey: ['navigation', 'admin'],
    queryFn: navigationApi.getAdminNavigation,
  })

  const navigation = useMemo(() => {
    if (navigationResponse?.success && navigationResponse.data) {
      return navigationResponse.data
    }
    return navigationConfig
  }, [navigationResponse])

  const routes = useMemo(() => buildNavigationRoutes(navigation), [navigation])

  const enhancedRoutes = useMemo(() => {
    const seen = new Set<string>()
    const blocked = new Set(['', 'dashboard', 'analytics', 'reports', 'settings'])

    return routes.reduce<Array<{ meta: NavigationRouteMeta; routePath: string; descriptionKey: string }>>((acc, meta) => {
      const routePath = resolveRoutePath(meta.path)

      if (!routePath || seen.has(routePath) || blocked.has(routePath)) {
        return acc
      }

      seen.add(routePath)

      const absoluteMetaPath = meta.path.startsWith('/') ? meta.path : `/${meta.path}`
      const fallbackKey = `/${routePath}`
      const descriptionKey = routeDescriptions[absoluteMetaPath] ? absoluteMetaPath : fallbackKey

      acc.push({
        meta,
        routePath,
        descriptionKey,
      })

      return acc
    }, [])
  }, [routes])
  const languageOptions = useMemo(() => mockLanguages, [])
  const [activeLanguage, setActiveLanguage] = useState<Language>(mockCurrentLanguage)

  useEffect(() => {
    if (typeof window === 'undefined') {
      setLocale(activeLanguage.code)
      api.defaults.headers.common['Accept-Language'] = activeLanguage.code
      return
    }

    const storedLocale = window.localStorage.getItem('dashboard_locale')
    const resolved = storedLocale
      ? languageOptions.find((language) => language.code === storedLocale)
      : null

    const initial = resolved ?? mockCurrentLanguage
    setActiveLanguage(initial)
    setLocale(initial.code)
    api.defaults.headers.common['Accept-Language'] = initial.code
  }, [languageOptions])

  const handleNavigate = useCallback((path: string) => {
    // Backend returns absolute paths like '/branches', '/merchants', etc.
    // We need to navigate to ABSOLUTE paths to prevent concatenation
    
    console.log('[Navigation] Received path:', path);
    
    // Strip leading slash to clean up
    let cleanPath = path.startsWith('/') ? path.slice(1) : path;
    
    // Remove 'dashboard' prefix if present in the path
    if (cleanPath === 'dashboard' || cleanPath.startsWith('dashboard/')) {
      cleanPath = cleanPath.replace(/^dashboard\/?/, '');
    }
    
    // Build absolute path from root to prevent path concatenation
    // This ensures navigation works correctly no matter what page we're on
    const absolutePath = cleanPath ? `/dashboard/${cleanPath}` : '/dashboard';
    
    console.log('[Navigation] Navigating to absolute path:', absolutePath);
    
    navigate(absolutePath);
    setSidebarOpen(false)
  }, [navigate])

  const handleCloseSidebar = () => {
    setSidebarOpen(false)
  }

  const handleLanguageChange = useCallback((language: Language) => {
    setActiveLanguage(language)
    setLocale(language.code)
    api.defaults.headers.common['Accept-Language'] = language.code
    if (typeof window !== 'undefined') {
      window.localStorage.setItem('dashboard_locale', language.code)
    }
    queryClient.invalidateQueries({ queryKey: ['navigation', 'admin'] })
    queryClient.invalidateQueries({ queryKey: ['dashboard', 'data'] })
    queryClient.invalidateQueries({ queryKey: ['dashboard', 'charts'] })
    queryClient.invalidateQueries({ queryKey: ['dashboard', 'kpis'] })
    queryClient.invalidateQueries({ queryKey: ['dashboard', 'workflow-queue'] })
  }, [queryClient])

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

  const routeMeta = useMemo(() => {
    const directMatch = findRouteMeta(location.pathname, routes)
    if (directMatch) {
      return directMatch
    }

    const canonical = canonicalisePath(location.pathname)
    if (!canonical) {
      return undefined
    }

    const withoutDashboardPrefix = canonical.startsWith('dashboard/')
      ? canonical.slice('dashboard/'.length)
      : canonical

    const canonicalPath = getCanonicalFromAlias(withoutDashboardPrefix)
    if (!canonicalPath) {
      return undefined
    }

    const lookupPath = canonicalPath.startsWith('/') ? canonicalPath : `/${canonicalPath}`
    return findRouteMeta(lookupPath, routes)
  }, [location.pathname, routes])

  const breadcrumbs = useMemo(() => {
    const crumbs = [] as { label: string; href?: string; active?: boolean }[]

    if (location.pathname === '/dashboard' || !routeMeta) {
      crumbs.push({ label: 'Dashboard', href: '/dashboard', active: true })
      return crumbs
    }

    crumbs.push({ label: 'Dashboard', href: '/dashboard', active: false })

    const activeHref = resolveDashboardNavigatePath(routeMeta.path)

    routeMeta.parents
      .filter((parent) => parent.label && parent.label !== routeMeta.label)
      .forEach((parent) => {
        const parentHref = parent.path ? resolveDashboardNavigatePath(parent.path) : undefined
        crumbs.push({
          label: parent.label,
          href: parentHref && parentHref !== activeHref ? parentHref : undefined,
          active: false,
        })
      })

    crumbs.push({ label: routeMeta.label, active: true })
    return crumbs
  }, [location.pathname, routeMeta])

  return (
    <div className="flex h-screen bg-mono-white text-mono-gray-900 overflow-hidden">
      {/* Sidebar */}
      <Sidebar
        navigation={navigation}
        currentPath={location.pathname}
        isOpen={sidebarOpen}
        onClose={handleCloseSidebar}
        onNavigate={handleNavigate}
      />

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <Header
          user={user ? {
            id: user.id.toString(),
            name: user.name,
            email: user.email,
            avatar: '/images/default/user.png',
            role: 'admin'
          } : mockUser}
          currentLanguage={activeLanguage}
          languages={languageOptions}
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
        <main className="flex-1 overflow-y-auto p-6">
          <Routes>
            <Route index element={<Dashboard />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="analytics" element={<div className="p-6"><h1 className="text-2xl font-bold">Analytics</h1></div>} />
            <Route path="reports" element={<div className="p-6"><h1 className="text-2xl font-bold">Reports</h1></div>} />
            <Route path="settings" element={<div className="p-6"><h1 className="text-2xl font-bold">Settings</h1></div>} />
            <Route path="support/:id" element={<SupportDetail />} />
            <Route path="support/:id/edit" element={<SupportForm />} />
            <Route path="branches/:branchId" element={<BranchDetail />} />
            <Route path="branch-managers/:id" element={<BranchManagerShow />} />
            <Route path="branch-managers/:id/edit" element={<BranchManagerEdit />} />
            <Route path="branch-workers/:id" element={<BranchWorkerShow />} />
            <Route path="branch-workers/:id/edit" element={<BranchWorkerEdit />} />
            <Route path="merchants/:merchantId" element={<MerchantDetail />} />
            <Route path="todo" element={<Todo />} />
            
            {/* Operations Routes - TODO: Implement these components */}
            <Route path="operations/dispatch" element={<div className="p-6"><h1 className="text-2xl font-bold">Dispatch Board</h1><p>Coming soon...</p></div>} />
            <Route path="operations/exceptions" element={<div className="p-6"><h1 className="text-2xl font-bold">Exception Tower</h1><p>Coming soon...</p></div>} />
            <Route path="operations/control-tower" element={<div className="p-6"><h1 className="text-2xl font-bold">Control Tower</h1><p>Coming soon...</p></div>} />
            <Route path="workflow" element={<WorkflowBoard />} />
            
            {/* Finance Routes - TODO: Implement these components */}
            <Route path="finance/rate-cards" element={<div className="p-6"><h1 className="text-2xl font-bold">Rate Cards</h1><p>Coming soon...</p></div>} />
            <Route path="finance/invoices" element={<div className="p-6"><h1 className="text-2xl font-bold">Invoices</h1><p>Coming soon...</p></div>} />
            <Route path="finance/cod" element={<div className="p-6"><h1 className="text-2xl font-bold">COD Dashboard</h1><p>Coming soon...</p></div>} />
            <Route path="finance/settlements/*" element={<div className="p-6"><h1 className="text-2xl font-bold">Settlements</h1><p>Coming soon...</p></div>} />
            
            {/* Compliance Routes - TODO: Implement these components */}
            <Route path="compliance/kyc" element={<div className="p-6"><h1 className="text-2xl font-bold">KYC Verification</h1><p>Coming soon...</p></div>} />
            <Route path="compliance/dg/*" element={<div className="p-6"><h1 className="text-2xl font-bold">Dangerous Goods</h1><p>Coming soon...</p></div>} />
            <Route path="compliance/customs/*" element={<div className="p-6"><h1 className="text-2xl font-bold">Customs Declarations</h1><p>Coming soon...</p></div>} />
            <Route path="compliance/fraud/*" element={<div className="p-6"><h1 className="text-2xl font-bold">Fraud Detection</h1><p>Coming soon...</p></div>} />
            
            {/* Integration Routes - TODO: Implement these components */}
            <Route path="integrations/api-keys" element={<div className="p-6"><h1 className="text-2xl font-bold">API Keys</h1><p>Coming soon...</p></div>} />
            <Route path="integrations/webhooks" element={<div className="p-6"><h1 className="text-2xl font-bold">Webhooks</h1><p>Coming soon...</p></div>} />
            <Route path="integrations/monitoring" element={<div className="p-6"><h1 className="text-2xl font-bold">Integration Monitoring</h1><p>Coming soon...</p></div>} />
            
            {/* Asset Routes - TODO: Implement these components */}
            <Route path="assets" element={<div className="p-6"><h1 className="text-2xl font-bold">Asset Status</h1><p>Coming soon...</p></div>} />
            <Route path="vehicles" element={<div className="p-6"><h1 className="text-2xl font-bold">Vehicles</h1><p>Coming soon...</p></div>} />
            
            {/* Branch Routes */}
            <Route path="branches/hierarchy" element={<BranchHierarchy />} />
            <Route path="branches/clients" element={<LocalClients />} />
            <Route path="branches/shipments" element={<ShipmentsByBranch />} />
            <Route path="branches/analytics" element={<div className="p-6"><h1 className="text-2xl font-bold">Branch Analytics</h1><p>Coming soon...</p></div>} />
            <Route path="branches/capacity" element={<div className="p-6"><h1 className="text-2xl font-bold">Capacity Planning</h1><p>Coming soon...</p></div>} />
            <Route path="branches/workers" element={<div className="p-6"><h1 className="text-2xl font-bold">Branch Workers</h1><p>Coming soon...</p></div>} />
            
            {/* Additional Operations Routes - TODO: Implement these components */}
            <Route path="parcels" element={<div className="p-6"><h1 className="text-2xl font-bold">Parcels</h1><p>Coming soon...</p></div>} />
            <Route path="bags" element={<div className="p-6"><h1 className="text-2xl font-bold">Bags</h1><p>Coming soon...</p></div>} />
            <Route path="routes" element={<div className="p-6"><h1 className="text-2xl font-bold">Routes</h1><p>Coming soon...</p></div>} />
            <Route path="routes/optimize" element={<div className="p-6"><h1 className="text-2xl font-bold">Route Optimizer</h1><p>Coming soon...</p></div>} />
            <Route path="routes/stops" element={<div className="p-6"><h1 className="text-2xl font-bold">Stops</h1><p>Coming soon...</p></div>} />
            
            {/* Delivery Workers - TODO: Implement this component */}
            <Route path="deliveryman" element={<div className="p-6"><h1 className="text-2xl font-bold">Delivery Workers</h1><p>Coming soon...</p></div>} />
            <Route path="branches/workers" element={<div className="p-6"><h1 className="text-2xl font-bold">Branch Workers</h1><p>Coming soon...</p></div>} />
            
            {/* Reports Routes - TODO: Implement these components */}
            <Route path="reports" element={<div className="p-6"><h1 className="text-2xl font-bold">Reports</h1><p>Coming soon...</p></div>} />
            <Route path="reports/operations" element={<div className="p-6"><h1 className="text-2xl font-bold">Operations Reports</h1><p>Coming soon...</p></div>} />
            <Route path="reports/financial" element={<div className="p-6"><h1 className="text-2xl font-bold">Financial Reports</h1><p>Coming soon...</p></div>} />
            <Route path="reports/compliance" element={<div className="p-6"><h1 className="text-2xl font-bold">Compliance Reports</h1><p>Coming soon...</p></div>} />
            <Route path="reports/analytics" element={<div className="p-6"><h1 className="text-2xl font-bold">Analytics Reports</h1><p>Coming soon...</p></div>} />
            <Route path="reports/workforce" element={<div className="p-6"><h1 className="text-2xl font-bold">Workforce Reports</h1><p>Coming soon...</p></div>} />
            
            {/* Settings Routes - TODO: Implement these components */}
            <Route path="settings/roles" element={<RolesManagement />} />
            <Route path="settings/users" element={<UsersManagement />} />
            <Route path="users" element={<UsersManagement />} />
            
            {enhancedRoutes.map(({ meta, routePath, descriptionKey }) => {
              let element: React.ReactNode

              switch (routePath) {
                case 'bookings':
                  element = <Bookings />
                  break
                case 'tracking':
                  element = <LiveTracking />
                  break
                case 'live-tracking':
                  element = <LiveTracking />
                  break
                case 'shipments/tracking':
                  element = <LiveTracking />
                  break
                case 'branches':
                  element = <Branches />
                  break
                case 'branch-managers':
                  element = <BranchManagersIndex />
                  break
                case 'branch-managers/create':
                  element = <BranchManagerCreate />
                  break
                case 'branch-workers':
                  element = <BranchWorkersIndex />
                  break
                case 'branch-workers/create':
                  element = <BranchWorkerCreate />
                  break
                case 'merchants':
                  element = <Merchants />
                  break
                case 'merchant/payments':
                  element = <MerchantPayments />
                  break
                case 'shipments':
                  element = <Shipments />
                  break
                case 'todo':
                  element = <Todo />
                  break
                case 'workflow':
                case 'workflow/board':
                case 'operations/workflow':
                  element = <WorkflowBoard />
                  break
                case 'customers':
                  element = <AllCustomers />
                  break
                case 'customers/create':
                  element = <CreateCustomer />
                  break
                case 'quotations':
                  element = <Quotations />
                  break
                case 'contracts':
                  element = <Contracts />
                  break
                case 'address-book':
                  element = <AddressBook />
                  break
                case 'support':
                  element = <AllSupport />
                  break
                case 'support/create':
                  element = <SupportForm />
                  break
                case 'reports':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Reports</h1><p>Coming soon...</p></div>
                  break
                case 'reports/operations':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Operations Reports</h1><p>Coming soon...</p></div>
                  break
                case 'reports/financial':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Financial Reports</h1><p>Coming soon...</p></div>
                  break
                case 'reports/compliance':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Compliance Reports</h1><p>Coming soon...</p></div>
                  break
                case 'parcels':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Parcels</h1><p>Coming soon...</p></div>
                  break
                case 'bags':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Bags</h1><p>Coming soon...</p></div>
                  break
                case 'routes':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Routes</h1><p>Coming soon...</p></div>
                  break
                case 'deliveryman':
                  element = <div className="p-6"><h1 className="text-2xl font-bold">Delivery Workers</h1><p>Coming soon...</p></div>
                  break
                case 'settings/roles':
                  element = <RolesManagement />
                  break
                case 'settings/users':
                  element = <UsersManagement />
                  break
                case 'users':
                  element = <UsersManagement />
                  break
                case 'roles':
                  element = <RolesManagement />
                  break
                default:
                  element = (
                    <ResourcePage
                      meta={meta}
                      description={routeDescriptions[descriptionKey] ?? routeDescriptions[`/${routePath}`]}
                    />
                  )
                  break
              }

              return (
                <Route
                  key={routePath}
                  path={routePath}
                  element={element}
                />
              )
            })}
          </Routes>

          <Footer {...mockFooterData} />
        </main>
      </div>
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
