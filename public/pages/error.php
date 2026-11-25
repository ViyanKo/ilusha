<?php
require_once __DIR__ . '/../../error/ApiError.php';

if (isset($error) && $error instanceof ApiError) {
    $status = $error->getStatusCode();
    $message = $error->getMessage();
} else {
    $status = http_response_code() ?: 500;
    $message = isset($message) ? $message : 'Произошла ошибка';
}

http_response_code($status);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <div class="header-container">
            <nav class="header-nav">
                <a href="/" class="logo-link">
                    <img src="/img/logo.jpg" alt="Логотип кондитерской" class="logo">
                </a>
                <ul class="header-list">
                    <li class="header-list-item">
                        <a class="header-list-link active" href="/">Главная</a>
                    </li>
                    <li class="header-list-item">
                        <a class="header-list-link" href="/">Прайс</a>
                    </li>
                    <li class="header-list-item">
                        <a class="header-list-link" href="/">Отзывы</a>
                    </li>
                    <li class="header-list-item">
                        <a class="header-list-link" href="/">Контакты</a>
                    </li>
                </ul>
                <a class="header-button" href="/">Заказать</a>
                <button class="burger" aria-label="Открыть меню" type="button">
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <main>
        <section class="error-page">
            <div class="error-page-container">
                <h1 class="error-page-title">Ошибка <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="error-page-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
                <a class="error-page-link" href="/">Вернуться на главную</a>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p class="footer-copy">All rights reserved, 2026</p>
        <div class="footer-container">
            <a href="/">Вконтакте</a>
            <a href="/">Whats Up</a>
            <a href="/">Max</a>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
