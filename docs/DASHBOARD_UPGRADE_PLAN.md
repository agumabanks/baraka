# Enterprise Dashboard Upgrade Plan

**Repo Reality Note:**
- Backend: Laravel 10+ with Blade templating
- Frontend: Blade templates + Bootstrap 5 + custom Tailwind styling (NOT React)
- RBAC: Permission-based with `hasPermission()` helper checking user permissions per feature
- Current sidebar: Modern styled, collapsible sections with config-driven buckets
- Dashboard: Blade view with permission-filtered KPI cards, statement widgets, charts

**Strategy:** Additive enhancement of existing Blade dashboard; no destructive rewrites.

---

## 1) OBJECTIVES & SUCCESS METRICS

### Business & UX Objectives
- **Enterprise-grade operational clarity**: Reduce cognitive load; single-glance health status
- **Faster decision-making**: ≤2 clicks to critical drill-downs (parcel exceptions, SLA breaches, cash variance)
- **Keyboard-first efficiency**: All nav/filters/actions keyboard-accessible
- **Multi-role precision**: Each role sees only relevant KPIs (no clutter for Finance when viewing Ops metrics)

### Quantified Success Criteria
- **Navigation**: ≤2 clicks to top 5 user tasks (Book Shipment, Parcel Search, Exception List, Cash Report, Support Ticket)
- **Accessibility**: WCAG 2.2 AA compliance (color contrast ≥4.5:1, keyboard nav, ARIA labels)
- **Performance**:
  - LCP ≤2.5s (dashboard first meaningful paint)
  - INP <200ms (interaction responsiveness)
  - CLS <0.1 (no layout jank on metric load)
- **Information density**: 30% increase in visible metrics without scrolling (12-col responsive grid optimization)
- **Theming**: Light/dark mode with user preference persistence

---

## 2) INFORMATION ARCHITECTURE & NAV RESTRUCTURE

### Proposed Sidebar Structure

```
NAVIGATION
├─ Dashboard (fa-home) [badge: SLA alerts if >0]
├─ Quick Actions (fa-bolt) [expandable]
│  ├─ Book Shipment
│  ├─ Parcel Lookup
│  └─ Create Support Ticket
├─ Delivery Team (fa-people-carry) [badge: active drivers count]
├─ Hub Management (fa-warehouse) [nested]
│  ├─ Hubs
│  └─ Payments
├─ Merchant Management (fa-users) [nested]
│  ├─ Merchants
│  └─ Payments
├─ To-do List (fa-tasks) [badge: open count]
├─ Support Tickets (fa-comments) [badge: urgent/open count]
└─ Parcels (fa-dolly) [badge: exception count]

OPERATIONS [divider]
├─ Operations Dashboard (fa-chart-line)
├─ Dispatch Board (fa-map-marked-alt)
└─ Route Optimization (fa-route)

SALES [divider]
└─ Sales Dashboard (fa-dollar-sign)

COMPLIANCE [divider]
└─ Compliance Dashboard (fa-shield-alt)

FINANCE [divider]
├─ Finance Dashboard (fa-coins)
├─ Statements (fa-file-invoice)
└─ Reconciliation (fa-balance-scale)

TOOLS [divider, config-driven buckets]
└─ [Dynamic from config/admin_nav.php]
```

### RBAC Visibility Matrix

| Nav Item | Admin | Manager | Operator | Courier | Finance | Compliance |
|----------|-------|---------|----------|---------|---------|------------|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Quick Actions | ✓ | ✓ | ✓ | ✓ | - | - |
| Delivery Team | ✓ | ✓ | ✓ | ✗ | - | - |
| Hub Management | ✓ | ✓ | ✓ | - | - | - |
| Merchant Management | ✓ | ✓ | ✓ | - | - | - |
| Operations Dashboard | ✓ | ✓ | ✓ | ✓ | - | - |
| Sales Dashboard | ✓ | ✓ | - | - | - | - |
| Finance Dashboard | ✓ | - | - | - | ✓ | - |
| Compliance Dashboard | ✓ | - | - | - | - | ✓ |

*Permission keys: `operations_dashboard_read`, `finance_dashboard_read`, `compliance_dashboard_read`, `sales_dashboard_read`*

### Global Utilities (Top Navbar)
- **Search**: Global parcel/tracking search (Ctrl+K shortcut, command palette-style)
- **Quick Actions Dropdown**: Book Shipment, Add Customer, Create Ticket
- **Notifications**: Bell icon with badge, dropdown list, mark-as-read
- **Language Switch**: Existing dropdown, preserve current i18n
- **Dark Mode Toggle**: Sun/moon icon, persist in `user_preferences` table or localStorage
- **Profile Menu**: Name, Settings, Logout

---

## 3) DESIGN SYSTEM & TOKENS

### Color Tokens

**Brand Primary:**
- `--color-primary-50`: #eff6ff
- `--color-primary-500`: #3b82f6 (buttons, active states)
- `--color-primary-700`: #1d4ed8 (hover)
- `--color-primary-900`: #1e3a8a (text on light)

**Neutrals (Light Theme):**
- `--color-gray-50`: #f9fafb (backgrounds)
- `--color-gray-100`: #f3f4f6 (hover states)
- `--color-gray-200`: #e5e7eb (borders)
- `--color-gray-700`: #374151 (body text)
- `--color-gray-900`: #111827 (headings)

