<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 20/06/2016
 * Time: 12:49
 */

namespace AppBundle\Supervision\Utils;

class CryptageHelpers
{

    public static function encrypt($pure_string, $encryption_key)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $encrypted_string = mcrypt_encrypt(
            MCRYPT_BLOWFISH,
            $encryption_key,
            utf8_encode($pure_string),
            MCRYPT_MODE_ECB,
            $iv
        );

        return $encrypted_string;
    }

    public static function decrypt($encrypted_string, $encryption_key)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypted_string = mcrypt_decrypt(MCRYPT_BLOWFISH, $encryption_key, $encrypted_string, MCRYPT_MODE_ECB, $iv);

        return $decrypted_string;
    }
}
