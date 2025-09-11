<?php
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error - <?= htmlspecialchars(SITE_NAME ?? 'Dishant Parihar Digital HQ') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
        <div class="card text-center" style="max-width: 500px;">
            <h1 style="font-size: 4rem; margin: 0; color: var(--error);">500</h1>
            <h2>Server Error</h2>
            <p class="text-muted">Something went wrong on our end. We're working to fix it.</p>
            <div class="mt-3">
                <a href="/" class="btn">Go Home</a>
                <a href="javascript:location.reload()" class="btn-outline ml-2">Try Again</a>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.2/feather.min.js"></script>
    <script>feather.replace();</script>
</body>
</html>