<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied - {{ config('app.name', 'Baraka') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="container text-center">
        <div class="mb-4">
            <span class="badge bg-danger rounded-pill px-3 py-2">Error 403</span>
        </div>
        <h1 class="display-5 fw-bold mb-3">Access denied</h1>
        <p class="text-muted mb-4">
            You do not have permission to access this resource.
        </p>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
            Back to Dashboard
        </a>
    </div>
</body>
</html>

