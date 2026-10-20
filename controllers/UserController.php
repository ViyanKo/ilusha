<?php 
require __DIR__ . "/../service/UserService.php";

class UserController
{
    public function register()
    {
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
                require __DIR__ . "/../public/pages/cab.php";
            }
        $first_name = $_POST['first_name'];
        $last_name  = $_POST['last_name'];
        $email      = $_POST['email'];
        $phone     = $_POST['phone'];
        $password   = $_POST['password'];

        $userService = new UserService();
        $user = $userService->createUser($first_name, $last_name, $email, $phone, $password);
        require __DIR__ . "/../public/pages/cab.php";
    }
    

    
}

?>