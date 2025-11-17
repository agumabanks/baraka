@extends('settings.layouts.app')

@section('title', 'Website Settings')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Website</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-globe me-2 text-primary"></i>
                Website
            </h1>
            <p class="text-muted mb-0">Public site metadata and SEO configuration</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="websiteSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="websiteSettingsForm" method="POST" action="{{ route('settings.website.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="Meta & SEO" subtitle="Control how Baraka appears publicly">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="site_title" class="form-label fw-semibold">Site Title</label>
                        <input
                            type="text"
                            id="site_title"
                            name="site_title"
                            class="form-control @error('site_title') is-invalid @enderror"
                            value="{{ old('site_title', $settings['site_title'] ?? 'Baraka Sanaa') }}"
                        >
                        @error('site_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label for="google_analytics_id" class="form-label fw-semibold">Google Analytics ID</label>
                        <input
                            type="text"
                            id="google_analytics_id"
                            name="google_analytics_id"
                            class="form-control @error('google_analytics_id') is-invalid @enderror"
                            value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}"
                        >
                        @error('google_analytics_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="site_description" class="form-label fw-semibold">Site Description</label>
                <textarea
                    id="site_description"
                    name="site_description"
                    rows="3"
                    class="form-control @error('site_description') is-invalid @enderror"
                >{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
                @error('site_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="site_keywords" class="form-label fw-semibold">Keywords</label>
                <textarea
                    id="site_keywords"
                    name="site_keywords"
                    rows="2"
                    class="form-control @error('site_keywords') is-invalid @enderror"
                >{{ old('site_keywords', $settings['site_keywords'] ?? '') }}</textarea>
                @error('site_keywords')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Comma-separated keywords that describe your services.</small>
            </div>
        </x-settings.card>

        <x-settings.card title="Robots & Sitemap" subtitle="Fine-tune how search engines crawl your site">
            <div class="mb-4">
                <label for="robots_txt" class="form-label fw-semibold">robots.txt</label>
                <textarea
                    id="robots_txt"
                    name="robots_txt"
                    rows="4"
                    class="form-control @error('robots_txt') is-invalid @enderror"
                >{{ old('robots_txt', $settings['robots_txt'] ?? '') }}</textarea>
                @error('robots_txt')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">Control which parts of your site search engines can crawl.</small>
            </div>

            <x-settings.toggle
                name="sitemap_enabled"
                label="Enable Sitemap"
                :checked="old('sitemap_enabled', $settings['sitemap_enabled'] ?? true)"
                help="Generate and expose an XML sitemap for search engines."
                icon="fas fa-sitemap"
            />
        </x-settings.card>
    </form>
@endsection
