<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CustomMailer
{
    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    public function send($to, $subject, $body)
    {
        try {
            // 邮件服务器设置
            $this->mail->isSMTP();
            $this->mail->Host       = env('MAIL_HOST');
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = env('MAIL_USERNAME');
            $this->mail->Password   = env('MAIL_PASSWORD');
            $this->mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $this->mail->Port       = env('MAIL_PORT');

            // 发件人
            $this->mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));

            // 收件人
            $this->mail->addAddress($to);

            // 邮件内容
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;

            // 发送邮件
            $this->mail->send();

            return true;
        } catch (Exception $e) {
            // 错误处理
            // return "邮件发送失败: {$this->mail->ErrorInfo}";
            return false;
        }
    }
}
