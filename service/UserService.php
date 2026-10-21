<?php
class UserService
{
    private function createConnect()
    {
        try {
            $connective = mysqli_connect("localhost", "kusminova_kusminova_4", "Alinakus12102007!", "kusminova_kusminova_4");
        } catch (mysqli_sql_exception $e) {
            error_log('db connect: ' . $e->getMessage());
        }

        if (!$connective) {
            error_log('db connect: ' . mysqli_connect_error());
        }

        mysqli_set_charset($connective, "utf8");

        return $connective;
    }

    private $db;
    public function __construct()
    {
        $this->db = $this->createConnect();
    }

    public function createUser($first_name, $last_name, $email, $password, $phone): array
    {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO user(first_name, last_name, email, password, phone) VALUES (?, ?, ?, ?, ?)";

        try {
            $stmt = mysqli_prepare($this->db, $query);

            if (!$stmt) {
                error_log('db prepare: ' . mysqli_error($this->db));

                throw new Error('ошибка базы данных', 500);
            }

            mysqli_stmt_bind_param($stmt, "sssss", $first_name, $last_name, $email, $passwordHash, $phone);

            mysqli_stmt_execute($stmt);

            $id = mysqli_stmt_insert_id($stmt);

            mysqli_stmt_close($stmt);
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) {
                throw new Error('Пользователь с таким email уже существует', 409);
            }

            error_log('db insert: ' . $e->getMessage());

            echo new Error('ошибка базы данных', 500);
        }

        return [
            'id' => $id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $passwordHash
        ];
    }
}