**Neutrals (Dark Theme):**
- `--color-dark-bg`: #0f172a (canvas)
- `--color-dark-surface`: #1e293b (cards)
- `--color-dark-border`: rgba(148,163,184,0.18)
- `--color-dark-text`: #e2e8f0 (body)
- `--color-dark-text-muted`: rgba(226,232,240,0.7)

**Semantic Colors:**
- Success: #10b981 (green-500)
- Warning: #f59e0b (amber-500)
- Error: #ef4444 (red-500)
- Info: #3b82f6 (blue-500)

**Contrast Checks:**
- All text/background combinations must pass WCAG AA (4.5:1 for normal text, 3:1 for large)
- Focus rings: 2px solid with 3:1 contrast against background

### Spacing Scale
- Base unit: 4px
- Scale: 4px, 8px, 12px, 16px, 24px, 32px, 48px, 64px

### Border Radius
- sm: 4px (inputs)
- md: 8px (buttons)
- lg: 12px (cards)
- xl: 16px (modals)

### Elevation (Box Shadows)
- sm: 0 1px 2px rgba(0,0,0,0.05)
- md: 0 4px 6px rgba(0,0,0,0.07)
- lg: 0 10px 15px rgba(0,0,0,0.1)
- xl: 0 20px 25px rgba(0,0,0,0.15)

### Typography
- Font family: 'Inter', system-ui, sans-serif (load via Google Fonts CDN)
- Base size: 16px
- Scale: 12px (xs), 14px (sm), 16px (base), 18px (lg), 24px (xl), 32px (2xl)
- Weights: 400 (regular), 500 (medium), 600 (semibold), 700 (bold)
- Line heights: 1.5 (body), 1.2 (headings)

### Component Inventory

**Navigation:**
- Sidebar, NavGroup, NavItem, NavBadge

**KPI Widgets:**
- KPICard (number + trend), MetricGrid (multi-metric), TrendCard (sparkline + change %), MiniTable (top 5 items), ProgressBar, Gauge

**Layout:**
- DashboardGrid (responsive 12-col), Section, Card, EmptyState

**Forms & Filters:**
- FilterBar, DateRangePicker, MultiSelect, SearchInput

**Feedback:**
- Toast, Modal, Drawer, LoadingSkeleton, Spinner

**Data Display:**
- Table, Pagination, Badge, Chip, Tooltip

### State Naming Convention
- default, hover, active, focus, disabled, selected, attention (pulsing for alerts)

---

## 4) DASHBOARD BLUEPRINT

### Primary KPIs by Role

**Admin / Manager / Operator:**
1. Today's SLA Status (on-time %)
2. Shipments in Exception (count + click to drill)
3. Pickup/Delivery Queue Today (pending pickup, out for delivery)
4. Open Support Tickets by Severity (urgent/high/normal)
5. Cash Collected vs Expected (7d trend)
6. On-time Delivery % (7d rolling)
7. Avg. Delivery Time (hours)
8. Customer Satisfaction Score (if available)

**Courier:**
1. My Tasks Today (assigned pickups/deliveries)
2. My On-time Rate (7d)
3. My Completed Tasks (today count)
4. My Route Efficiency Score

**Finance:**
1. Today's Cash Collection (actual vs target)
2. Pending COD Remittances (count + amount)
3. Merchant Payment Backlog (count + amount)
4. Revenue vs Target (MTD)
5. Outstanding Invoices (aging analysis)

**Compliance:**
1. Overweight/Underdeclared Shipments (count)
2. Missing Documentation (count)
3. Customs Hold (count)
4. Regulatory Violations (count + severity)

### Layout: 12-Column Responsive Grid

**Row 1: Business Health (full-width cards)**
- Col 1-3: SLA Status (big number + gauge)
- Col 4-6: Exceptions (number + change % + drill-down)
- Col 7-9: On-time Delivery % (sparkline + trend)
- Col 10-12: Open Tickets (severity breakdown)

**Row 2: Work in Progress**
- Col 1-6: Today's Queue (pickup/delivery split, mini table of top 5)
- Col 7-12: Cash Collected vs Expected (bar chart, 7d)

**Row 3: Trends & Statements**
- Col 1-6: Delivery Man Statement (existing, refactored as widget)
- Col 7-12: Merchant Statement (existing, refactored as widget)

**Row 4: Charts**
- Col 1-6: Income/Expense Chart (existing, enhanced)
- Col 7-12: Courier Revenue Pie (existing, enhanced)

**Row 5: Quick Actions Tiles**
- Col 1-3: Book Shipment (link card)
- Col 4-6: Bulk Upload (link card)
- Col 7-9: Generate Report (link card)
- Col 10-12: View All Parcels (link card)

### KPI Specifications

#### 1. Today's SLA Status
- **Title**: "SLA Performance Today"
- **Description**: "% of shipments delivered within promised time window"
- **Formula**: `(on_time_deliveries / total_deliveries_due_today) * 100`
- **Timeframe**: Current day (00:00 to now)
- **Drill-down**: Route to `/parcels?filter=delivered_today&sort=sla_status`
- **States**: Loading (skeleton), Success (green if ≥95%), Warning (amber if 90-94%), Error (red if <90%), Empty (no deliveries scheduled today)

