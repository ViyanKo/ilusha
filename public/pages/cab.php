<?php 
if (!isset($user) || !is_array($user)) {
    header('Location: /register');
    exit;
}

$photoUrl = $photoUrl ?? null;
$photoSuccess = $photoSuccess ?? false;
$photoError = $photoError ?? '';
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

                <table class="profile-card">
                    <tr>
                        <td class="profile-avatar<?php echo $photoUrl ? ' profile-avatar--filled' : ''; ?>">
                            <?php if ($photoUrl): ?>
                                <img class="profile-avatar-image"
                                     src="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                     alt="Фото пользователя">
                            <?php else: ?>
                                <span class="profile-avatar-text">Здесь будет ваше изображение</span>
                            <?php endif; ?>

                            <form class="photo-upload-form"
                                  action="/cab/photo"
                                  method="POST"
                                  enctype="multipart/form-data"
                                  id="photo_form">
                                <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
                                <input class="photo-upload-input"
                                       type="file"
                                       name="photo"
                                       id="photo"
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       required>
                                <label class="login-button photo-upload-button" for="photo">Загрузить</label>
                                <p class="hint photo-upload-hint">JPG, PNG, GIF или WebP, размер не более 5 МБ</p>
                                <p class="photo-upload-message" id="photo_message" role="alert"></p>

                                <?php if ($photoSuccess): ?>
                                    <p class="form-message form-message--success">Изображение успешно загружено</p>
                                <?php endif; ?>

                                <?php if ($photoError !== ''): ?>
                                    <p class="form-message form-message--error"><?php echo htmlspecialchars($photoError, ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </form>
                        </td>
                        <td class="profile-info">
                            <p class="profile-hello">
                                Добро пожаловать, <strong><?php echo htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name); ?></strong>
                            </p>

                            <a class="login-button login-button--secondary profile-logout" href="/logout">Выйти из приложения</a>

                            <?php
                                $check = (int) ($user['check_email'] ?? 0);
                                if ($check !== 1) {
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
                        <td id="id_u"><?php echo htmlspecialchars($id); ?></td>
                    </tr>
                    <tr>
                        <td>Имя</td>
                        <td><?php echo htmlspecialchars($first_name); ?></td>
                    </tr>
                    <tr>
                        <td>Фамилия</td>
                        <td><?php echo htmlspecialchars($last_name); ?></td>
                    </tr>
                    <tr>
                        <td>Электронная почта</td>
                        <td><?php echo htmlspecialchars($email); ?></td>
                    </tr>
                    <tr>
                        <td>Телефон</td>
                        <td><?php echo htmlspecialchars($phone); ?></td>
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