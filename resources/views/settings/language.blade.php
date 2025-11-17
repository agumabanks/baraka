@extends('settings.layouts.app')

@section('title', 'Language & Translations')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Language &amp; Translations</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-language me-2 text-primary"></i>
                Language &amp; Translations
            </h1>
            <p class="text-muted mb-0">
                Control the default interface language and fine-tune key phrases across English, French, and Swahili.
            </p>
        </div>
        <div class="page-actions">
            <button type="submit" form="languageSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form
        id="languageSettingsForm"
        method="POST"
        action="{{ route('settings.language.update') }}"
        class="ajax-form settings-form-enhanced"
    >
        @csrf

        <input type="hidden" name="active_locale" value="{{ $activeLocale }}">

        <x-settings.card
            title="Default Language"
            subtitle="Choose the primary language used across the system"
            :icon="'fas fa-globe-africa'"
        >
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Interface default</label>
                        <div class="btn-group" role="group" aria-label="Default language">
                            @foreach($locales as $code => $label)
                                <input
                                    type="radio"
                                    class="btn-check"
                                    name="default_locale"
                                    id="default-locale-{{ $code }}"
                                    value="{{ $code }}"
                                    autocomplete="off"
                                    {{ $defaultLocale === $code ? 'checked' : '' }}
                                >
                                <label class="btn btn-outline-primary" for="default-locale-{{ $code }}">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <small class="form-text text-muted mt-2">
                            This is the language new users see by default. Individual users can still switch
                            their own language via the dashboard language switcher.
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 p-3 bg-light h-100 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            <span class="fw-semibold">Translation engine</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Translations are stored in a database-backed key/value system and served via
                            the <code>trans_db()</code> helper. This allows you to safely update copy without
                            redeploying code.
                        </p>
                        <p class="text-muted small mb-0">
                            Focus on keeping keys stable (e.g. <code>dashboard.title</code>) and adjust values
                            per language as needed.
                        </p>
                    </div>
                </div>
            </div>
        </x-settings.card>

        <x-settings.card
            title="Translation Keys"
            subtitle="Edit phrases for the active language"
            :icon="'fas fa-key'"
        >
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    @foreach($locales as $code => $label)
                        <a
                            href="{{ route('settings.language', ['language_code' => $code, 'q' => $search]) }}"
                            class="badge rounded-pill px-3 py-2 {{ $activeLocale === $code ? 'bg-primary text-white' : 'bg-light text-muted' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                <div class="mb-2">
                    <form
                        method="GET"
                        action="{{ route('settings.language') }}"
                        class="d-flex align-items-center gap-2"
                    >
                        <input type="hidden" name="language_code" value="{{ $activeLocale }}">
                        <input
                            type="search"
                            name="q"
                            class="form-control form-control-sm"
                            placeholder="Filter by key…"
                            value="{{ $search }}"
                            style="min-width: 220px;"
                        >
                        <button class="btn btn-outline-secondary btn-sm" type="submit">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-muted small mb-3">
                Showing <strong>{{ $translations->count() }}</strong> of
                <strong>{{ $totalCount }}</strong> keys for
                <strong>{{ $locales[$activeLocale] ?? strtoupper($activeLocale) }}</strong>.
            </p>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 32%;">Key</th>
                            <th>Value ({{ $locales[$activeLocale] ?? strtoupper($activeLocale) }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($translations as $translation)
                            <tr>
                                <td class="small text-muted">
                                    <code>{{ $translation->key }}</code>
                                </td>
                                <td>
                                    <textarea
                                        name="translations[{{ $translation->key }}]"
                                        rows="2"
                                        class="form-control form-control-sm"
                                    >{{ old("translations.{$translation->key}", $translation->value) }}</textarea>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted small">
                                    No translations found for this language yet. Use the row below to add the first key.
                                </td>
                            </tr>
                        @endforelse

                        <tr class="table-light">
                            <td>
                                <input
                                    type="text"
                                    name="new_translation[key]"
                                    class="form-control form-control-sm"
                                    placeholder="e.g. dashboard.subtitle"
                                    value="{{ old('new_translation.key') }}"
                                >
                            </td>
                            <td>
                                <textarea
                                    name="new_translation[value]"
                                    rows="2"
                                    class="form-control form-control-sm"
                                    placeholder="New translation value for {{ $locales[$activeLocale] ?? strtoupper($activeLocale) }}"
                                >{{ old('new_translation.value') }}</textarea>
                                <small class="form-text text-muted">
                                    Leave both fields blank to skip adding a new key.
                                </small>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if($translations instanceof \Illuminate\Contracts\Pagination\Paginator)
                <div class="mt-3">
                    {{ $translations->links() }}
                </div>
            @endif
        </x-settings.card>
    </form>
@endsection