#### 2. Shipments in Exception
- **Title**: "Exception Parcels"
- **Description**: "Shipments requiring attention (failed delivery, damaged, lost, etc.)"
- **Formula**: `COUNT(parcels WHERE status IN [exception_statuses])`
- **Timeframe**: Real-time
- **Drill-down**: `/parcels?filter=exception_status`
- **States**: Loading, Normal (count), Attention (pulsing badge if >10), Empty (no exceptions)

#### 3. On-time Delivery % (7d)
- **Title**: "7-Day Delivery Performance"
- **Description**: "Rolling 7-day on-time delivery rate"
- **Formula**: `(on_time_deliveries_7d / total_deliveries_7d) * 100`
- **Timeframe**: Last 7 days
- **Drill-down**: `/reports/delivery-performance?range=7d`
- **States**: Loading, Success (sparkline + %), Empty (no data)

#### 4. Open Tickets by Severity
- **Title**: "Support Tickets"
- **Description**: "Open support tickets grouped by severity"
- **Formula**: `GROUP_COUNT(tickets WHERE status=open BY severity)`
- **Timeframe**: Real-time
- **Drill-down**: `/support?filter=open&severity={clicked_severity}`
- **States**: Loading, Normal (severity chips), Attention (red pulse if urgent >0), Empty (no open tickets)

#### 5. Cash Collected vs Expected (7d)
- **Title**: "Cash Collection Trend"
- **Description**: "Actual COD collected vs expected over last 7 days"
- **Formula**: `SUM(cod_receipts.amount) vs SUM(parcels.cod_amount WHERE status=delivered)`
- **Timeframe**: Last 7 days
- **Drill-down**: `/finance/cash-collection?range=7d`
- **States**: Loading, Normal (dual-axis chart), Warning (variance >10%), Empty (no COD deliveries)

#### 6. Pickup/Delivery Queue Today
- **Title**: "Today's Workload"
- **Description**: "Pending pickups and active deliveries"
- **Formula**: `COUNT(parcels WHERE status IN [pending_pickup, out_for_delivery] AND scheduled_date=today)`
- **Timeframe**: Today
- **Drill-down**: `/parcels?filter=pending_or_active_today`
- **States**: Loading, Normal (split counts), Empty (all completed)

#### Quick Action Tiles
- **Book Shipment**: Links to `/admin/booking/step1`, icon: fa-plus-circle
- **Bulk Upload**: Links to `/admin/parcels/bulk-upload`, icon: fa-file-upload
- **Generate Report**: Links to `/reports/generate`, icon: fa-file-export
- **View All Parcels**: Links to `/parcels`, icon: fa-dolly

---

## 5) API CONTRACTS

### Read-Only Dashboard Endpoints

#### GET `/api/dashboard/kpis`

**Query Params:**
- `date_range`: today | 7d | 30d | custom
- `date_from`: YYYY-MM-DD (if custom)
- `date_to`: YYYY-MM-DD (if custom)
- `branch_id`: (optional filter by branch)
- `courier_id`: (optional filter by courier)

**Response:**
```json
{
  "success": true,
  "data": {
    "sla_status": {
      "percentage": 96.5,
      "on_time": 193,
      "total": 200,
      "change_7d": 2.1
    },
    "exceptions": {
      "count": 12,
      "change_24h": 3,
      "by_type": {
        "failed_delivery": 5,
        "damaged": 3,
        "lost": 2,
        "address_issue": 2
      }
    },
    "on_time_delivery_7d": {
      "percentage": 94.2,
      "sparkline": [95, 93, 94, 96, 94, 95, 94],
      "trend": "stable"
    },
    "open_tickets": {
      "total": 28,
      "urgent": 2,
      "high": 8,
      "normal": 18
    },
    "cash_collected_7d": {
      "expected": 125000,
      "collected": 118500,
      "variance": -5.2,
      "daily": [
        {"date": "2025-09-24", "expected": 18000, "collected": 17200},
        ...
      ]
    },
    "today_queue": {
      "pending_pickup": 42,
      "out_for_delivery": 87,
      "top_pending": [
        {"id": 1, "tracking_number": "BRK123", "customer": "John", "branch": "North"},
        ...
      ]
    }
  },
  "cache_ttl": 300
}
```

**Caching:** 5 min for aggregates, 1 min for real-time counters (exceptions, queue)

#### GET `/api/dashboard/statements`

**Query Params:**
- `entity_type`: delivery_man | merchant | hub
- `date_from`: YYYY-MM-DD
- `date_to`: YYYY-MM-DD

**Response:**
```json
{
  "success": true,
  "data": {
    "entity_type": "delivery_man",
    "income": 45000,
    "expense": 12000,
    "balance": 33000,
    "transactions": [
      {"date": "2025-09-30", "type": "income", "amount": 1500, "description": "COD collection"},
      ...
    ]
  },
  "cache_ttl": 600
}
```

**Caching:** 10 min (financial data, less volatile)

#### GET `/api/dashboard/charts/income-expense`

**Query Params:**
- `date_range`: 7d | 30d | 90d

**Response:**
```json
{
  "success": true,
  "data": {
    "income": [12000, 15000, 13500, 14200, 16000, 17500, 15800],
    "expense": [8000, 9500, 8200, 9000, 10200, 11000, 10500],
    "labels": ["Sep 24", "Sep 25", "Sep 26", "Sep 27", "Sep 28", "Sep 29", "Sep 30"],
    "net": [4000, 5500, 5300, 5200, 5800, 6500, 5300]
  },
  "cache_ttl": 600
}
```

**Caching:** 10 min

