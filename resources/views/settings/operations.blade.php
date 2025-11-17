@extends('settings.layouts.app')

@section('title', 'Operations Settings')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Operations</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-cogs me-2 text-primary"></i>
                Operations
            </h1>
            <p class="text-muted mb-0">Workflow automation and file handling</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="operationsSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="operationsSettingsForm" method="POST" action="{{ route('settings.operations.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="File Handling" subtitle="Control limits and supported file types">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="max_file_size" class="form-label fw-semibold">Max File Size (KB)</label>
                        <input
                            type="number"
                            id="max_file_size"
                            name="max_file_size"
                            class="form-control @error('max_file_size') is-invalid @enderror"
                            value="{{ old('max_file_size', $settings['max_file_size'] ?? 10240) }}"
                            min="1024"
                            max="102400"
                        >
                        @error('max_file_size')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Allowed File Types</label>
                        <input
                            type="text"
                            name="allowed_file_types[]"
                            class="form-control"
                            value="{{ implode(',', $settings['allowed_file_types'] ?? ['jpg','jpeg','png','gif','pdf']) }}"
                            readonly
                        >
                        <small class="form-text text-muted">
                            Full control is available via configuration; this display is read-only for production safety.
                        </small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <x-settings.toggle
                        name="auto_backup"
                        label="Automatic Backups"
                        :checked="old('auto_backup', $settings['auto_backup'] ?? true)"
                        help="Run scheduled database and configuration backups."
                        icon="fas fa-database"
                    />
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="backup_frequency" class="form-label fw-semibold">Backup Frequency</label>
                        <select
                            id="backup_frequency"
                            name="backup_frequency"
                            class="form-select @error('backup_frequency') is-invalid @enderror"
                        >
                            @foreach(['hourly','daily','weekly','monthly'] as $option)
                                <option value="{{ $option }}" {{ old('backup_frequency', $settings['backup_frequency'] ?? 'daily') === $option ? 'selected' : '' }}>
                                    {{ ucfirst($option) }}
                                </option>
                            @endforeach
                        </select>
                        @error('backup_frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label for="maintenance_window" class="form-label fw-semibold">Maintenance Window (24h)</label>
                        <input
                            type="time"
                            id="maintenance_window"
                            name="maintenance_window"
                            class="form-control @error('maintenance_window') is-invalid @enderror"
                            value="{{ old('maintenance_window', $settings['maintenance_window'] ?? '02:00') }}"
                        >
                        @error('maintenance_window')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>
    </form>
@endsection
