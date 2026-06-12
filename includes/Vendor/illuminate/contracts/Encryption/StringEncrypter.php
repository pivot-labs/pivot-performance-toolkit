<?php

namespace PerformanceToolkit\Vendor\Illuminate\Contracts\Encryption;

interface StringEncrypter
{
    /**
     * Encrypt a string without serialization.
     *
     * @param  string  $value
     * @return string
     *
     * @throws \PerformanceToolkit\Vendor\Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptString(#[\SensitiveParameter] $value);

    /**
     * Decrypt the given string without unserialization.
     *
     * @param  string  $payload
     * @return string
     *
     * @throws \PerformanceToolkit\Vendor\Illuminate\Contracts\Encryption\DecryptException
     */
    public function decryptString($payload);
}