### Reuse Existing Entities
- Leverage existing Parcel, User, Hub, Merchant, Account models
- Add thin read models if complex joins needed (e.g., `DashboardMetricsView` for pre-aggregated daily stats)

### Idempotent Quick Actions

#### POST `/api/quick-actions/book-shipment`

**Headers:**
- `Idempotency-Key`: UUID (prevent duplicate bookings)

**Body:**
```json
{
  "customer_id": 123,
  "origin_hub_id": 5,
  "destination_address": {...},
  "parcel_details": {...}
}
```

**Response:** 201 Created (shipment resource) or 200 OK (if idempotency key already processed)

---

## 6) ACCESSIBILITY & i18n

### Keyboard Navigation
- **Tab Order**: Logo → Search → Notifications → Profile → Sidebar items (top-down) → Main content KPIs (left-to-right, top-to-bottom) → Footer
- **Skip Links**: "Skip to main content" (hidden until focused, jumps past sidebar/nav)
- **Focus Rings**: 2px solid `--color-primary-500`, offset 2px
- **Shortcuts**:
  - `Ctrl+K` or `Cmd+K`: Open search palette
  - `Alt+D`: Focus dashboard
  - `Alt+N`: Open notifications
  - `/`: Focus global search
  - `Esc`: Close modals/drawers

### ARIA Roles & Labels
- Sidebar: `<nav role="navigation" aria-label="Main navigation">`
- Each nav item: `aria-current="page"` for active
- KPI cards: `<article role="article" aria-labelledby="kpi-title-{id}">`
- Badges: `<span role="status" aria-live="polite">12 new</span>`
- Charts: `<div role="img" aria-label="Income vs Expense chart for last 7 days">`
- Empty states: `<div role="status" aria-label="No data available">`

### RTL Readiness
- Use logical properties: `margin-inline-start` instead of `margin-left`
- Flip icons for directional UI (chevrons, arrows)
- Test with `lang="ar"` and `dir="rtl"` attributes

### i18n Strategy
- Translation keys: `dashboard.sla_status`, `dashboard.exceptions`, etc.
- Number formatting: `number_format($value, 2)` for currency, respect locale decimal separator
- Date formatting: Use `Carbon::parse()->locale(app()->getLocale())->isoFormat('ll')`
- Units: Store in config (`currency_symbol`, `distance_unit`)

---

## 7) PERFORMANCE BUDGET & TELEMETRY

### Performance Targets
- **LCP (Largest Contentful Paint)**: ≤2.5s
  - Tactic: Inline critical CSS, defer non-critical JS, lazy-load charts
- **INP (Interaction to Next Paint)**: <200ms
  - Tactic: Debounce search input, use virtualized tables for large lists
- **CLS (Cumulative Layout Shift)**: <0.1
  - Tactic: Reserve space for KPI cards with skeletons, explicit width/height for images

### Optimization Tactics
1. **Code Splitting**: Separate JS bundles for dashboard widgets (load on-demand via `import()`)
2. **Lazy Data Loading**: Load KPIs in parallel with `Promise.all()`, show skeletons individually
3. **Skeletons**: Use CSS-only skeleton loaders (animated gradients) while data fetches
4. **HTTP Caching**:
   - KPI endpoints: `Cache-Control: public, max-age=300, stale-while-revalidate=60`
   - Chart data: `Cache-Control: public, max-age=600, stale-while-revalidate=120`
5. **Laravel Caching**: Cache dashboard queries in Redis with 5-10 min TTL
6. **Asset Optimization**: Minify CSS/JS, use Brotli compression, CDN for static assets

### Telemetry Events

#### Navigation Events
- **Event**: `nav_item_clicked`
- **Payload**: `{item_key: "parcels", user_role: "admin", timestamp: ISO8601}`

#### KPI Interactions
- **Event**: `kpi_drilled`
- **Payload**: `{kpi_name: "exceptions", drill_target: "/parcels?filter=exception_status", user_id: 123}`

#### Filter Changes
- **Event**: `filter_changed`
- **Payload**: `{filter_type: "date_range", value: "7d", page: "dashboard"}`

#### Quick Actions
- **Event**: `quick_action_used`
- **Payload**: `{action: "book_shipment", source: "dashboard_tile"}`

**Implementation**: Use `gtag('event', ...)` or custom Laravel event system writing to `analytics_events` table

---

## 8) TASK GRAPH (≤60-min tasks, dependency-ordered)

### Phase 1: Foundation (Design Tokens & Theme)
1. **Create design tokens CSS file** (30 min)
   - Goal: Define all color, spacing, typography tokens as CSS variables
   - Files: `resources/css/design-tokens.css`
   - Acceptance: Both light/dark themes defined, AA contrast verified
   - Reviewer: Fast Coder

2. **Implement dark mode toggle** (45 min)
   - Goal: Sun/moon icon in navbar, persist preference in localStorage
   - Files: `resources/views/backend/partials/navber.blade.php`, `public/js/theme-switcher.js`
   - Acceptance: Theme switches instantly, persists across sessions
   - Reviewer: Fast Coder

3. **Create component skeleton library** (60 min)
   - Goal: CSS-only loading skeletons for KPI cards, tables, charts
   - Files: `resources/css/skeleton-loaders.css`
   - Acceptance: Smooth pulse animation, matches design tokens
   - Reviewer: Fast Coder

