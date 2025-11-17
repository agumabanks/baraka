@extends('settings.layouts.app')

@section('title', 'Settings Overview')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Overview</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Settings Overview</h1>
            <p class="text-muted mb-0">Manage your application settings and configurations</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-outline-primary">
                <i class="fas fa-download me-2"></i>Export Settings
            </button>
            <button class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Setting
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- System Status -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="settings-card">
                <div class="settings-card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-server text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="settings-card-title">System Status</h5>
                    <div class="mb-2">
                        <span class="badge bg-success fs-6">Online</span>
                    </div>
                    <p class="text-muted small">All systems operational</p>
                    <a href="{{ route('settings.system') }}" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
            </div>
        </div>

        <!-- Database -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="settings-card">
                <div class="settings-card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-database text-info" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="settings-card-title">Database</h5>
                    <div class="mb-2">
                        <span class="badge bg-success fs-6">Healthy</span>
                    </div>
                    <p class="text-muted small">Connection stable</p>
                    <a href="{{ route('settings.system') }}" class="btn btn-sm btn-outline-info">Manage</a>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="settings-card">
                <div class="settings-card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-bell text-warning" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="settings-card-title">Notifications</h5>
                    <div class="mb-2">
                        <span class="badge bg-warning fs-6">3 New</span>
                    </div>
                    <p class="text-muted small">Pending alerts</p>
                    <a href="{{ route('settings.notifications') }}" class="btn btn-sm btn-outline-warning">Configure</a>
                </div>
            </div>
        </div>

        <!-- Integrations -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="settings-card">
                <div class="settings-card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-plug text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    <h5 class="settings-card-title">Integrations</h5>
                    <div class="mb-2">
                        <span class="badge bg-success fs-6">Active</span>
                    </div>
                    <p class="text-muted small">5 services connected</p>
                    <a href="{{ route('settings.integrations') }}" class="btn btn-sm btn-outline-success">Manage</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="settings-card">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Quick Actions
                    </h5>
                </div>
                <div class="settings-card-body">
                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="me-3">
                                    <i class="fas fa-palette text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Customize Branding</h6>
                                    <p class="text-muted small mb-2">Update logo, colors, and appearance</p>
                                    <a href="{{ route('settings.branding') }}" class="btn btn-sm btn-outline-primary">Configure</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="me-3">
                                    <i class="fas fa-cogs text-info"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Operations Setup</h6>
                                    <p class="text-muted small mb-2">Configure workflows and processes</p>
                                    <a href="{{ route('settings.operations') }}" class="btn btn-sm btn-outline-info">Manage</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="me-3">
                                    <i class="fas fa-dollar-sign text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Finance Settings</h6>
                                    <p class="text-muted small mb-2">Payment methods and billing</p>
                                    <a href="{{ route('settings.finance') }}" class="btn btn-sm btn-outline-success">Setup</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="me-3">
                                    <i class="fas fa-globe text-secondary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Website Configuration</h6>
                                    <p class="text-muted small mb-2">Manage website settings</p>
                                    <a href="{{ route('settings.website') }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="me-3">
                                    <i class="fas fa-shield-alt text-danger"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Security Settings</h6>
                                    <p class="text-muted small mb-2">Authentication and security</p>
                                    <a href="{{ route('settings.system') }}#security" class="btn btn-sm btn-outline-danger">Secure</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="me-3">
                                    <i class="fas fa-users text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">User Management</h6>
                                    <p class="text-muted small mb-2">Manage user accounts</p>
                                    <a
                                        href="{{ \Illuminate\Support\Facades\Route::has('users.index') ? route('users.index') : url('/dashboard/settings/users') }}"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Manage
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-lg-6">
            <div class="settings-card">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-history me-2 text-info"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="settings-card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <h6 class="mb-1">System backup completed</h6>
                                <p class="text-muted small mb-0">All settings backed up successfully</p>
                            </div>
                            <small class="text-muted">2 hours ago</small>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <h6 class="mb-1">Email settings updated</h6>
                                <p class="text-muted small mb-0">SMTP configuration modified</p>
                            </div>
                            <small class="text-muted">5 hours ago</small>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <h6 class="mb-1">New integration added</h6>
                                <p class="text-muted small mb-0">Stripe payment gateway connected</p>
                            </div>
                            <small class="text-muted">1 day ago</small>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                            <div>
                                <h6 class="mb-1">Security audit completed</h6>
                                <p class="text-muted small mb-0">No vulnerabilities found</p>
                            </div>
                            <small class="text-muted">2 days ago</small>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a
                            href="{{ \Illuminate\Support\Facades\Route::has('logs.index') ? route('logs.index') : url('/dashboard/logs') }}"
                            class="btn btn-sm btn-outline-primary"
                        >
                            View All Activity
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="settings-card">
                <div class="settings-card-header">
                    <h5 class="settings-card-title">
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                        System Alerts
                    </h5>
                </div>
                <div class="settings-card-body">
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3"></i>
                        <div>
                            <strong>Database Connection Warning:</strong>
                            <br>
                            <small>Connection pool is at 80% capacity. Consider increasing limits.</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fas fa-info-circle me-3"></i>
                        <div>
                            <strong>Update Available:</strong>
                            <br>
                            <small>New version 2.1.3 is available for installation.</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="fas fa-check-circle me-3"></i>
                        <div>
                            <strong>Backup Status:</strong>
                            <br>
                            <small>Last backup completed successfully 2 hours ago.</small>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('settings.system') }}#alerts" class="btn btn-sm btn-outline-primary">
                            Manage Alerts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .settings-card-body .border {
            transition: all 0.2s ease;
        }
        
        .settings-card-body .border:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .list-group-item h6 {
            color: #495057;
        }
        
        .list-group-item h6:hover {
            color: var(--primary-color);
        }
    </style>
@endpush
