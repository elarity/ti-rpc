<?php
namespace System\Library\RNCryptor;

use stdClass;

/**
 * RNDecryptor for PHP
 *
 * Decrypt data interchangeably with Rob Napier's Objective-C implementation
 * of RNCryptor
 */
class Decryptor extends Cryptor
{
    /**
     * Decrypt RNCryptor-encrypted data
     *
     * @param string $base64EncryptedData Encrypted, Base64-encoded text
     * @param string $password Password the text was encoded with
     * @throws Exception If the detected version is unsupported
     * @return string|false Decrypted string, or false if decryption failed
     */
    public function decrypt($encryptedBase64Data, $password)
    {
        $components = $this->unpackEncryptedBase64Data($encryptedBase64Data);

        if (!$this->hmacIsValid($components, $password)) {
            return false;
        }

        $key = $this->makeKey($components->headers->encSalt, $password);
        if ($this->config->mode == 'ctr') {
            return $this->aesCtrLittleEndianCrypt($components->ciphertext, $key, $components->headers->iv);
        }

        $iv = (string)$components->headers->iv;
        $method = $this->config->algorithm . 'cbc';

        return openssl_decrypt($components->ciphertext, $method, $key, OPENSSL_RAW_DATA, (string)$iv);
    }

    private function unpackEncryptedBase64Data($encryptedBase64Data, $isPasswordBased = true)
    {
        $binaryData = base64_decode($encryptedBase64Data);

        $components = new stdClass;
        $components->headers = $this->parseHeaders($binaryData, $isPasswordBased);

        $components->hmac = substr($binaryData, -$this->config->hmac->length);

        $offset = $components->headers->length;
        $length = strlen($binaryData) - $offset - strlen($components->hmac);

        $components->ciphertext = substr($binaryData, $offset, $length);

        return $components;
    }

    private function parseHeaders($binData, $isPasswordBased = true)
    {
        $offset = 0;

        $versionChr = $binData[0];
        $offset += strlen($versionChr);

        $this->configure(ord($versionChr));

        $optionsChr = $binData[1];
        $offset += strlen($optionsChr);

        $encSalt = null;
        $hmacSalt = null;
        if ($isPasswordBased) {
            $encSalt = substr($binData, $offset, $this->config->saltLength);
            $offset += strlen($encSalt);

            $hmacSalt = substr($binData, $offset, $this->config->saltLength);
            $offset += strlen($hmacSalt);
        }

        $iv = substr($binData, $offset, $this->config->ivLength);
        $offset += strlen($iv);

        $headers = (object)[
            'version' => $versionChr,
            'options' => $optionsChr,
            'encSalt' => $encSalt,
            'hmacSalt' => $hmacSalt,
            'iv' => $iv,
            'length' => $offset
        ];

        return $headers;
    }

    private function hmacIsValid($components, $password)
    {
        $hmacKey = $this->makeKey($components->headers->hmacSalt, $password);

        return hash_equals($components->hmac, $this->makeHmac($components, $hmacKey));
    }
}