### Phase 2: Sidebar Enhancements
4. **Add badge support to sidebar nav items** (30 min)
   - Goal: Display counts (open tickets, exceptions) next to nav items
   - Files: `resources/views/backend/partials/sidebar.blade.php`
   - Acceptance: Badges show dynamically, update on data change
   - Reviewer: Fast Coder

5. **Implement quick actions dropdown in navbar** (45 min)
   - Goal: Dropdown with Book Shipment, Add Customer, Create Ticket
   - Files: `resources/views/backend/partials/navber.blade.php`
   - Acceptance: Keyboard accessible, ARIA labeled
   - Reviewer: Fast Coder

6. **Add keyboard shortcut handler** (45 min)
   - Goal: Ctrl+K for search, Alt+D for dashboard, Esc for modals
   - Files: `public/js/keyboard-shortcuts.js`
   - Acceptance: All shortcuts work, no conflict with browser defaults
   - Reviewer: Fast Coder

### Phase 3: Backend APIs
7. **Create dashboard KPIs controller** (60 min)
   - Goal: `/api/dashboard/kpis` endpoint with all metrics
   - Files: `app/Http/Controllers/Api/DashboardController.php`
   - Acceptance: Returns JSON matching spec, handles filters
   - Reviewer: Premium (involves business logic)

8. **Implement caching layer for KPIs** (45 min)
   - Goal: Redis cache with 5 min TTL, stale-while-revalidate
   - Files: `app/Services/DashboardMetricsService.php`
   - Acceptance: Cache hit rate >80%, invalidation on data change
   - Reviewer: Premium

9. **Create statements API endpoint** (45 min)
   - Goal: `/api/dashboard/statements` for delivery man/merchant/hub
   - Files: `app/Http/Controllers/Api/DashboardController.php`
   - Acceptance: Returns correct income/expense/balance, cached
   - Reviewer: Fast Coder

10. **Create charts API endpoint** (30 min)
    - Goal: `/api/dashboard/charts/income-expense` for chart data
    - Files: `app/Http/Controllers/Api/DashboardController.php`
    - Acceptance: Returns 7d/30d/90d data, optimized queries
    - Reviewer: Fast Coder

### Phase 4: Dashboard UI Components
11. **Refactor dashboard layout to 12-col grid** (45 min)
    - Goal: Replace current layout with responsive 12-col grid
    - Files: `resources/views/backend/dashboard.blade.php`
    - Acceptance: Responsive on mobile/tablet/desktop, no CLS
    - Reviewer: Fast Coder

12. **Create KPI card Blade component** (60 min)
    - Goal: Reusable `<x-kpi-card>` with loading/error/empty states
    - Files: `resources/views/components/kpi-card.blade.php`
    - Acceptance: Accessible, supports drill-down links, shows trends
    - Reviewer: Fast Coder

13. **Implement SLA Status widget** (45 min)
    - Goal: Today's SLA gauge with color-coded status
    - Files: `resources/views/backend/dashboard.blade.php`, JS fetch
    - Acceptance: Fetches data, shows gauge, handles empty state
    - Reviewer: Fast Coder

14. **Implement Exceptions widget** (45 min)
    - Goal: Exception count with pulsing badge if >10
    - Files: Dashboard view, JS component
    - Acceptance: Drill-down link works, badge pulses correctly
    - Reviewer: Fast Coder

15. **Implement On-time Delivery % widget** (60 min)
    - Goal: 7d trend with sparkline chart
    - Files: Dashboard view, add lightweight sparkline lib (sparkline.js)
    - Acceptance: Sparkline renders, tooltip shows daily %
    - Reviewer: Fast Coder

16. **Implement Open Tickets widget** (45 min)
    - Goal: Severity breakdown with drill-down per severity
    - Files: Dashboard view, JS component
    - Acceptance: Chips show counts, clicking filters support tickets
    - Reviewer: Fast Coder

17. **Implement Cash Collection widget** (60 min)
    - Goal: Bar chart showing expected vs collected (7d)
    - Files: Dashboard view, use ApexCharts (already in project)
    - Acceptance: Dual-axis chart, variance highlighted
    - Reviewer: Fast Coder

18. **Implement Today's Queue widget** (45 min)
    - Goal: Mini table of top 5 pending parcels
    - Files: Dashboard view, JS component
    - Acceptance: Table loads, clicking row opens parcel detail
    - Reviewer: Fast Coder

19. **Refactor existing statement widgets** (45 min)
    - Goal: Extract delivery man/merchant/hub statements into components
    - Files: `resources/views/components/statement-card.blade.php`
    - Acceptance: Reusable, matches new design tokens
    - Reviewer: Fast Coder

20. **Enhance existing charts** (45 min)
    - Goal: Apply new color tokens, improve contrast, add dark mode
    - Files: `resources/views/backend/dashboard-charts.blade.php`
    - Acceptance: Charts respect theme, AA contrast in both modes
    - Reviewer: Fast Coder

### Phase 5: Quick Actions & Utilities
21. **Create quick action tiles** (30 min)
    - Goal: Book Shipment, Bulk Upload, Reports, View All tiles
    - Files: Dashboard view
    - Acceptance: Tiles link correctly, icon+text layout
    - Reviewer: Fast Coder

22. **Implement global search (Ctrl+K)** (60 min)
    - Goal: Command palette-style search for parcels/tracking
    - Files: `resources/views/backend/partials/navber.blade.php`, `public/js/global-search.js`
    - Acceptance: Opens on shortcut, searches parcels, keyboard navigable
    - Reviewer: Premium (complex interaction)

