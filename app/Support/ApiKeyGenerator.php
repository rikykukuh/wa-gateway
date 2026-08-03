<?php

namespace App\Support;

class ApiKeyGenerator
{
    public static function generate(): array
    {
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $plainText = 'wag_live_'.$secret;

        return [
            'plain_text' => $plainText,
            'prefix' => substr($plainText, 0, 17),
            'hash' => hash('sha256', $plainText),
        ];
    }
}
