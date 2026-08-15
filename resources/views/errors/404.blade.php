<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <p class="text-8xl font-black text-gray-200">404</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-800">Page not found</h1>
        <p class="mt-2 text-gray-500">The page you're looking for doesn't exist or has been moved.</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : '/' }}"
           class="mt-6 inline-flex items-center gap-2 rounded bg-gray-900 px-5 py-2 text-sm font-medium text-white hover:bg-gray-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Go Back
        </a>
    </div>
</body>
</html>