23. **Add notifications dropdown** (45 min)
    - Goal: Bell icon with badge, dropdown list of notifications
    - Files: Navbar view, `app/Http/Controllers/NotificationController.php`
    - Acceptance: Loads unread count, mark-as-read works
    - Reviewer: Fast Coder

### Phase 6: Role-Based Dashboards
24. **Create role-based KPI filter logic** (45 min)
    - Goal: Show only relevant KPIs per role (Admin/Operator/Finance/etc)
    - Files: `app/Services/DashboardMetricsService.php`, dashboard view
    - Acceptance: Finance sees only finance KPIs, Courier sees only courier KPIs
    - Reviewer: Premium (RBAC logic)

25. **Add permission checks to dashboard sections** (30 min)
    - Goal: Wrap KPI widgets with `@if(hasPermission(...))`
    - Files: Dashboard view
    - Acceptance: Each widget respects permissions
    - Reviewer: Fast Coder

### Phase 7: Accessibility & i18n
26. **Add ARIA labels to all KPI widgets** (30 min)
    - Goal: Screen reader friendly, proper roles
    - Files: Dashboard view, component files
    - Acceptance: Passes axe DevTools audit
    - Reviewer: Fast Coder

27. **Implement skip-to-content link** (15 min)
    - Goal: Hidden link at top, visible on focus
    - Files: Master layout
    - Acceptance: Tab once from page load, Enter skips to main
    - Reviewer: Fast Coder

28. **Add keyboard focus rings to all interactive elements** (30 min)
    - Goal: 2px primary-500 ring on focus
    - Files: Design tokens CSS, component styles
    - Acceptance: All buttons/links/inputs show focus ring
    - Reviewer: Fast Coder

29. **Extract all hardcoded text to i18n keys** (45 min)
    - Goal: Replace strings with `{{ __('dashboard.key') }}`
    - Files: Dashboard view, components
    - Acceptance: English translations in `lang/en/dashboard.php`
    - Reviewer: Fast Coder

30. **Test RTL layout** (30 min)
    - Goal: Set locale to Arabic, verify layout flips correctly
    - Files: CSS (use logical properties)
    - Acceptance: Sidebar, KPIs, charts flip correctly
    - Reviewer: Fast Coder

### Phase 8: Performance Optimization
31. **Implement lazy loading for chart widgets** (45 min)
    - Goal: Load chart JS only when widget scrolls into view
    - Files: Dashboard view, use Intersection Observer
    - Acceptance: Charts load on-demand, LCP <2.5s
    - Reviewer: Fast Coder

32. **Add HTTP caching headers to API endpoints** (30 min)
    - Goal: Set `Cache-Control` with stale-while-revalidate
    - Files: API middleware, dashboard controller
    - Acceptance: Browser caches responses, revalidates in background
    - Reviewer: Fast Coder

33. **Optimize dashboard SQL queries** (60 min)
    - Goal: Add indexes, reduce N+1, use eager loading
    - Files: Dashboard metrics service, DB migrations for indexes
    - Acceptance: Query time <100ms per KPI, no N+1 alerts
    - Reviewer: Premium (DB optimization)

34. **Implement Redis caching for dashboard data** (45 min)
    - Goal: Cache KPI results with 5 min TTL
    - Files: Dashboard metrics service
    - Acceptance: Cache hit rate >80%, auto-invalidation
    - Reviewer: Fast Coder

### Phase 9: Telemetry
35. **Add analytics event tracking** (45 min)
    - Goal: Track nav clicks, KPI drills, filter changes, quick actions
    - Files: `public/js/analytics.js`, create `analytics_events` table
    - Acceptance: Events fire correctly, stored in DB
    - Reviewer: Fast Coder

36. **Create analytics dashboard (optional)** (60 min)
    - Goal: View event logs, popular features, user behavior
    - Files: `app/Http/Controllers/AnalyticsController.php`, view
    - Acceptance: Admin can view event summary
    - Reviewer: Fast Coder (optional task)

### Phase 10: Testing & Documentation
37. **Write feature tests for dashboard APIs** (60 min)
    - Goal: Test KPIs endpoint, statements endpoint, auth/RBAC
    - Files: `tests/Feature/DashboardApiTest.php`
    - Acceptance: All tests pass, >80% coverage
    - Reviewer: Premium

38. **Write accessibility audit checklist** (30 min)
    - Goal: Document WCAG compliance, keyboard nav, ARIA usage
    - Files: `docs/DASHBOARD_A11Y_AUDIT.md`
    - Acceptance: Checklist complete, ready for manual testing
    - Reviewer: Fast Coder

39. **Create user guide for dashboard** (45 min)
    - Goal: Explain each KPI, how to use filters, quick actions
    - Files: `docs/DASHBOARD_USER_GUIDE.md`
    - Acceptance: Non-technical users can understand
    - Reviewer: Fast Coder

40. **Update API documentation (OpenAPI)** (30 min)
    - Goal: Add dashboard endpoints to swagger docs
    - Files: Dashboard controller PHPDoc annotations
    - Acceptance: Swagger UI shows new endpoints
    - Reviewer: Fast Coder

---

## 9) MIGRATION & ROLLBACK (SAFE)

### Additive-Then-Switch Approach

