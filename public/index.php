<?php

require __DIR__ . "/../controllers/UserController.php";

$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

    $userController = new UserController();
    
    switch ($uri) {
        case 'register':
            $userController->register();
            break;
        case '':
            require __DIR__ . "/pages/index.html";
            break;
    }

?>