<?php

namespace App\Services;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    public function __construct()
    {
    }

    public function sendMail() 
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();

            $mail->Host = '127.0.0.1';
            $mail->Port = 1025;

            $mail->SMTPAuth = false;

            $mail->CharSet = 'UTF-8';

            $mail->setFrom('from@example.com', 'テスト送信');
            $mail->addAddress('to@example.com');

            $mail->Subject = 'Mailpitテスト';
            $mail->Body = 'これはテストメールです';

            $mail->send();

            echo '送信成功';

        } catch (Exception $e) {
            echo $mail->ErrorInfo;
        }
    }
}