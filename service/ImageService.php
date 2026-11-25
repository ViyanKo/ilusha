<?php
require_once __DIR__ . "/../error/ApiError.php";

class ImageService
{
    private const UPLOAD_DIR = __DIR__ . "/../public/loaded_img";
    private const MAX_SIZE = 5 * 1024 * 1024;

    private const ALLOWED_MIME = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/gif"  => "gif",
        "image/webp" => "webp",
    ];

    public function upload(array $file): string
    {
        try {
            $this->checkUploadError($file);

            if (!isset($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
                throw new ApiError("Файл не был загружен", 400);
            }

            $size = isset($file["size"]) ? (int) $file["size"] : 0;
            if ($size <= 0 || $size > self::MAX_SIZE) {
                throw new ApiError("Размер файла не должен превышать 5 МБ", 400);
            }

            $mime = $this->detectMimeType($file["tmp_name"]);

            if ($mime === "") {
                throw new ApiError("Не удалось определить тип файла", 400);
            }

            if (!isset(self::ALLOWED_MIME[$mime])) {
                throw new ApiError("Допустимы только изображения JPG, PNG, GIF и WebP", 400);
            }

            if (@getimagesize($file["tmp_name"]) === false) {
                throw new ApiError("Файл не является изображением", 400);
            }

            $uploadDir = $this->ensureUploadDir();
            $filename = bin2hex(random_bytes(8)) . "." . self::ALLOWED_MIME[$mime];
            $targetPath = $uploadDir . "/" . $filename;

            if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
                $lastError = error_get_last();
                error_log(sprintf(
                    'upload move failed: from=%s to=%s dir_writable=%s error=%s',
                    $file["tmp_name"] ?? 'unknown',
                    $targetPath,
                    is_writable($uploadDir) ? 'yes' : 'no',
                    $lastError["message"] ?? 'unknown'
                ));
                throw new ApiError("Не удалось сохранить файл", 500);
            }

            @chmod($targetPath, 0644);

            return $filename;
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('image upload: ' . $e->getMessage());
            throw new ApiError("Ошибка при загрузке изображения", 500);
        }
    }

    public function uploadForUser(array $file, int $userId): string
    {
        try {
            if ($userId <= 0) {
                throw new ApiError("Некорректный пользователь", 400);
            }

            $this->checkUploadError($file);

            if (!isset($file["tmp_name"]) || !is_uploaded_file($file["tmp_name"])) {
                throw new ApiError("Файл не был загружен", 400);
            }

            $size = isset($file["size"]) ? (int) $file["size"] : 0;
            if ($size <= 0 || $size > self::MAX_SIZE) {
                throw new ApiError("Размер файла не должен превышать 5 МБ", 400);
            }

            $mime = $this->detectMimeType($file["tmp_name"]);

            if ($mime === "") {
                throw new ApiError("Не удалось определить тип файла", 400);
            }

            if (!isset(self::ALLOWED_MIME[$mime])) {
                throw new ApiError("Допустимы только изображения JPG, PNG, GIF и WebP", 400);
            }

            if (@getimagesize($file["tmp_name"]) === false) {
                throw new ApiError("Файл не является изображением", 400);
            }

            $uploadDir = $this->ensureUserUploadDir($userId);
            $filename = "avatar." . self::ALLOWED_MIME[$mime];
            $targetPath = $uploadDir . "/" . $filename;

            if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
                $lastError = error_get_last();
                error_log(sprintf(
                    'upload user photo failed: from=%s to=%s dir_writable=%s error=%s',
                    $file["tmp_name"] ?? 'unknown',
                    $targetPath,
                    is_writable($uploadDir) ? 'yes' : 'no',
                    $lastError["message"] ?? 'unknown'
                ));
                throw new ApiError("Не удалось сохранить файл", 500);
            }

            @chmod($targetPath, 0644);
            $this->removeOtherUserPhotos($uploadDir, $filename);

            return $filename;
        } catch (ApiError $e) {
            throw $e;
        } catch (Throwable $e) {
            error_log('user photo upload: ' . $e->getMessage());
            throw new ApiError("Ошибка при загрузке изображения", 500);
        }
    }

    public function getUserPhotoUrl(int $userId, ?string $fotoPath = null): ?string
    {
        try {
            if ($userId <= 0) {
                return null;
            }

            $baseUrl = "/loaded_img/" . $userId . "/";

            if ($fotoPath !== null && trim($fotoPath) !== '') {
                $normalizedPath = "/" . ltrim(trim($fotoPath), "/");

                if (substr($normalizedPath, -1) !== "/") {
                    $normalizedPath .= "/";
                }

                if (
                    preg_match('#^/loaded_img/(\d+)/$#', $normalizedPath, $matches) === 1
                    && (int) $matches[1] === $userId
                ) {
                    $baseUrl = $normalizedPath;
                }
            }

            $userDir = self::UPLOAD_DIR . "/" . $userId;

            if (!is_dir($userDir)) {
                return null;
            }

            $files = array_diff(scandir($userDir), [".", ".."]);
            $allowedExtensions = array_values(self::ALLOWED_MIME);
            $latestFile = null;
            $latestMtime = -1;

            foreach ($files as $file) {
                $path = $userDir . "/" . $file;

                if (!is_file($path)) {
                    continue;
                }

                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }

                $mtime = filemtime($path) ?: 0;
                if ($mtime > $latestMtime) {
                    $latestMtime = $mtime;
                    $latestFile = $file;
                }
            }

            if ($latestFile === null) {
                return null;
            }

            return $baseUrl . rawurlencode($latestFile) . "?v=" . $latestMtime;
        } catch (Throwable $e) {
            error_log('get user photo: ' . $e->getMessage());
            return null;
        }
    }

    public function getAll(): array
    {
        try {
            if (!is_dir(self::UPLOAD_DIR)) {
                return [];
            }

            $files = array_diff(scandir(self::UPLOAD_DIR), [".", ".."]);
            $images = [];
            $allowedExtensions = array_values(self::ALLOWED_MIME);

            foreach ($files as $file) {
                $path = self::UPLOAD_DIR . "/" . $file;

                if (!is_file($path)) {
                    continue;
                }

                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowedExtensions, true)) {
                    continue;
                }

                $images[] = [
                    "name" => $file,
                    "mtime" => filemtime($path) ?: 0,
                ];
            }

            usort($images, static function (array $a, array $b): int {
                return $b["mtime"] <=> $a["mtime"];
            });

            return array_column($images, "name");
        } catch (Throwable $e) {
            error_log('image list: ' . $e->getMessage());
            throw new ApiError("Не удалось получить список изображений", 500);
        }
    }

    private function detectMimeType(string $path): string
    {
        if (class_exists("finfo")) {
            try {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($path);

                if (is_string($mime) && $mime !== "") {
                    return $mime;
                }
            } catch (Throwable $e) {
                error_log('finfo: ' . $e->getMessage());
            }
        }

        if (function_exists("mime_content_type")) {
            $mime = mime_content_type($path);

            if (is_string($mime) && $mime !== "") {
                return $mime;
            }
        }

        return "";
    }

    private function ensureUploadDir(): string
    {
        $dir = self::UPLOAD_DIR;

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                error_log('upload dir: cannot create ' . $dir);
                throw new ApiError("Не удалось создать каталог для загрузки", 500);
            }
        }

        if (!is_writable($dir)) {
            error_log('upload dir: not writable ' . $dir);
            throw new ApiError("Каталог для загрузки недоступен для записи", 500);
        }

        return $dir;
    }

    private function ensureUserUploadDir(int $userId): string
    {
        $dir = self::UPLOAD_DIR . "/" . $userId;

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
                error_log('user upload dir: cannot create ' . $dir);
                throw new ApiError("Не удалось создать каталог для загрузки", 500);
            }
        }

        if (!is_writable($dir)) {
            error_log('user upload dir: not writable ' . $dir);
            throw new ApiError("Каталог для загрузки недоступен для записи", 500);
        }

        $indexPath = $dir . "/index.html";
        if (!is_file($indexPath) && !@file_put_contents($indexPath, "")) {
            error_log('user upload dir: cannot create index ' . $indexPath);
        }

        return $dir;
    }

    private function removeOtherUserPhotos(string $dir, string $keepFilename): void
    {
        $allowedExtensions = array_values(self::ALLOWED_MIME);
        $files = array_diff(scandir($dir), [".", ".."]);

        foreach ($files as $file) {
            if ($file === $keepFilename) {
                continue;
            }

            $path = $dir . "/" . $file;
            if (!is_file($path)) {
                continue;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            if (!@unlink($path)) {
                error_log('user photo: cannot delete old file ' . $path);
            }
        }
    }

    private function checkUploadError(array $file): void
    {
        $error = $file["error"] ?? UPLOAD_ERR_NO_FILE;

        switch ($error) {
            case UPLOAD_ERR_OK:
                return;
            case UPLOAD_ERR_NO_FILE:
                throw new ApiError("Выберите файл для загрузки", 400);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new ApiError("Размер файла не должен превышать 5 МБ", 400);
            case UPLOAD_ERR_PARTIAL:
                throw new ApiError("Файл был загружен только частично", 400);
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                error_log('upload error: cannot write to temp dir, code ' . $error);
                throw new ApiError("Не удалось сохранить файл на сервере", 500);
            case UPLOAD_ERR_EXTENSION:
                error_log('upload error: stopped by extension, code ' . $error);
                throw new ApiError("Загрузка файла была остановлена сервером", 500);
            default:
                error_log('upload error code: ' . $error);
                throw new ApiError("Ошибка при загрузке файла", 500);
        }
    }
}

?>
