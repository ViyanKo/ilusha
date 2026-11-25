<?php
/** @var array $verifiedUser */
$firstName = $verifiedUser['first_name'] ?? '';
$email = $verifiedUser['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email подтверждён — Кондитерская</title>
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
                        <a class="header-list-link" href="/">Главная</a>
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
        <section class="login">
            <div class="login-container">
                <div class="login-text">
                    <h2 class="login-title">Email подтверждён</h2>
                    <p class="login-desc">
                        <?php if ($firstName !== ''): ?>
                            Спасибо, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!
                        <?php else: ?>
                            Спасибо!
                        <?php endif; ?>
                        Адрес <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?> успешно подтверждён.
                    </p>
                </div>

                <div class="registr-button">
                    <a class="login-button login-button--secondary" href="/">На главную</a>
                    <a class="login-button" href="/cab">В личный кабинет</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="footer-container">
            <a href="/">Прайс</a>
            <a href="/">Отзывы</a>
            <a href="/">Контакты</a>
        </div>

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
