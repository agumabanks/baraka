<?php

use App\Models\DeliveryMan;
use App\Models\Shipment;
use App\Models\Backend\Branch;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('shipment.{id}', function ($user, $id) {
    $shipment = Shipment::find($id);

    return $user && $user->can('view', $shipment);
});

Broadcast::channel('driver.{id}', function ($user, $id) {
    return $user && $user->id === (int) $id && DeliveryMan::where('user_id', $user->id)->exists();
});

Broadcast::channel('track.{token}', function ($token) {
    // Public channel, signed token validation can be done in event or elsewhere
    return true;
});

/*
|--------------------------------------------------------------------------
| Operations Control Center Broadcasting Channels
|--------------------------------------------------------------------------
|
| Channels for real-time operations control center updates
|
*/

// Operations dashboard updates
Broadcast::channel('operations.dashboard', function ($user) {
    return $user && $user->hasAnyRole(['admin', 'operations_manager', 'supervisor']);
});

// Branch-specific dashboard updates
Broadcast::channel('operations.dashboard.branch.{branchId}', function ($user, $branchId) {
    if (!$user) {
        return false;
    }

    // Check if user has access to this branch
    $branch = Branch::find($branchId);
    if (!$branch) {
        return false;
    }

    // Admin and operations managers can access all branches
    if ($user->hasAnyRole(['admin', 'operations_manager'])) {
        return true;
    }

    // Branch managers and supervisors can access their own branch
    if ($user->hasAnyRole(['branch_manager', 'supervisor'])) {
        return $branch->branchManager && $branch->branchManager->user_id === $user->id;
    }

    // Workers can access their assigned branch
    $worker = $user->branchWorker;
    if ($worker) {
        return $worker->branch_id === (int) $branchId;
    }

    return false;
});

// Exception notifications
Broadcast::channel('operations.exceptions', function ($user) {
    return $user && $user->hasAnyRole(['admin', 'operations_manager', 'supervisor', 'branch_manager']);
});

// Branch-specific exception notifications
Broadcast::channel('operations.exceptions.branch.{branchId}', function ($user, $branchId) {
    if (!$user) {
        return false;
    }

    // Check if user has access to this branch
    $branch = Branch::find($branchId);
    if (!$branch) {
        return false;
    }

    // Admin and operations managers can access all branches
    if ($user->hasAnyRole(['admin', 'operations_manager'])) {
        return true;
    }

    // Branch managers and supervisors can access their own branch
    if ($user->hasAnyRole(['branch_manager', 'supervisor'])) {
        return $branch->branchManager && $branch->branchManager->user_id === $user->id;
    }

    return false;
});

// Operational alerts
Broadcast::channel('operations.alerts', function ($user) {
    return $user && $user->hasAnyRole(['admin', 'operations_manager', 'supervisor', 'branch_manager']);
});

// Branch-specific operational alerts
Broadcast::channel('operations.alerts.branch.{branchId}', function ($user, $branchId) {
    if (!$user) {
        return false;
    }

    // Check if user has access to this branch
    $branch = Branch::find($branchId);
    if (!$branch) {
        return false;
    }

    // Admin and operations managers can access all branches
    if ($user->hasAnyRole(['admin', 'operations_manager'])) {
        return true;
    }

    // Branch managers and supervisors can access their own branch
    if ($user->hasAnyRole(['branch_manager', 'supervisor'])) {
        return $branch->branchManager && $branch->branchManager->user_id === $user->id;
    }

    return false;
});

// User-specific alerts and notifications
Broadcast::channel('operations.alerts.user.{userId}', function ($user, $userId) {
    return $user && (int) $user->id === (int) $userId;
});

// Worker capacity alerts
Broadcast::channel('operations.worker.capacity', function ($user) {
    return $user && $user->hasAnyRole(['admin', 'operations_manager', 'supervisor', 'branch_manager']);
});

// Asset maintenance alerts
Broadcast::channel('operations.asset.maintenance', function ($user) {
    return $user && $user->hasAnyRole(['admin', 'operations_manager', 'supervisor', 'branch_manager']);
});

// Dispatch board updates
Broadcast::channel('operations.dispatch', function ($user) {
    return $user && $user->hasAnyRole(['admin', 'operations_manager', 'supervisor', 'branch_manager', 'dispatcher']);
});

// Branch-specific dispatch updates
Broadcast::channel('operations.dispatch.branch.{branchId}', function ($user, $branchId) {
    if (!$user) {
        return false;
    }

    // Check if user has access to this branch
    $branch = Branch::find($branchId);
    if (!$branch) {
        return false;
    }

    // Admin and operations managers can access all branches
    if ($user->hasAnyRole(['admin', 'operations_manager'])) {
        return true;
    }

    // Branch managers, supervisors, and dispatchers can access their own branch
    if ($user->hasAnyRole(['branch_manager', 'supervisor', 'dispatcher'])) {
        return $branch->branchManager && $branch->branchManager->user_id === $user->id;
    }

    // Workers can access their assigned branch
    $worker = $user->branchWorker;
    if ($worker) {
        return $worker->branch_id === (int) $branchId;
    }

    return false;
});
