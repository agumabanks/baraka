@props(['queueItems' => []])

@php
    $processingLabel = __('dashboard.processing');
    if ($processingLabel === 'dashboard.processing') {
        $processingLabel = 'Processing…';
    }

    $emptyStateTitle = __('dashboard.no_workflow_items_title');
    if ($emptyStateTitle === 'dashboard.no_workflow_items_title') {
        $emptyStateTitle = "You're all caught up";
    }

    $emptyStateDescription = __('dashboard.no_workflow_items_description');
    if ($emptyStateDescription === 'dashboard.no_workflow_items_description') {
        $emptyStateDescription = 'New tasks will appear here in real time.';
    }

    $filterEmptyTitle = __('dashboard.no_filtered_items_title');
    if ($filterEmptyTitle === 'dashboard.no_filtered_items_title') {
        $filterEmptyTitle = 'No tasks match this filter';
    }

    $filterEmptyDescription = __('dashboard.no_filtered_items_description');
    if ($filterEmptyDescription === 'dashboard.no_filtered_items_description') {
        $filterEmptyDescription = 'Try selecting a different priority or reset your filters.';
    }

    $todayWorkflowLabel = __('dashboard.today_workflow');
    if ($todayWorkflowLabel === 'dashboard.today_workflow') {
        $todayWorkflowLabel = "Today's Workflow";
    }

    $filterPriorityLabel = __('dashboard.filter_by_priority');
    if ($filterPriorityLabel === 'dashboard.filter_by_priority') {
        $filterPriorityLabel = 'Filter workflow items by priority';
    }

    $workflowItemsLabel = __('dashboard.workflow_queue_items');
    if ($workflowItemsLabel === 'dashboard.workflow_queue_items') {
        $workflowItemsLabel = 'Workflow queue items';
    }

    $dueLabel = __('dashboard.due');
    if ($dueLabel === 'dashboard.due') {
        $dueLabel = 'Due';
    }

    $filters = [
        'all' => __('levels.all'),
        'high' => __('levels.high'),
        'medium' => __('levels.medium'),
        'low' => __('levels.low'),
    ];
@endphp

