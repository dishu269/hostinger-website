<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - <?= htmlspecialchars(SITE_NAME ?? 'Dishant Parihar Digital HQ') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="card text-center" style="max-width: 500px;">
            <h1 style="font-size: 4rem; margin: 0; color: var(--gold);">404</h1>
            <h2>Page Not Found</h2>
            <p class="text-muted">The page you're looking for doesn't exist or has been moved.</p>
            <div class="mt-3">
                <a href="/" class="btn">Go Home</a>
                <a href="javascript:history.back()" class="btn-outline ml-2">Go Back</a>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.2/feather.min.js"></script>
    <script>feather.replace();</script>
</body>
</html>