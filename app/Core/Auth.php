<?php
namespace App\Core;

class Auth {

    public static function isAdmin(): bool {
        return ($_SESSION['user']['role'] === 'admin');
    }

    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: '. BASE_PATH);
            exit;
        }
    }

    public static function isLogged(): bool {
        return isset($_SESSION['user']);
    }

    public static function requireUser() {
        if (!self::isLogged()) {
            header('Location: '. BASE_PATH);
            exit;
        }
    }
}