<?php
require_once __DIR__ . "/../service/ImageService.php";
require_once __DIR__ . "/../error/ApiError.php";

class ImageController
{
    public function uploadFile()
    {
        $imageService = new ImageService();
        $images = [];
        $success = null;
        $error = null;

        $formAction = "/" . ltrim((string) parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH), "/");
        if (!in_array($formAction, ["/img", "/images"], true)) {
            $formAction = "/images";
        }

        try {
            if ($_SERVER["REQUEST_METHOD"] === "POST") {
                $contentLength = (int) ($_SERVER["CONTENT_LENGTH"] ?? 0);

                if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
                    throw new ApiError("Файл слишком большой. Максимальный размер — 5 МБ", 400);
                }

                $file = $_FILES["image"] ?? [];
                $imageService->upload($file);

                header("Location: " . $formAction . "?uploaded=1");
                exit;
            }

            $uploaded = filter_input(INPUT_GET, "uploaded", FILTER_VALIDATE_INT);
            if ($uploaded === 1) {
                $success = "Изображение успешно загружено";
            }

            $images = $imageService->getAll();
        } catch (ApiError $e) {
            http_response_code($e->getStatusCode());
            $error = $e->getMessage();

            try {
                $images = $imageService->getAll();
            } catch (Throwable $listError) {
                error_log('image list: ' . $listError->getMessage());
                $images = [];
            }
        } catch (Throwable $e) {
            error_log('upload file: ' . $e->getMessage());
            http_response_code(500);
            $error = "Произошла ошибка при загрузке изображения";
        }

        require __DIR__ . "/../public/pages/img.php";
    }
}

?>
