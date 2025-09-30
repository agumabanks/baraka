@props(['queueItems' => []])

<div class="workflow-queue" role="region" aria-label="Today's workflow queue" aria-live="polite">
  <div class="queue-header">
    <h3 class="queue-title">{{ __('dashboard.today_workflow') ?? 'Today\'s Workflow' }}</h3>
    <div class="queue-filters" role="group" aria-label="Filter workflow items by priority">
      <button class="filter-btn active" data-priority="all" aria-pressed="true" tabindex="0">
        {{ __('levels.all') ?? 'All' }}
      </button>
      <button class="filter-btn" data-priority="high" aria-pressed="false" tabindex="0">
        {{ __('levels.high') ?? 'High' }}
      </button>
      <button class="filter-btn" data-priority="medium" aria-pressed="false" tabindex="0">
        {{ __('levels.medium') ?? 'Medium' }}
      </button>
      <button class="filter-btn" data-priority="low" aria-pressed="false" tabindex="0">
        {{ __('levels.low') ?? 'Low' }}
      </button>
    </div>
  </div>

  <div class="queue-list" role="list" aria-label="Workflow queue items">
    @forelse($queueItems ?? [] as $item)
      <div class="queue-item priority-{{ $item['priority'] ?? 'medium' }}"
           role="listitem"
           data-priority="{{ $item['priority'] ?? 'medium' }}"
           data-type="{{ $item['type'] ?? 'pickup' }}"
           tabindex="0">
        <div class="item-priority" aria-label="Priority: {{ ucfirst($item['priority'] ?? 'medium') }}">
          {{ ucfirst($item['priority'] ?? 'medium') }}
        </div>
        <div class="item-content">
          <h4 class="item-title">{{ $item['title'] ?? 'Task Title' }}</h4>
          <p class="item-details">{{ $item['details'] ?? 'Task details' }}</p>
          <div class="item-meta">
            <span class="item-due">{{ __('dashboard.due') ?? 'Due' }}: {{ $item['due_time'] ?? 'N/A' }}</span>
            <span class="item-type">{{ ucfirst($item['type'] ?? 'pickup') }}</span>
          </div>
        </div>
        <div class="item-actions" role="group" aria-label="Quick actions for {{ $item['title'] ?? 'task' }}">
          @if(hasPermission('assign_tasks') ?? true)
            <button class="btn-action btn-assign"
                    data-action="assign"
                    data-item-id="{{ $item['id'] ?? '' }}"
                    aria-label="Assign {{ $item['title'] ?? 'task' }}"
                    tabindex="0">
              {{ __('dashboard.assign') ?? 'Assign' }}
            </button>
          @endif
          @if(hasPermission('reschedule_tasks') ?? true)
            <button class="btn-action btn-reschedule"
                    data-action="reschedule"
                    data-item-id="{{ $item['id'] ?? '' }}"
                    aria-label="Reschedule {{ $item['title'] ?? 'task' }}"
                    tabindex="0">
              {{ __('dashboard.reschedule') ?? 'Reschedule' }}
            </button>
          @endif
          @if(hasPermission('contact_customers') ?? true)
            <button class="btn-action btn-contact"
                    data-action="contact"
                    data-item-id="{{ $item['id'] ?? '' }}"
                    aria-label="Contact customer for {{ $item['title'] ?? 'task' }}"
                    tabindex="0">
              {{ __('dashboard.contact') ?? 'Contact' }}
            </button>
          @endif
        </div>
      </div>
    @empty
      <!-- Sample data for development -->
      <div class="queue-item priority-high" role="listitem" data-priority="high" data-type="pickup" tabindex="0">
        <div class="item-priority" aria-label="Priority: High">High</div>
        <div class="item-content">
          <h4 class="item-title">Pickup Request #12345</h4>
          <p class="item-details">Customer: John Doe - Address: 123 Main St</p>
          <div class="item-meta">
            <span class="item-due">Due: 2 hours</span>
            <span class="item-type">Pickup</span>
          </div>
        </div>
        <div class="item-actions" role="group" aria-label="Quick actions for Pickup Request #12345">
          <button class="btn-action btn-assign" data-action="assign" data-item-id="12345" aria-label="Assign Pickup Request #12345" tabindex="0">Assign</button>
          <button class="btn-action btn-reschedule" data-action="reschedule" data-item-id="12345" aria-label="Reschedule Pickup Request #12345" tabindex="0">Reschedule</button>
          <button class="btn-action btn-contact" data-action="contact" data-item-id="12345" aria-label="Contact customer for Pickup Request #12345" tabindex="0">Contact</button>
        </div>
      </div>

      <div class="queue-item priority-medium" role="listitem" data-priority="medium" data-type="delivery" tabindex="0">
        <div class="item-priority" aria-label="Priority: Medium">Medium</div>
        <div class="item-content">
          <h4 class="item-title">Delivery #67890</h4>
          <p class="item-details">Customer: Jane Smith - Address: 456 Oak Ave</p>
          <div class="item-meta">
            <span class="item-due">Due: 4 hours</span>
            <span class="item-type">Delivery</span>
          </div>
        </div>
        <div class="item-actions" role="group" aria-label="Quick actions for Delivery #67890">
          <button class="btn-action btn-assign" data-action="assign" data-item-id="67890" aria-label="Assign Delivery #67890" tabindex="0">Assign</button>
          <button class="btn-action btn-reschedule" data-action="reschedule" data-item-id="67890" aria-label="Reschedule Delivery #67890" tabindex="0">Reschedule</button>
          <button class="btn-action btn-contact" data-action="contact" data-item-id="67890" aria-label="Contact customer for Delivery #67890" tabindex="0">Contact</button>
        </div>
      </div>

      <div class="queue-item priority-low" role="listitem" data-priority="low" data-type="exception" tabindex="0">
        <div class="item-priority" aria-label="Priority: Low">Low</div>
        <div class="item-content">
          <h4 class="item-title">Exception Handling #11111</h4>
          <p class="item-details">Issue: Damaged package - Customer: Bob Wilson</p>
          <div class="item-meta">
            <span class="item-due">Due: Tomorrow</span>
            <span class="item-type">Exception</span>
          </div>
        </div>
        <div class="item-actions" role="group" aria-label="Quick actions for Exception Handling #11111">
          <button class="btn-action btn-assign" data-action="assign" data-item-id="11111" aria-label="Assign Exception Handling #11111" tabindex="0">Assign</button>
          <button class="btn-action btn-reschedule" data-action="reschedule" data-item-id="11111" aria-label="Reschedule Exception Handling #11111" tabindex="0">Reschedule</button>
          <button class="btn-action btn-contact" data-action="contact" data-item-id="11111" aria-label="Contact customer for Exception Handling #11111" tabindex="0">Contact</button>
        </div>
      </div>

      <div class="queue-item priority-high" role="listitem" data-priority="high" data-type="customer-service" tabindex="0">
        <div class="item-priority" aria-label="Priority: High">High</div>
        <div class="item-content">
          <h4 class="item-title">Customer Complaint #22222</h4>
          <p class="item-details">Issue: Late delivery - Customer: Alice Brown</p>
          <div class="item-meta">
            <span class="item-due">Due: 1 hour</span>
            <span class="item-type">Customer Service</span>
          </div>
        </div>
        <div class="item-actions" role="group" aria-label="Quick actions for Customer Complaint #22222">
          <button class="btn-action btn-assign" data-action="assign" data-item-id="22222" aria-label="Assign Customer Complaint #22222" tabindex="0">Assign</button>
          <button class="btn-action btn-reschedule" data-action="reschedule" data-item-id="22222" aria-label="Reschedule Customer Complaint #22222" tabindex="0">Reschedule</button>
          <button class="btn-action btn-contact" data-action="contact" data-item-id="22222" aria-label="Contact customer for Customer Complaint #22222" tabindex="0">Contact</button>
        </div>
      </div>
    @endforelse
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

      // Announce filter change to screen readers
      const announcement = `Filtered to show ${priority === 'all' ? 'all' : priority + ' priority'} items`;
      announceToScreenReader(announcement);
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
      const itemTitle = this.closest('.queue-item').querySelector('.item-title').textContent;

      // Placeholder for actual action handling
      console.log(`Action: ${action} for item ${itemId}: ${itemTitle}`);

      // Show loading state
      this.disabled = true;
      this.textContent = 'Processing...';

      // Simulate API call
      setTimeout(() => {
        this.disabled = false;
        this.textContent = action.charAt(0).toUpperCase() + action.slice(1);

        // Announce action completion
        const announcement = `${action.charAt(0).toUpperCase() + action.slice(1)} action completed for ${itemTitle}`;
        announceToScreenReader(announcement);
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
    announceToScreenReader('Workflow queue updated with new items');
  }

  // Initialize real-time updates
  setupRealTimeUpdates();
});
</script>
@endpush