<?php

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

require __DIR__ . "/../controllers/UserController.php";
require __DIR__ . "/../controllers/ImageController.php";
require_once __DIR__ . "/../error/ApiError.php";

$userController = new UserController();

try {
    try {
        $userService = new UserService();
        $userService->assertIpNotBlocked(UserService::clientIp());
    } catch (ApiError $e) {
        if ($e->getStatusCode() !== 500) {
            throw $e;
        }

        error_log('block check: ' . $e->getMessage());
    }

    switch ($uri) {
        case 'register':
            $userController->register();
            break;
        case 'login':
            $userController->login();
            break;
        case 'verify':
            $userController->verify();
            break;
        case 'cab':
            $userController->cab();
            break;
        case 'cab/photo':
            $userController->uploadPhoto();
            break;
        case 'logout':
            $userController->logout();
            break;
        case 'check_email':
            $userController->checkEmail();
            break;
        case '':
            require __DIR__ . "/pages/index.php";
            break;
        case 'img':
        case 'images':
            $imageController = new ImageController();
            $imageController->uploadFile();
            break;
        default:
            throw new ApiError('маршрут не найден', 404);
    }
} catch (Throwable $e) {
    ApiError::handle($e);
}


?>