**Phase 1: Additive**
1. Create new dashboard route `/dashboard-v2` (parallel to existing `/dashboard`)
2. Build new components in `resources/views/backend/dashboard-v2.blade.php`
3. New API endpoints in `Api/V2/DashboardController` (or versioned routes)
4. All existing routes/views remain untouched

**Phase 2: Feature Flag**
1. Add `dashboard_v2_enabled` column to `users` table (boolean, default false)
2. Or use config flag: `config('features.dashboard_v2_enabled')` per-user or global
3. Middleware checks flag: if enabled, serve v2; else serve v1

**Phase 3: A/B Rollout**
1. Enable for internal users (admin team) first
2. Monitor telemetry for errors, performance, user feedback
3. Gradually expand to 10%, 50%, 100% of users
4. During this phase, users can opt-in/out via profile settings

**Phase 4: Full Switch**
1. Once stable (e.g., 2 weeks with <0.1% error rate), set global flag to v2
2. Redirect `/dashboard` to new implementation
3. Deprecate v1 routes after 1 month

### No Destructive DB Changes
- All new columns are `nullable` or have defaults
- No dropping of existing tables/columns
- Read models (if added) are separate tables, not replacing core entities

### Backfill Steps
- If adding `user_preferences` table for theme/settings: backfill with defaults for existing users
- Run migration: `php artisan migrate`
- Seed preferences: `php artisan db:seed --class=UserPreferencesSeeder`

### Rollback Steps
1. **Config Flip**: Set `config('features.dashboard_v2_enabled' => false)` or env var
2. **Route Fallback**: Update route middleware to serve v1 if v2 errors occur
3. **Revert Diffs**: If code merged to production, revert the merge commit (git revert)
4. **Cache Clear**: `php artisan cache:clear`, `php artisan view:clear`, `php artisan config:clear`
5. **User Communication**: Notify users of temporary revert, ETA for re-enable

### Testing Before Full Rollout
- Automated: Run feature tests (`php artisan test --filter=Dashboard`)
- Manual: QA checklist covering all KPIs, permissions, themes, browsers
- Performance: Lighthouse CI reports must meet targets (LCP <2.5s, CLS <0.1)
- Accessibility: axe DevTools audit, keyboard-only navigation test

---

## 10) ACCEPTANCE TESTS (Gherkin-style)

### Scenario 1: Role-Based Menu Visibility
```gherkin
Feature: Role-based dashboard menu visibility
  As an admin
  I want to see all dashboard sections
  So that I can access all system features

Scenario: Admin sees all menu items
  Given I am logged in as an "admin" user
  When I navigate to "/dashboard"
  Then I should see the "Operations Dashboard" menu item
  And I should see the "Finance Dashboard" menu item
  And I should see the "Compliance Dashboard" menu item

Scenario: Finance user sees only finance menu
  Given I am logged in as a "finance" user
  When I navigate to "/dashboard"
  Then I should see the "Finance Dashboard" menu item
  And I should NOT see the "Operations Dashboard" menu item
  And I should NOT see the "Compliance Dashboard" menu item
```

### Scenario 2: Keyboard Navigation
```gherkin
Feature: Keyboard navigation
  As a power user
  I want to navigate the dashboard using only keyboard
  So that I can work efficiently

Scenario: Navigate sidebar using Tab and Enter
  Given I am on the dashboard page
  When I press "Tab" until "Parcels" nav item is focused
  And I press "Enter"
  Then I should navigate to the parcels page

Scenario: Open search with Ctrl+K
  Given I am on any page
  When I press "Ctrl+K" (or "Cmd+K" on Mac)
  Then the global search modal should open
  And the search input should be focused

Scenario: Close modal with Escape
  Given the global search modal is open
  When I press "Esc"
  Then the modal should close
  And focus should return to the page
```

### Scenario 3: Dark/Light Theme Contrast
```gherkin
Feature: Theme contrast compliance
  As a user with visual impairment
  I want sufficient color contrast in both themes
  So that I can read all text comfortably

Scenario: Light theme text contrast
  Given the dashboard is in "light" theme
  When I inspect any KPI card heading
  Then the contrast ratio should be at least 4.5:1 (WCAG AA)

Scenario: Dark theme text contrast
  Given the dashboard is in "dark" theme
  When I inspect any KPI card heading
  Then the contrast ratio should be at least 4.5:1 (WCAG AA)

Scenario: Theme preference persists
  Given I switch to "dark" theme
  When I reload the page
  Then the "dark" theme should still be active
```

### Scenario 4: KPI Correctness
```gherkin
Feature: KPI calculation accuracy
  As a manager
  I want accurate KPI values
  So that I can make informed decisions

Background:
  Given the following fixture data exists:
    | parcels_delivered_today | 95 |
    | parcels_due_today       | 100 |
    | parcels_in_exception    | 8 |
    | open_tickets_urgent     | 2 |

Scenario: SLA Status shows correct percentage
  Given I am on the dashboard
  When the "SLA Status" widget loads
  Then it should display "95.0%"
  And the status color should be "amber" (90-94% range)

Scenario: Exception count is accurate
  Given I am on the dashboard
  When the "Exceptions" widget loads
  Then it should display "8"
  And the badge should NOT be pulsing (threshold is >10)

Scenario: Urgent tickets show attention state
  Given I am on the dashboard
  When the "Open Tickets" widget loads
  Then the "urgent" chip should display "2"
  And the widget should have a pulsing red indicator
```

