<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 — Az oldal nem található</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/front.css') ?>?v=5">
</head>
<body>
<div class="error-page">
    <div class="error-code">404</div>
    <h1>Hoppá, ez az oldal nem található</h1>
    <p>Lehet, hogy törölték, átnevezték, vagy sosem létezett.</p>
    <a class="btn btn-primary" href="<?= base_url('/') ?>">Vissza a kezdőlapra</a>
</div>
</body>
</html>
