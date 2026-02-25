<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class ApiController extends Controller
{
    public function checkEmail(): void
    {
        $email = trim($_GET['email'] ?? '');
        if (!$email) {
            $this->json(['exists' => false]);
            return;
        }
        $userModel = new User($this->db);
        $this->json(['exists' => $userModel->emailExists($email)]);
    }

    public function captcha(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5);
        $_SESSION['captcha'] = $code;

        $w = 120;
        $h = 40;
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 245, 245, 245);
        $text = imagecolorallocate($img, 50, 50, 50);
        $noise = imagecolorallocate($img, 180, 180, 180);
        imagefill($img, 0, 0, $bg);
        for ($i = 0; $i < 50; $i++) {
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $noise);
        }
        imagestring($img, 5, 25, 12, $code, $text);
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store');
        imagepng($img);
        imagedestroy($img);
    }
}
