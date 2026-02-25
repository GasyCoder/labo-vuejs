<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ \App\Models\Setting::getNomEntreprise() ?: config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ \App\Models\Setting::getFavicon() }}">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', 'resources/js/scripts.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased text-default bg-slate-50 text-slate-700 dark:bg-slate-900 dark:text-slate-100 min-h-screen">
        @inertia
    </body>
</html>
