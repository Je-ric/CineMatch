<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Movie Recommendation System' }}</title>
    @include('partials.links')
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: "Oswald", sans-serif;
            color: #fff;
        }
    </style>
</head>
<body class="min-h-screen text-text-primary bg-primary-bg">
    @include('partials.includes.header')
    <div class="min-h-screen">
        {{ $slot }}
    </div>
    @include('partials.includes.footer')

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

    @stack('scripts')
</body>
</html>
