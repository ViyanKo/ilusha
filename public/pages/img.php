<?php
$images = $images ?? [];
$success = $success ?? null;
$error = $error ?? null;
$formAction = $formAction ?? '/img';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Галерея — Кондитерская</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/styles.css">
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
                    <h2 class="login-title">Галерея</h2>
                    <p class="login-desc">Загрузите изображение и посмотрите все фотографии из папки loaded_img</p>
                </div>

                <?php if (!empty($success)): ?>
                    <p class="form-message form-message--success"><?php echo htmlspecialchars($success); ?></p>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <p class="form-message form-message--error"><?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <form class="login-form upload-form"
                      action="<?php echo htmlspecialchars($formAction, ENT_QUOTES); ?>"
                      method="POST"
                      enctype="multipart/form-data">

                    <label class="register-label" for="image">Изображение</label>
                    <input class="login-input login-input--file"
                           type="file"
                           name="image"
                           id="image"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           required>
                    <p class="hint">JPG, PNG, GIF или WebP, размер не более 5 МБ</p>

                    <div class="registr-button">
                        <button type="submit" class="login-button upload-button">Загрузить</button>
                    </div>
                </form>

                <p class="profile-subtitle">Все загруженные изображения</p>

                <?php if (empty($images)): ?>
                    <p class="gallery-empty">Пока нет загруженных изображений</p>
                <?php else: ?>
                    <div class="gallery">
                        <?php foreach ($images as $image): ?>
                            <?php $imageName = htmlspecialchars($image, ENT_QUOTES); ?>
                            <figure class="gallery-item">
                                <a href="/loaded_img/<?php echo rawurlencode($image); ?>" target="_blank" rel="noopener">
                                    <img src="/loaded_img/<?php echo rawurlencode($image); ?>"
                                         alt="<?php echo $imageName; ?>"
                                         loading="lazy">
                                </a>
                                <figcaption><?php echo $imageName; ?></figcaption>
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
