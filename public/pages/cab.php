<?php 
require_once __DIR__ . "/service/UserService.php";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет — Кондитерская</title>
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
                <?php
                /** @var array $user */
                    $id = $user['id'];
                    $first_name = $user['first_name'];
                    $last_name = $user['last_name'];
                    $email = $user['email'];
                    $phone = $user['phone'];
                ?>

                <!-- Верхний блок: аватар слева, приветствие справа, всё в одну строку -->
                <table class="profile-card">
                    <tr>
                        <td class="profile-avatar">
                            Здесь будет ваше изображение
                        </td>
                        <td class="profile-info">
                            <p class="profile-hello">
                                Добро пожаловать, <strong><?php echo $first_name . ' ' . $last_name; ?></strong>
                            </p>

                            <a class="login-button login-button--secondary profile-logout" href="unautoriz.php">Выйти из приложения</a>

                            <?php
                                $check = 0;
                                if ($check != 1) {
                                    echo "<p class='error'>Адрес электронной почты не подтверждён, зайдите на почту. Аккаунт будет удалён в течение 30 дней в случае отсутствия подтверждения!</p>";
                                }
                            ?>
                        </td>
                    </tr>
                </table>

                <p class="profile-subtitle">Ваши регистрационные данные</p>

                <!-- Нижний блок: таблица данных -->
                <table class="profile-table">
                    <tr>
                        <td>ID</td>
                        <td id="id_u"></td>
                    </tr>
                    <tr>
                        <td>Имя</td>
                        <td><?php echo $first_name; ?></td>
                    </tr>
                    <tr>
                        <td>Фамилия</td>
                        <td><?php echo $last_name; ?></td>
                    </tr>
                    <tr>
                        <td>Электронная почта</td>
                        <td><?php echo $email; ?></td>
                    </tr>
                    <tr>
                        <td>Телефон</td>
                        <td><?php echo $phone; ?></td>
                    </tr>
                </table>
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