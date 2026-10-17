<!doctype html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Teatar</title>
    <style>
        :root { color-scheme: dark; }
        body { margin: 0; background: #110F10; color: #ECE6E3; font: 16px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { max-width: 680px; margin: 0 auto; padding: 32px 16px 64px; }
        h1 { font-size: 28px; line-height: 1.2; margin: 0 0 4px; }
        h2 { font-size: 19px; margin: 32px 0 8px; }
        p, li { color: #CFC6C2; }
        a { color: #E5707E; }
        .muted { color: #8E8481; font-size: 14px; }
        .brand { color: #E5707E; font-weight: 600; text-decoration: none; display: inline-block; margin-bottom: 24px; }
    </style>
</head>
<body>
<main>
    <span class="brand">Teatar</span>
    @yield('content')
</main>
</body>
</html>
