# Task Board Transformation — Design Blueprint

## Guiding Principles (Jobsian Standards)
1. **Simplicity** — every action is obvious; defaults reduce cognitive load.
2. **Elegance** — clean monochrome aesthetic with purposeful color for state and urgency.
3. **Delight** — micro-interactions, buttery drag/drop, responsive feedback.
4. **Focus** — surface the right context (dependencies, owners, timers) without clutter.

## Experience Goals
- **Single source of truth** shared with Today’s Workflow snapshot and Operations Workflow Board.
- **Real-time collaborative canvas** where transitions, edits, and bulk actions appear instantly for all connected operators.
- **Permission-aware automation** that respects admin / standard roles, project scopes, and explicit task assignments.
- **Power user efficiency** via keyboard shortcuts, saved filter views, and bulk tooling.
- **Operational visibility** with dependency signals, time tracking badges, attachment previews, and activity counts directly on the card.
- **Resilience** with offline banners, optimistic UI, error toasts, and auto-recovery.

## Feature Architecture

### 1. Shared Data Layer
- **`useWorkflowQueue` + `useWorkflowBoard`** feed a new `TaskBoardStore` (Zustand) caching latest payload.
- **BroadcastChannel “workflow-sync”** for same-origin multi-tab updates; server-side WebSocket/SSE ready hook for cross-user sync.
- **Optimistic updates** with rollback on API failure; queued retries when offline.
- **Bidirectional sync**: dashboard widgets subscribe to the shared store to stay in lockstep.

### 2. Permission Framework
- **Role detection** via enriched `AuthContext` (`role_key`, `permissions`, `project_ids`).
- **Task metadata** expanded to include `assigned_user_id`, `project_id`, `allowed_transitions`, `restricted_roles`.
- **`canTransition(task, from, to, user)`** utility ensures:
  - Super admins & admins → unrestricted.
  - Standard users → must match assignee OR have project permission AND transition allowed.
  - Custom rules (e.g., QA only moves `testing` → `completed`).
- Denied moves show subtle toast + column shake animation.

### 3. Kanban Canvas (shared component)
- **HTML5 drag/drop** with drop indicators, ghost previews, and auto-scroll.
- **Selection affordance** (checkbox) enabling multi-select.
- **Card anatomy**:
  - Title, description excerpt, priority chip, due badge.
  - Dependency pills (blocked, at risk, complete).
  - Attachment & activity counters.
  - Time tracking badge (running indicator).
  - Avatar stack for assignee + watchers.
  - Quick actions row (status transitions, timer, comment, files).
- **Context menu** (three-dot) for edit, delete, clone, timers, recurring toggles.
- **Empty state** friendly prompts per column.

### 4. Advanced Filters & Presets
- Filter pane includes status, priority, owner, project, tags, due range, dependency state.
- **Saved presets** with “My Backlog”, “QA Today” etc., persisted in localStorage & optionally server.
- Quick toggle chips + keyboard shortcuts (`F` to focus search, `Shift+1` apply first preset).

### 5. Bulk & Keyboard Operations
- Multi-select supports bulk status change, assign, delete/archive, export.
- Keyboard map:
  - `N` new task modal.
  - `Cmd/Ctrl+K` command palette (future).
  - Arrow keys navigates cards; `Enter` opens detail modal; `Space` toggles selection.
  - `Cmd/Ctrl+Shift+Arrow` transitions selected tasks.

### 6. Real-time Collaboration
- **Broadcast events** for create/update/delete/status/assignment.
- **Activity log** appended with user & timestamp; detail modal streams latest entries.
- Visual toast and subtle highlight when card updates from another user.

### 7. Integrations
- **Dashboard “Today’s Workflow”** subscribes to store, showing counts with quick filters.
- **Operations Workflow Board** shares the Kanban component with operations-specific theming.
- **Time tracking** surfaces aggregated seconds + CTA to open detailed view.
- **Attachments** preview using lightbox (existing infra) where available.

### 8. Resilience & UX polish
- Offline banner with queuing icon, disable server mutations.
- Loading skeletons for columns; optimistic placeholders on create.
- Error boundary with recovery CTA per column.
- Micro animations (drop bounce, column pulsate on update).

## Implementation Phasing (current iteration covers Phase 1)
1. **Phase 1 (this PR)** — shared Kanban component, permission-aware drag/drop, bulk selection, filter presets, real-time channel scaffold, offline banner.
2. **Phase 2** — WebSocket/SSE integration, command palette, advanced filters persisted server-side, task dependency management UI.
3. **Phase 3** — Time tracking deep integration, file previews, recurring automation, server-driven workflow templates.

This blueprint guides the implementation in this sprint and future enhancements.
