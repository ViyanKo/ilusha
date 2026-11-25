<?php
require_once __DIR__ . "/../error/ApiError.php";

class UserService
{
    private const MAX_LOGIN_ATTEMPTS = 3;
    private const LOGIN_BLOCK_SECONDS = 60;

    private function createConnect()
    {
        $connective = null;
        try {
            $connective = mysqli_connect("localhost", "kusminova_kusminova_4", "Alinakus12102007!", "kusminova_kusminova_4");
        } catch (mysqli_sql_exception $e) {
            error_log('db connect: ' . $e->getMessage());
            throw new ApiError('ошибка подключения к базе данных', 500);
        }

        if (!$connective) {
            error_log('db connect: ' . mysqli_connect_error());
            throw new ApiError('ошибка подключения к базе данных', 500);
        }

        mysqli_set_charset($connective, "utf8");

        return $connective;
    }

    private $db;
    public function __construct()
    {
        try {
            $this->db = $this->createConnect();
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('db connect: ' . $e->getMessage());
            throw new ApiError('ошибка подключения к базе данных', 500);
        }
    }

    public function createUser($first_name, $last_name, $email, $password, $phone): array
    {
        try {
            if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($phone)) {
                throw new ApiError('Все поля обязательны для заполнения', 400);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO user(first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)";

            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $email, $passwordHash, $phone);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                $errno = mysqli_stmt_errno($stmt);
                mysqli_stmt_close($stmt);

                if ($errno === 1062) {
                    throw new ApiError('Пользователь с таким email уже существует', 409);
                }

                error_log('db insert: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            $id = mysqli_stmt_insert_id($stmt);
            mysqli_stmt_close($stmt);

            return [
                'id' => $id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'password' => $passwordHash,
                'check_email' => 0
            ];
        } catch (ApiError $e) {
            throw $e;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new ApiError('Пользователь с таким email уже существует', 409);
            }

            error_log('db insert: ' . $e->getMessage());
            throw new ApiError('ошибка базы данных', 500);
        } catch (Throwable $e) {
            error_log('user create: ' . $e->getMessage());
            throw new ApiError('ошибка при создании пользователя', 500);
        }
    }

    public function updatePhoto(int $userId, string $foto): void
    {
        $foto = trim($foto);

        if ($userId <= 0 || $foto === '') {
            throw new ApiError('Некорректный путь к изображению', 400);
        }

        if (strlen($foto) > 255) {
            throw new ApiError('Слишком длинный путь к изображению', 400);
        }

        try {
            $query = "UPDATE user SET foto = ? WHERE id = ?";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "si", $foto, $userId);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db update user photo: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_close($stmt);
        } catch (ApiError $e) {
            throw $e;
        } catch (mysqli_sql_exception $e) {
            error_log('db update user photo: ' . $e->getMessage());
            throw new ApiError('ошибка базы данных', 500);
        } catch (Throwable $e) {
            error_log('update user photo: ' . $e->getMessage());
            throw new ApiError('ошибка при сохранении пути к изображению', 500);
        }
    }

    public function emailExists(string $email): bool
    {
        try {
            $query = "SELECT 1 FROM user WHERE email = ? LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "s", $email);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select email: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            if (!mysqli_stmt_store_result($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db store email result: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            $exists = mysqli_stmt_num_rows($stmt) > 0;

            mysqli_stmt_free_result($stmt);
            mysqli_stmt_close($stmt);

            return $exists;
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('email exists: ' . $e->getMessage());
            throw new ApiError('ошибка при проверке email', 500);
        }
    }

    public function issueVerifyToken(int $userId): array
    {
        try {
            $token = bin2hex(random_bytes(32));

            $deleteQuery = "DELETE FROM verify_token WHERE user_id = ?";
            $stmt = mysqli_prepare($this->db, $deleteQuery);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "i", $userId);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db delete token: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_close($stmt);

            $insertQuery = "INSERT INTO verify_token(token, user_id, exp_date) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
            $stmt = mysqli_prepare($this->db, $insertQuery);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "si", $token, $userId);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db insert token: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_close($stmt);

            return ['token' => $token];
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('issue verify token: ' . $e->getMessage());
            throw new ApiError('ошибка при создании токена подтверждения', 500);
        }
    }

    public function verifyToken(string $token): array
    {
        try {
            $token = trim($token);

            if ($token === '') {
                throw new ApiError('Не указан токен подтверждения', 400);
            }

            $query = "SELECT id, user_id, exp_date, (exp_date < NOW()) AS is_expired FROM verify_token WHERE token = ? LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "s", $token);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select token: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_result($stmt, $tokenId, $userId, $expDate, $isExpired);

            if (!mysqli_stmt_fetch($stmt)) {
                mysqli_stmt_close($stmt);
                throw new ApiError('Ссылка недействительна или уже использована', 400);
            }

            mysqli_stmt_close($stmt);

            $tokenId = (int) $tokenId;
            $userId = (int) $userId;

            if ($isExpired === null || (int) $isExpired === 1) {
                $this->deleteVerifyToken($tokenId);
                throw new ApiError('Срок действия ссылки истёк', 410);
            }

            mysqli_begin_transaction($this->db);

            try {
                $updateQuery = "UPDATE user SET check_email = 1 WHERE id = ?";
                $stmt = mysqli_prepare($this->db, $updateQuery);

                if (!$stmt) {
                    throw new RuntimeException(mysqli_error($this->db));
                }

                mysqli_stmt_bind_param($stmt, "i", $userId);

                if (!mysqli_stmt_execute($stmt)) {
                    $err = mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    throw new RuntimeException($err);
                }

                mysqli_stmt_close($stmt);

                $deleteQuery = "DELETE FROM verify_token WHERE id = ?";
                $stmt = mysqli_prepare($this->db, $deleteQuery);

                if (!$stmt) {
                    throw new RuntimeException(mysqli_error($this->db));
                }

                mysqli_stmt_bind_param($stmt, "i", $tokenId);

                if (!mysqli_stmt_execute($stmt)) {
                    $err = mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);
                    throw new RuntimeException($err);
                }

                mysqli_stmt_close($stmt);

                mysqli_commit($this->db);
            } catch (Throwable $e) {
                mysqli_rollback($this->db);
                error_log('db verify email: ' . $e->getMessage());
                throw new ApiError('ошибка базы данных', 500);
            }

            $query = "SELECT id, first_name, last_name, email FROM user WHERE id = ? LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "i", $userId);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select user: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_result($stmt, $id, $firstName, $lastName, $email);

            if (!mysqli_stmt_fetch($stmt)) {
                mysqli_stmt_close($stmt);
                throw new ApiError('Пользователь не найден', 404);
            }

            mysqli_stmt_close($stmt);

            return [
                'id' => (int) $id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email
            ];
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('verify token: ' . $e->getMessage());
            throw new ApiError('ошибка при подтверждении email', 500);
        }
    }

    public function authenticate(string $email, string $password): array
    {
        try {
            $email = trim($email);

            if ($email === '' || $password === '') {
                throw new ApiError('Введите email и пароль', 400);
            }

            $query = "SELECT id, first_name, last_name, email, phone, foto, password, check_email FROM user WHERE email = ? LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "s", $email);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select auth: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_result($stmt, $id, $firstName, $lastName, $userEmail, $phone, $foto, $passwordHash, $checkEmail);

            if (!mysqli_stmt_fetch($stmt)) {
                mysqli_stmt_close($stmt);
                throw new ApiError('Неверный email или пароль', 401);
            }

            mysqli_stmt_close($stmt);

            if (!password_verify($password, $passwordHash)) {
                throw new ApiError('Неверный email или пароль', 401);
            }

            return [
                'id' => (int) $id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $userEmail,
                'phone' => $phone,
                'foto' => $foto,
                'check_email' => (int) $checkEmail
            ];
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('authenticate: ' . $e->getMessage());
            throw new ApiError('ошибка при проверке учётных данных', 500);
        }
    }

    public function getUserById(int $userId): array
    {
        try {
            $query = "SELECT id, first_name, last_name, email, phone, foto, check_email FROM user WHERE id = ? LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "i", $userId);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select user: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_result($stmt, $id, $firstName, $lastName, $email, $phone, $foto, $checkEmail);

            if (!mysqli_stmt_fetch($stmt)) {
                mysqli_stmt_close($stmt);
                throw new ApiError('Пользователь не найден', 404);
            }

            mysqli_stmt_close($stmt);

            return [
                'id' => (int) $id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'foto' => $foto,
                'check_email' => (int) $checkEmail
            ];
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('get user by id: ' . $e->getMessage());
            throw new ApiError('ошибка при получении пользователя', 500);
        }
    }

    public function issueAuthToken(int $userId): string
    {
        try {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            $this->deleteExpiredAuthTokens();

            $query = "INSERT INTO auth_token(token, user_id, exp_date) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "si", $tokenHash, $userId);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db insert auth token: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_close($stmt);

            return $token;
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('issue auth token: ' . $e->getMessage());
            throw new ApiError('ошибка при создании токена авторизации', 500);
        }
    }

    public function getUserByAuthToken(string $token): array
    {
        try {
            $token = trim($token);

            if ($token === '') {
                throw new ApiError('Сессия недействительна', 401);
            }

            $tokenHash = hash('sha256', $token);

            $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.foto, u.check_email
                      FROM auth_token t
                      INNER JOIN user u ON u.id = t.user_id
                      WHERE t.token = ? AND t.exp_date > NOW()
                      LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "s", $tokenHash);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select auth token: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_result($stmt, $id, $firstName, $lastName, $email, $phone, $foto, $checkEmail);

            if (!mysqli_stmt_fetch($stmt)) {
                mysqli_stmt_close($stmt);
                throw new ApiError('Сессия недействительна', 401);
            }

            mysqli_stmt_close($stmt);

            return [
                'id' => (int) $id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'foto' => $foto,
                'check_email' => (int) $checkEmail
            ];
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('get user by auth token: ' . $e->getMessage());
            throw new ApiError('ошибка при проверке токена авторизации', 500);
        }
    }

    public function revokeAuthToken(string $token): void
    {
        $token = trim($token);

        if ($token === '') {
            return;
        }

        $tokenHash = hash('sha256', $token);

        $query = "DELETE FROM auth_token WHERE token = ?";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            error_log('db prepare: ' . mysqli_error($this->db));
            return;
        }

        mysqli_stmt_bind_param($stmt, "s", $tokenHash);

        if (!mysqli_stmt_execute($stmt)) {
            error_log('db delete auth token: ' . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
    }

    private function deleteExpiredAuthTokens(): void
    {
        $query = "DELETE FROM auth_token WHERE exp_date < NOW()";
        $stmt = mysqli_prepare($this->db, $query);

        if (!$stmt) {
            error_log('db prepare: ' . mysqli_error($this->db));
            return;
        }

        if (!mysqli_stmt_execute($stmt)) {
            error_log('db delete expired auth tokens: ' . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
    }

    private function deleteVerifyToken(int $tokenId): void
    {
        $deleteQuery = "DELETE FROM verify_token WHERE id = ?";
        $stmt = mysqli_prepare($this->db, $deleteQuery);

        if (!$stmt) {
            error_log('db prepare: ' . mysqli_error($this->db));
            return;
        }

        mysqli_stmt_bind_param($stmt, "i", $tokenId);

        if (!mysqli_stmt_execute($stmt)) {
            error_log('db delete token: ' . mysqli_stmt_error($stmt));
        }

        mysqli_stmt_close($stmt);
    }

    public static function clientIp(): string
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $ip = filter_var($ip, FILTER_VALIDATE_IP);

        return $ip === false ? '' : $ip;
    }

    public function getBlockRemainingSeconds(string $ip): int
    {
        if ($ip === '') {
            return 0;
        }

        try {
            $query = "SELECT TIMESTAMPDIFF(SECOND, NOW(), blocked_until) AS seconds_left
                      FROM auth_attempt
                      WHERE ip = ? AND blocked_until > NOW()
                      LIMIT 1";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "s", $ip);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db select auth attempt: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_result($stmt, $secondsLeft);
            $fetched = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            return $fetched ? max(0, (int) $secondsLeft) : 0;
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('get block remaining seconds: ' . $e->getMessage());
            throw new ApiError('ошибка при проверке блокировки', 500);
        }
    }

    public function assertIpNotBlocked(string $ip): void
    {
        $secondsLeft = $this->getBlockRemainingSeconds($ip);

        if ($secondsLeft <= 0) {
            return;
        }

        header('Retry-After: ' . $secondsLeft);
        throw new ApiError('Ваш IP заблокирован. Осталось ' . $secondsLeft . ' с.', 429);
    }

    public function registerFailedLogin(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        try {
            $query = "INSERT INTO auth_attempt (ip, attempt, blocked_until) VALUES (?, 1, NULL)
                      ON DUPLICATE KEY UPDATE
                        attempt = IF(attempt >= ? AND (blocked_until IS NULL OR blocked_until <= NOW()), 1, attempt + 1),
                        blocked_until = IF(
                          attempt >= ? AND (blocked_until IS NULL OR blocked_until <= NOW()),
                          DATE_ADD(NOW(), INTERVAL ? SECOND),
                          IF(blocked_until > NOW(), blocked_until, NULL)
                        )";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                throw new ApiError('ошибка базы данных', 500);
            }

            $maxAttempts = self::MAX_LOGIN_ATTEMPTS;
            $blockSeconds = self::LOGIN_BLOCK_SECONDS;
            mysqli_stmt_bind_param($stmt, "siii", $ip, $maxAttempts, $maxAttempts, $blockSeconds);

            if (!mysqli_stmt_execute($stmt)) {
                $err = mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
                error_log('db insert auth attempt: ' . $err);
                throw new ApiError('ошибка базы данных', 500);
            }

            mysqli_stmt_close($stmt);
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('register failed login: ' . $e->getMessage());
            throw new ApiError('ошибка при учете попыток входа', 500);
        }
    }

    public function resetFailedLogins(string $ip): void
    {
        if ($ip === '') {
            return;
        }

        try {
            $query = "UPDATE auth_attempt SET attempt = 0, blocked_until = NULL WHERE ip = ?";
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));
                return;
            }

            mysqli_stmt_bind_param($stmt, "s", $ip);

            if (!mysqli_stmt_execute($stmt)) {
                error_log('db update auth attempt: ' . mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);
        } catch (Throwable $e) {
            error_log('reset failed logins: ' . $e->getMessage());
        }
    }
}