<div class="workflow-queue"
     role="region"
     aria-label="{{ $todayWorkflowLabel }}"
     aria-live="polite"
     data-processing-label="{{ $processingLabel }}">
  <div class="queue-header">
    <h3 class="queue-title">{{ $todayWorkflowLabel }}</h3>
    <div class="queue-filters" role="group" aria-label="{{ $filterPriorityLabel }}">
      @foreach($filters as $priority => $label)
        @php
            $isActive = $loop->first;
            $displayLabel = $label === "levels.$priority" ? ucfirst($priority) : $label;
        @endphp
        <button type="button"
                class="filter-btn{{ $isActive ? ' active' : '' }}"
                data-priority="{{ $priority }}"
                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                tabindex="0">
          <span class="filter-btn__label">{{ $priority === 'all' ? ($label === 'levels.all' ? 'All' : $label) : $displayLabel }}</span>
        </button>
      @endforeach
    </div>
  </div>

  <div class="sr-only" aria-live="polite" aria-atomic="true" data-role="queue-status"></div>

  <div class="queue-list" role="list" aria-label="{{ $workflowItemsLabel }}">
    @forelse($queueItems ?? [] as $item)
      <div class="queue-item priority-{{ $item['priority'] ?? 'medium' }}"
           role="listitem"
           data-priority="{{ $item['priority'] ?? 'medium' }}"
           data-type="{{ $item['type'] ?? 'pickup' }}"
           tabindex="0">
        <div class="item-header">
          <div class="item-priority" aria-label="Priority: {{ ucfirst($item['priority'] ?? 'medium') }}">
            {{ ucfirst($item['priority'] ?? 'medium') }}
          </div>
          <div class="item-meta">
            <span class="item-due">{{ $dueLabel }}: {{ $item['due_time'] ?? 'N/A' }}</span>
            <span class="item-type">{{ ucfirst($item['type'] ?? 'pickup') }}</span>
          </div>
        </div>
        <div class="item-content">
          <h4 class="item-title">{{ $item['title'] ?? 'Task Title' }}</h4>
          <p class="item-details">{{ $item['details'] ?? 'Task details' }}</p>
        </div>
        <div class="item-actions" role="group" aria-label="{{ __('dashboard.quick_actions_for') !== 'dashboard.quick_actions_for' ? __('dashboard.quick_actions_for', ['item' => $item['title'] ?? 'task']) : 'Quick actions for '.($item['title'] ?? 'task') }}">
          @if(hasPermission('assign_tasks') ?? true)
            @php
                $assignLabel = __('dashboard.assign');
                if ($assignLabel === 'dashboard.assign') {
                    $assignLabel = 'Assign';
                }
            @endphp
            <button type="button"
                    class="btn-action btn-assign"
                    data-action="assign"
                    data-item-id="{{ $item['id'] ?? '' }}"
                    data-default-label="{{ $assignLabel }}"
                    aria-label="{{ __('dashboard.assign_task') !== 'dashboard.assign_task' ? __('dashboard.assign_task', ['item' => $item['title'] ?? 'task']) : 'Assign '.($item['title'] ?? 'task') }}"
                    tabindex="0">
              <span class="btn-action__label">{{ $assignLabel }}</span>
            </button>
          @endif
          @if(hasPermission('reschedule_tasks') ?? true)
            @php
                $rescheduleLabel = __('dashboard.reschedule');
                if ($rescheduleLabel === 'dashboard.reschedule') {
                    $rescheduleLabel = 'Reschedule';
                }
            @endphp
            <button type="button"
                    class="btn-action btn-reschedule"
                    data-action="reschedule"
                    data-item-id="{{ $item['id'] ?? '' }}"
                    data-default-label="{{ $rescheduleLabel }}"
                    aria-label="{{ __('dashboard.reschedule_task') !== 'dashboard.reschedule_task' ? __('dashboard.reschedule_task', ['item' => $item['title'] ?? 'task']) : 'Reschedule '.($item['title'] ?? 'task') }}"
                    tabindex="0">
              <span class="btn-action__label">{{ $rescheduleLabel }}</span>
            </button>
          @endif
          @if(hasPermission('contact_customers') ?? true)
            @php
                $contactLabel = __('dashboard.contact');
                if ($contactLabel === 'dashboard.contact') {
                    $contactLabel = 'Contact';
                }
            @endphp
            <button type="button"
                    class="btn-action btn-contact"
                    data-action="contact"
                    data-item-id="{{ $item['id'] ?? '' }}"
                    data-default-label="{{ $contactLabel }}"
                    aria-label="{{ __('dashboard.contact_customer') !== 'dashboard.contact_customer' ? __('dashboard.contact_customer', ['item' => $item['title'] ?? 'task']) : 'Contact customer for '.($item['title'] ?? 'task') }}"
                    tabindex="0">
              <span class="btn-action__label">{{ $contactLabel }}</span>
            </button>
          @endif
        </div>
      </div>
    @empty
      <div class="queue-empty-state" data-role="empty-state" role="status" aria-live="polite">
        <div class="queue-empty-state__icon" aria-hidden="true">🗓️</div>
        <h4 class="queue-empty-state__title">{{ $emptyStateTitle }}</h4>
        <p class="queue-empty-state__description">{{ $emptyStateDescription }}</p>
      </div>
    @endforelse
    @if(!empty($queueItems))
      <div class="queue-empty-state queue-empty-state--filters"
           data-role="filter-empty-state"
           role="status"
           aria-live="polite"
           hidden
           aria-hidden="true">
        <div class="queue-empty-state__icon" aria-hidden="true">🗂️</div>
        <h4 class="queue-empty-state__title" data-role="filter-empty-title">{{ $filterEmptyTitle }}</h4>
        <p class="queue-empty-state__description" data-role="filter-empty-description">{{ $filterEmptyDescription }}</p>
      </div>
    @endif
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const workflowQueue = document.querySelector('.workflow-queue');
  if (!workflowQueue) return;

  // Filter functionality
  const filterButtons = workflowQueue.querySelectorAll('.filter-btn');
  const queueItems = workflowQueue.querySelectorAll('.queue-item');
  const statusRegion = workflowQueue.querySelector('[data-role="queue-status"]');
  const processingLabel = workflowQueue.getAttribute('data-processing-label') || 'Processing…';
  const filterEmptyState = workflowQueue.querySelector('[data-role="filter-empty-state"]');

  const setButtonLabel = (button, label) => {
    const labelElement = button.querySelector('.btn-action__label');
    if (labelElement) {
      labelElement.textContent = label;
    } else {
      button.textContent = label;
    }
  };

  function announce(message) {
    if (!message) return;

    if (statusRegion) {
      statusRegion.textContent = message;
    } else {
      announceToScreenReader(message);
    }
  }

  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      const priority = this.getAttribute('data-priority');

      // Update active state
      filterButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
      });
      this.classList.add('active');
      this.setAttribute('aria-pressed', 'true');

      // Filter items
      queueItems.forEach(item => {
        if (priority === 'all' || item.getAttribute('data-priority') === priority) {
          item.style.display = '';
          item.setAttribute('aria-hidden', 'false');
        } else {
          item.style.display = 'none';
          item.setAttribute('aria-hidden', 'true');
        }
      });

      let shouldShowFilterEmpty = false;
      if (filterEmptyState) {
        const hasVisibleItems = Array.from(queueItems).some(item => item.style.display !== 'none');
        shouldShowFilterEmpty = !hasVisibleItems && queueItems.length > 0 && priority !== 'all';
        filterEmptyState.hidden = !shouldShowFilterEmpty;
        filterEmptyState.setAttribute('aria-hidden', shouldShowFilterEmpty ? 'false' : 'true');
      }

      // Announce filter change to screen readers
      const announcement = `Filtered to show ${priority === 'all' ? 'all' : priority + ' priority'} items${shouldShowFilterEmpty ? '. No items match this filter' : ''}`;
      announce(announcement);
    });

    // Keyboard navigation for filters
    button.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.click();
      }
    });
  });

  // Action button handlers
  const actionButtons = workflowQueue.querySelectorAll('.btn-action');
  actionButtons.forEach(button => {
    button.addEventListener('click', function() {
      const action = this.getAttribute('data-action');
      const itemId = this.getAttribute('data-item-id');
      const queueItem = this.closest('.queue-item');
      const itemTitleElement = queueItem ? queueItem.querySelector('.item-title') : null;
      const itemTitle = itemTitleElement ? itemTitleElement.textContent.trim() : '';
      const defaultLabel = this.getAttribute('data-default-label') || this.textContent.trim();
      const actionLabel = defaultLabel || (action ? action.charAt(0).toUpperCase() + action.slice(1) : defaultLabel);
      const busyLabel = this.getAttribute('data-processing-label') || processingLabel;

      // Placeholder for actual action handling
      console.log(`Action: ${action} for item ${itemId}: ${itemTitle}`);

      // Show loading state
      this.disabled = true;
      this.setAttribute('aria-busy', 'true');
      this.setAttribute('data-default-label', defaultLabel);
      setButtonLabel(this, busyLabel);
      announce(`${busyLabel} ${itemTitle}`.trim());

      // Simulate API call
      setTimeout(() => {
        this.disabled = false;
        this.removeAttribute('aria-busy');
        setButtonLabel(this, actionLabel);

        // Announce action completion
        const announcement = `${actionLabel} action completed${itemTitle ? ` for ${itemTitle}` : ''}`;
        announce(announcement);
      }, 1000);
    });

    // Keyboard navigation for actions
    button.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.click();
      }
    });
  });

  // Keyboard navigation for queue items
  queueItems.forEach(item => {
    item.addEventListener('keydown', function(e) {
      const actions = this.querySelectorAll('.btn-action');
      if (actions.length === 0) return;

      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        actions[0].focus();
      }
    });
  });

  // Screen reader announcement helper
  function announceToScreenReader(message) {
    if (!message) return;

    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.style.position = 'absolute';
    announcement.style.left = '-10000px';
    announcement.style.width = '1px';
    announcement.style.height = '1px';
    announcement.style.overflow = 'hidden';
    announcement.textContent = message;

    document.body.appendChild(announcement);
    setTimeout(() => {
      document.body.removeChild(announcement);
    }, 1000);
  }

  // Real-time update framework placeholder
  // This would connect to WebSocket/SSE for live updates
  function setupRealTimeUpdates() {
    // Placeholder for WebSocket/SSE connection
    console.log('Real-time update framework initialized');

    // Example: Listen for updates
    // const eventSource = new EventSource('/api/v10/dashboard/workflow-queue/updates');
    // eventSource.onmessage = function(event) {
    //   const update = JSON.parse(event.data);
    //   handleQueueUpdate(update);
    // };
  }

  function handleQueueUpdate(update) {
    // Placeholder for handling real-time updates
    console.log('Queue update received:', update);
    announce('Workflow queue updated with new items');
  }

  // Initialize real-time updates
  setupRealTimeUpdates();
});
</script>
@endpush
