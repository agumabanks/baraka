@extends('settings.layouts.app')

@section('title', 'System Status')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">System</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-server me-2 text-primary"></i>
                System Status
            </h1>
            <p class="text-muted mb-0">Runtime environment and infrastructure diagnostics</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="systemSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="systemSettingsForm" method="POST" action="{{ route('settings.system.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="Environment Overview" subtitle="Key PHP and Laravel settings">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">PHP Version</label>
                        <input type="text" class="form-control" value="{{ $system['php_version'] ?? PHP_VERSION }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Laravel Version</label>
                        <input type="text" class="form-control" value="{{ $system['laravel_version'] ?? app()->version() }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Timezone</label>
                        <input type="text" class="form-control" value="{{ $system['timezone'] ?? date_default_timezone_get() }}" disabled>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Memory Limit</label>
                        <input type="text" class="form-control" value="{{ $system['memory_limit'] ?? ini_get('memory_limit') }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Max Execution Time</label>
                        <input type="text" class="form-control" value="{{ $system['max_execution_time'] ?? ini_get('max_execution_time') }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Upload Max Filesize</label>
                        <input type="text" class="form-control" value="{{ $system['upload_max_filesize'] ?? ini_get('upload_max_filesize') }}" disabled>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Database Connection</label>
                        <input type="text" class="form-control" value="{{ $system['database_connection'] ?? config('database.default') }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cache Driver</label>
                        <input type="text" class="form-control" value="{{ $system['cache_driver'] ?? config('cache.default') }}" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Queue Connection</label>
                        <input type="text" class="form-control" value="{{ $system['queue_connection'] ?? config('queue.default') }}" disabled>
                    </div>
                </div>
            </div>
        </x-settings.card>
    </form>
@endsection
