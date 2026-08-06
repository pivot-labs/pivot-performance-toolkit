<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Encryption;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface StringEncrypter
{
    /**
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptString(#[\SensitiveParameter] $value);

    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptString($payload);
}
