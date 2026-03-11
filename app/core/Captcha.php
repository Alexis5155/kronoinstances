<?php
namespace app\core;

class Captcha {

    /**
     * Génère une image captcha et stocke le code en session.
     * @param string $key  Clé de session (permet plusieurs captchas distincts sur la même page)
     */
    public static function generate(string $key = 'captcha_code', int $width = 120, int $height = 40): void {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        $_SESSION[$key] = $code;

        $image       = imagecreatetruecolor($width, $height);
        $bg          = imagecolorallocate($image, 248, 249, 250);
        $text_color  = imagecolorallocate($image, 13, 110, 253);
        $noise_color = imagecolorallocate($image, 200, 200, 200);

        imagefill($image, 0, 0, $bg);

        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
        }
        for ($i = 0; $i < 3; $i++) {
            imageline($image, 0, rand(0, $height), $width, rand(0, $height), $noise_color);
        }

        $font_size = 5;
        $x = ($width - (strlen($code) * imagefontwidth($font_size))) / 2;
        $y = ($height - imagefontheight($font_size)) / 2;
        imagestring($image, $font_size, $x, $y, $code, $text_color);

        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
        exit;
    }

    /**
     * Vérifie le code saisi par l'utilisateur.
     * Consomme la clé de session (usage unique).
     *
     * @param string $input  Valeur saisie par l'utilisateur
     * @param string $key    Clé de session à vérifier (doit correspondre à celle passée à generate())
     * @return bool
     */
    public static function verify(string $input, string $key = 'captcha_code'): bool {
        $expected = $_SESSION[$key] ?? '';
        unset($_SESSION[$key]);

        return strtoupper(trim($input)) === strtoupper($expected);
    }
}
