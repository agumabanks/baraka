<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Error - {{ config('app.name', 'Baraka') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="container text-center">
        <div class="mb-4">
            <span class="badge bg-danger rounded-pill px-3 py-2">Error 500</span>
        </div>
        <h1 class="display-5 fw-bold mb-3">Something went wrong</h1>
        <p class="text-muted mb-4">
            An unexpected error occurred while processing your request.
            Our systems have logged this issue. Please try again in a moment.
        </p>
        <div class="d-flex justify-content-center gap-2">
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                Go Back
            </a>
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

