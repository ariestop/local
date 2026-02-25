<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function login(): void
    {
        if ($this->getLoggedUser()) {
            $this->json(['success' => true, 'user' => $this->getLoggedUser()]);
            return;
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            $this->json(['success' => false, 'error' => 'Введите email и пароль'], 400);
            return;
        }
        $userModel = new User($this->db);
        $user = $userModel->login($email, $password);
        if (!$user) {
            $this->json(['success' => false, 'error' => 'Неверный email или пароль'], 401);
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = $user;
        $this->json(['success' => true, 'user' => $user]);
    }

    public function register(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $captcha = $_POST['captcha'] ?? '';

        if (!$email || !$password || !$name) {
            $this->json(['success' => false, 'error' => 'Заполните все поля'], 400);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'error' => 'Некорректный email'], 400);
            return;
        }
        if (strlen($password) < 5) {
            $this->json(['success' => false, 'error' => 'Пароль не менее 5 символов'], 400);
            return;
        }
        if ($password !== $password2) {
            $this->json(['success' => false, 'error' => 'Пароли не совпадают'], 400);
            return;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $expected = $_SESSION['captcha'] ?? '';
        if (!$expected || strtolower(trim($captcha)) !== strtolower($expected)) {
            unset($_SESSION['captcha']);
            $this->json(['success' => false, 'error' => 'Неверная капча'], 400);
            return;
        }
        unset($_SESSION['captcha']);

        $userModel = new User($this->db);
        if ($userModel->emailExists($email)) {
            $this->json(['success' => false, 'error' => 'Этот email уже зарегистрирован'], 400);
            return;
        }
        $userId = $userModel->register(['email' => $email, 'password' => $password, 'name' => $name]);
        $_SESSION['user'] = ['id' => $userId, 'email' => $email, 'name' => $name];
        $this->json(['success' => true, 'message' => 'Регистрация успешна', 'user' => $_SESSION['user']]);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user']);
        $this->redirect('/');
    }
}
