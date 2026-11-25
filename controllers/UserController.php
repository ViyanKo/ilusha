<?php
require_once __DIR__ . "/../service/UserService.php";
require_once __DIR__ . "/../service/EmailService.php";

class UserController
{
    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            require __DIR__ . "/../public/pages/registration.html";
            return;
        }

        $firstName = trim((string) (filter_input(INPUT_POST, 'first_name', FILTER_DEFAULT) ?? ''));
        $lastName = trim((string) (filter_input(INPUT_POST, 'last_name', FILTER_DEFAULT) ?? ''));
        $email = trim((string) (filter_input(INPUT_POST, 'email', FILTER_DEFAULT) ?? ''));
        $phone = trim((string) (filter_input(INPUT_POST, 'phone', FILTER_DEFAULT) ?? ''));
        $password = (string) (filter_input(INPUT_POST, 'password', FILTER_DEFAULT) ?? '');

        try {
            $userService = new UserService();
            $user = $userService->createUser($firstName, $lastName, $email, $password, $phone);
            $verification = $userService->issueVerifyToken((int) $user['id']);
            $emailService = new EmailService();
            $emailService->sendVerificationEmail($user, $verification['token']);

            header('Location: /cab');
            exit;
        } catch (ApiError $e) {
            if ($e->getStatusCode() === 409) {
                $loginError = $e->getMessage();
                require __DIR__ . "/../public/pages/registration.html";
                return;
            }

            if ($e->getStatusCode() !== 500) {
                $error = $e;
                require __DIR__ . "/../public/pages/error.php";
                return;
            }

            error_log('register: ' . $e->getMessage());
            $loginError = 'Произошла ошибка при регистрации';
            require __DIR__ . "/../public/pages/registration.html";
            return;
        } catch (Throwable $e) {
            error_log('register error: ' . $e->getMessage());
            $loginError = 'Произошла ошибка при регистрации';
            require __DIR__ . "/../public/pages/registration.html";
        }


    }

    public function login()
    {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            header('Location: /');
            exit;
        }

        $loginEmail = trim((string) (filter_input(INPUT_POST, 'login', FILTER_VALIDATE_EMAIL) ?: ''));
        $password   = (string) (filter_input(INPUT_POST, 'password', FILTER_DEFAULT) ?? '');
        $remember   = filter_input(INPUT_POST, 'remember', FILTER_VALIDATE_BOOLEAN) ?? false;
        $ip         = UserService::clientIp();

        try {
            $userService = new UserService();
            $user = $userService->authenticate($loginEmail, $password);

            $userService->resetFailedLogins($ip);

            $authToken = $userService->issueAuthToken((int) $user['id']);
            $this->setAuthCookie($authToken, $remember);

            header('Location: /cab');
            exit;
        } catch (ApiError $e) {
            $status = $e->getStatusCode();

            if ($status !== 400 && $status !== 401) {
                throw $e;
            }

            $userService->registerFailedLogin($ip);
            $userService->assertIpNotBlocked($ip);

            $loginError = $e->getMessage();
            require __DIR__ . "/../public/pages/index.php";
        } catch (Throwable $e) {
            error_log('login error: ' . $e->getMessage());
            $loginError = 'Произошла ошибка при входе';
            require __DIR__ . "/../public/pages/index.php";
        }
    }

    public function verify()
    {
        $token = trim((string) (filter_input(INPUT_GET, 'token', FILTER_DEFAULT) ?? ''));

        try {
            $userService = new UserService();
            $user = $userService->verifyToken($token);
            require __DIR__ . "/../public/pages/verify-success.php";
        } catch (ApiError $e) {
            $error = $e;
            require __DIR__ . "/../public/pages/error.php";
        } catch (Throwable $e) {
            error_log('verify: ' . $e->getMessage());
            $error = new ApiError('ошибка при подтверждении email', 500);
            require __DIR__ . "/../public/pages/error.php";
        }
    }

    public function cab()
    {
        try {
            $userService = new UserService();
            $user = $userService->getUserByAuthToken($this->authToken());
            require __DIR__ . "/../public/pages/cab.php";
        } catch (Throwable $e) {
            header('Location: /');
            exit;
        }
    }

    public function uploadPhoto()
    {
        try {
            $userService = new UserService();
            $user = $userService->getUserByAuthToken($this->authToken());
        } catch (Throwable $e) {
            header('Location: /');
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header('Location: /cab');
            exit;
        }

        try {
            $file = $_FILES['photo'] ?? [];

            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                throw new ApiError('Файл не был загружен', 400);
            }

            $size = isset($file['size']) ? (int) $file['size'] : 0;
            if ($size <= 0 || $size > 5 * 1024 * 1024) {
                throw new ApiError('Размер файла не должен превышать 5 МБ', 400);
            }

            $mime = mime_content_type($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if ($mime === false || !isset($allowed[$mime])) {
                throw new ApiError('Допустимы только изображения JPG, PNG, GIF и WebP', 400);
            }

            if (@getimagesize($file['tmp_name']) === false) {
                throw new ApiError('Файл не является изображением', 400);
            }

            $originalName = (string) ($file['name'] ?? '');
            if ($originalName === '') {
                throw new ApiError('Не указано имя файла', 400);
            }

            $extension = $allowed[$mime];
            $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
            $uploadDir = __DIR__ . "/../public/loaded_img";
            $path = $uploadDir . '/' . $fileName;

            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new ApiError('Не удалось создать каталог для загрузки', 500);
            }

            if (!is_writable($uploadDir)) {
                throw new ApiError('Каталог для загрузки недоступен для записи', 500);
            }

            if (!move_uploaded_file($file['tmp_name'], $path)) {
                throw new ApiError('Не удалось сохранить файл', 500);
            }

            $userService->updatePhoto((int) $user['id'], '/loaded_img/' . $fileName);

            header('Location: /cab');
            exit;
        } catch (ApiError $e) {
            header('Location: /cab?photo_error=' . urlencode($e->getMessage()));
            exit;
        } catch (Throwable $e) {
            error_log('upload photo: ' . $e->getMessage());
            header('Location: /cab?photo_error=' . urlencode('Произошла ошибка при загрузке изображения'));
            exit;
        }
    }

    public function checkEmail(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER["REQUEST_METHOD"] !== "POST") {
                http_response_code(405);
                echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $email = trim((string) (filter_input(INPUT_POST, 'email', FILTER_DEFAULT) ?? ''));

            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Введите корректный email'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $userService = new UserService();
            $exists = $userService->emailExists($email);

            echo json_encode(['exists' => $exists], JSON_UNESCAPED_UNICODE);
        } catch (ApiError $e) {
            http_response_code($e->getStatusCode());
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('check email: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Ошибка при проверке email'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function logout()
    {
        $this->revokeAuthCookie();
        header('Location: /');
        exit;
    }

    private function setAuthCookie(string $token, bool $remember): void
    {
        $lifetime = $remember ? 30 * 24 * 60 * 60 : 0;
        setcookie('auth_token', $token, [
            'expires' => $lifetime > 0 ? time() + $lifetime : 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function authToken(): string
    {
        return trim((string) ($_COOKIE['auth_token'] ?? ''));
    }

    private function revokeAuthCookie(): void
    {
        $token = $this->authToken();

        if ($token !== '') {
            try {
                $userService = new UserService();
                $userService->revokeAuthToken($token);
            } catch (Throwable $e) {
                error_log('logout: ' . $e->getMessage());
            }
        }

        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