### Scenario 5: Drill-Down Routing
```gherkin
Feature: KPI drill-down navigation
  As a user
  I want to click on a KPI to see detailed data
  So that I can investigate issues

Scenario: Exception KPI drill-down
  Given I am on the dashboard
  When I click on the "Exceptions" KPI card
  Then I should navigate to "/parcels?filter=exception_status"
  And the parcels list should show only exception parcels

Scenario: Open tickets severity drill-down
  Given I am on the dashboard
  When I click on the "urgent" severity chip in "Open Tickets" widget
  Then I should navigate to "/support?filter=open&severity=urgent"
  And the support tickets list should show only urgent open tickets
```

### Scenario 6: Telemetry Event Firing
```gherkin
Feature: Analytics event tracking
  As a product manager
  I want to track user interactions with the dashboard
  So that I can understand feature usage

Scenario: Track nav item click
  Given I am on the dashboard
  When I click on the "Parcels" nav item
  Then an analytics event "nav_item_clicked" should fire
  And the payload should contain:
    | item_key | parcels |
    | user_role | admin  |

Scenario: Track KPI drill-down
  Given I am on the dashboard
  When I click on the "Exceptions" KPI to drill down
  Then an analytics event "kpi_drilled" should fire
  And the payload should contain:
    | kpi_name | exceptions |
    | drill_target | /parcels?filter=exception_status |

Scenario: Track quick action usage
  Given I am on the dashboard
  When I click the "Book Shipment" quick action tile
  Then an analytics event "quick_action_used" should fire
  And the payload should contain:
    | action | book_shipment |
    | source | dashboard_tile |
```

---

## 11) RISKS & MITIGATIONS

### Risk 1: Data Freshness Lag
**Description:** Cached KPI data may be stale, showing incorrect counts (e.g., exception parcels already resolved)

**Impact:** Medium (user confusion, redundant investigations)

**Likelihood:** High (5 min cache TTL)

**Mitigation:**
- Implement cache invalidation on data change (e.g., when parcel status updated, clear KPI cache)
- Show "Last updated" timestamp on each widget
- Add manual refresh button (with debounce to prevent abuse)
- Use Laravel cache tags for granular invalidation: `Cache::tags(['dashboard:kpis'])->flush()`

### Risk 2: RBAC Permission Leaks
**Description:** User might see KPIs they shouldn't due to missing permission checks

**Impact:** High (security, data privacy violation)

**Likelihood:** Medium (developer oversight)

**Mitigation:**
- Wrap every widget with `@if(hasPermission('kpi_name_read'))`
- Add automated test suite checking each role's visible widgets
- Code review checklist: "Are all new dashboard sections permission-gated?"
- Use policy classes for complex permission logic: `$this->authorize('view', DashboardMetric::class)`

### Risk 3: N+1 Query Performance on Heavy KPIs
**Description:** Dashboard page load triggers hundreds of queries, causing timeout

**Impact:** High (page load >10s, user frustration)

**Likelihood:** Medium (complex KPIs with nested relationships)

**Mitigation:**
- Use eager loading: `Parcel::with(['hub', 'merchant', 'deliveryman'])->get()`
- Add database indexes on frequently filtered columns (status, date, branch_id)
- Implement query monitoring: Laravel Telescope or Clockwork
- Set up APM alerts (New Relic, Datadog) for queries >100ms
- Consider read replicas for heavy analytics queries

### Risk 4: CLS Regression (Layout Jank)
**Description:** KPI widgets pop in after page load, causing content to shift

**Impact:** Medium (poor UX, fails Core Web Vitals)

**Likelihood:** High (async data loading)

**Mitigation:**
- Reserve space with skeletons (fixed height for each widget)
- Use CSS `aspect-ratio` for chart containers
- Load critical KPIs inline (server-side render), defer heavy charts
- Test with slow 3G throttling in Chrome DevTools
- Monitor CLS in production with Real User Monitoring (RUM)

### Risk 5: i18n Text Truncation
**Description:** Translated text longer than English, breaking layouts (e.g., German words)

**Impact:** Low (visual glitch, readability issue)

**Likelihood:** High (certain languages verbose)

**Mitigation:**
- Use `text-overflow: ellipsis` with tooltips for long labels
- Test UI with longest target language (German, Russian, Arabic)
- Set max-width on text containers, allow wrapping for multi-line
- Avoid fixed pixel widths; use responsive units (rem, %)
- Design reviews with i18n in mind (allocate 30% more space for text)

---

## Summary

This plan provides a **surgical, additive upgrade** to the existing Laravel Blade dashboard, respecting the current architecture (no React rewrite). It focuses on:
1. **Enterprise-grade KPIs** with clear drill-downs
2. **Role-based precision** (RBAC-filtered widgets)
3. **Accessibility & keyboard efficiency** (WCAG AA, shortcuts)
4. **Performance optimization** (caching, lazy loading, skeletons)
5. **Safe migration** (feature flags, A/B rollout, easy rollback)

All tasks are scoped to ≤60 minutes, with clear acceptance criteria and reviewer guidance (Fast Coder for UI/JS, Premium for backend logic). The plan avoids destructive changes and enables incremental rollout to minimize risk.

**Next Steps:**
1. Review this plan with stakeholders (PM, UX, Dev leads)
2. Prioritize tasks into 2-week sprints
3. Set up feature flag infrastructure
4. Begin Phase 1: Foundation tasks
5. Schedule weekly progress reviews and adjust as needed