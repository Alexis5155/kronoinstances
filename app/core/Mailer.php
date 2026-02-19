<?php
namespace app\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    public static function send($to, $subject, $body) {
        $mail = new PHPMailer(true);

        try {
            // Configuration Serveur
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->Port       = MAIL_PORT;
            if (MAIL_PORT == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Hostname = substr(strrchr(MAIL_FROM, "@"), 1);
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Paramètres de l'email
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(MAIL_FROM, 'KronoActes');
            $mail->addAddress($to);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
}