<?php
namespace app\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    
    /**
     * Envoie un email avec ou sans le template global KronoInstances
     * 
     * @param string $to L'adresse email du destinataire
     * @param string $subject L'objet de l'email
     * @param string $body Le contenu de l'email (HTML accepté)
     * @param bool $useTemplate Booléen pour encapsuler le contenu dans le design de la plateforme
     */
    public static function send($to, $subject, $body, $useTemplate = true) {
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
            $mail->setFrom(MAIL_FROM, 'KronoInstances');
            $mail->addAddress($to);

            // Génération du contenu final
            if ($useTemplate) {
                $finalBody = self::getTemplate($subject, $body);
            } else {
                $finalBody = $body;
            }

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $finalBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $finalBody));

            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Retourne le code HTML du gabarit global des e-mails
     */
    private static function getTemplate($subject, $body) {
        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        </head>
        <body style='margin:0; padding:0; background-color:#f4f6f8; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#f4f6f8; padding: 40px 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius: 12px; overflow:hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);'>
                            
                            <!-- EN-TÊTE KRONOINSTANCES -->
                            <tr>
                                <td style='background-color: #0d6efd; padding: 30px 40px; text-align: center;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: 0.5px;'>
                                        KronoInstances
                                    </h1>
                                </td>
                            </tr>
                            
                            <!-- TITRE DE L'ACTION (SUJET) -->
                            <tr>
                                <td style='padding: 35px 40px 10px 40px; text-align: center;'>
                                    <h2 style='color: #212529; margin: 0; font-size: 20px; font-weight: 600;'>
                                        {$subject}
                                    </h2>
                                </td>
                            </tr>

                            <!-- CONTENU DE L'E-MAIL -->
                            <tr>
                                <td style='padding: 20px 40px 40px 40px; color: #495057; font-size: 15px; line-height: 1.6;'>
                                    {$body}
                                </td>
                            </tr>
                            
                            <!-- PIED DE PAGE -->
                            <tr>
                                <td style='background-color: #f8f9fa; padding: 25px 40px; text-align: center; border-top: 1px solid #e9ecef;'>
                                    <p style='color: #6c757d; font-size: 13px; margin: 0 0 10px 0; font-weight: 600;'>
                                        Votre plateforme de gestion des instances
                                    </p>
                                    <p style='color: #adb5bd; font-size: 12px; margin: 0;'>
                                        Ceci est un message automatique envoyé par KronoInstances.<br>
                                        Merci de ne pas y répondre directement.
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}
