@foreach (notifications() as $notify)
<a href=" @if ($notify['type'] === 'support') {{ route('support.view', $notify['support_id']) }}
        @elseif($notify['type'] === 'newsoffer') {{ route('news-offer.index') }} @endif"
    class="list-group-item list-group-item-action active">
    <div class="notification-info">
        <div class="notification-list-user-img">
            <img src="{{ singleUser($notify['user_id'])->image }}"
                class="user-avatar-md rounded-circle">
        </div>
        <div class="notification-list-user-block">
            <span class="notification-list-user-name">
                {{ singleUser($notify['user_id'])->name }}
            </span>
            {{ $notify['subject'] }}
            <div class="notification-date">
                {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $notify['created_at'])->diffForHumans() }}
            </div>
        </div>
    </div>
</a>
@endforeach