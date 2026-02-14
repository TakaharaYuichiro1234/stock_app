<?php
namespace App\Controllers;

class AuthController {
    public function showLogin() {
        if (isset($_GET['redirect']) && str_starts_with($_GET['redirect'], BASE_PATH)) {
            $_SESSION['redirect_after_login'] = $_GET['redirect'];
        }

        require __DIR__ . '/../Views/login.php';
    }

    public function login()  {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($email === '' || $password === '') {
            $_SESSION['error'] = '未入力項目があります';
            header('Location: '.BASE_PATH. '/login');
            exit;
        }

        require __DIR__ . '/../../config/db.php';
        $this->pdo = $pdo;

        $stmt = $pdo->prepare(
            'SELECT * FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'ログイン失敗';
            header('Location: '. BASE_PATH);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'uuid' => $user['uuid'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];

        $redirect = $_SESSION['redirect_after_login'] ?? BASE_PATH;
        unset($_SESSION['redirect_after_login']);

        header('Location: ' . $redirect);
        exit;
    }

    public function logout() {
        $_SESSION = [];
        session_destroy();

        header('Location: '. BASE_PATH);
        exit;
    }
}
