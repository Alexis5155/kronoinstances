<?php
namespace app\core;

class Captcha {
    /**
     * Génère une image de captcha et stocke le code en session
     */
    public static function generate($width = 120, $height = 40) {
        // 1. Création du code aléatoire (lettres majuscules et chiffres)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // On retire 0, O, 1, I pour la lisibilité
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Stockage en session pour vérification ultérieure
        $_SESSION['captcha_code'] = $code;

        // 2. Création de l'image
        $image = imagecreatetruecolor($width, $height);
        
        // Couleurs
        $bg = imagecolorallocate($image, 248, 249, 250); // Fond gris clair Bootstrap
        $text_color = imagecolorallocate($image, 13, 110, 253); // Bleu Bootstrap principal
        $noise_color = imagecolorallocate($image, 200, 200, 200);

        imagefill($image, 0, 0, $bg);

        // 3. Ajout de "bruit" (points et lignes) pour les bots
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
        }
        for ($i = 0; $i < 3; $i++) {
            imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);
        }

        // 4. Écriture du texte (Utilisation d'une police native de PHP pour l'exportabilité)
        // Note: On utilise imagestring si on ne veut pas gérer de fichier .ttf externe
        $font_size = 5; 
        $x = ($width - (strlen($code) * imagefontwidth($font_size))) / 2;
        $y = ($height - imagefontheight($font_size)) / 2;
        imagestring($image, $font_size, $x, $y, $code, $text_color);

        // 5. Rendu de l'image
        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
        exit;
    }
}