<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email_Controller {
    /**
     * 邮件发送 SendEmail
     * @param array $email 邮件地址
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @return void
     */
    static function SendEmail(array $email,string $title,string $content):void {
        $config=GetConfig();
        $mail=new PHPMailer(true);
        try {
            $mail->CharSet="UTF-8";
            $mail->SMTPDebug=0;
            $mail->isSMTP();
            $mail->Host=$config["email_host"];
            $mail->SMTPAuth=true;                      // 允许 SMTP 认证
            $mail->Username=$config["email_name"];                // SMTP 用户名  即邮箱的用户名
            $mail->Password=$config["email_password"];             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure=$config["email_protocol"];                    // 允许 TLS 或者ssl协议
            $mail->Port=$config["email_port"];                            // 服务器端口 25 或者465 具体要看邮箱服务器支持
            $mail->setFrom($config["email_name"],$config["email_from"]);
            for ($i=0;$i<count($email);$i++) $mail->addAddress($email[$i]);
            $mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容
            $mail->Subject=$title;
            $mail->Body   =$content;
            $mail->AltBody='Your email client don\'t support to analyse HTML!';
            $mail->send();
        } catch (Exception $e) {
            Error_Controller::Common("Failed to send email!",-400,true);
        }
    }
}
?>