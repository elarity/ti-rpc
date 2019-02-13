<?php
namespace System\Library\RNCryptor;

use stdClass;

/**
 * RNEncryptor for PHP
 *
 * Encrypt data interchangeably with Rob Napier's Objective-C implementation
 * of RNCryptor
 */
class Encryptor extends Cryptor
{
    /**
     * Encrypt plaintext using RNCryptor's algorithm
     *
     * @param string $plaintext Text to be encrypted
     * @param string $password Password to use
     * @param int $version (Optional) RNCryptor schema version to use.
     * @throws \Exception If the provided version (if any) is unsupported
     * @return string Encrypted, Base64-encoded string
     */
    public function encrypt($plaintext, $password, $version = Cryptor::DEFAULT_SCHEMA_VERSION, $base64Encode = true)
    {
        $this->configure($version);

        $components = $this->makeComponents($version);
        $components->headers->encSalt = $this->makeSalt();
        $components->headers->hmacSalt = $this->makeSalt();
        $components->headers->iv = $this->makeIv($this->config->ivLength);

        $encKey = $this->makeKey($components->headers->encSalt, $password);
        $hmacKey = $this->makeKey($components->headers->hmacSalt, $password);

        return $this->encryptFromComponents($plaintext, $components, $encKey, $hmacKey, $base64Encode);
    }

    public function encryptWithArbitrarySalts(
        $plaintext,
        $password,
        $encSalt,
        $hmacSalt,
        $iv,
        $version = Cryptor::DEFAULT_SCHEMA_VERSION,
        $base64Encode = true
    ) {
        $this->configure($version);

        $components = $this->makeComponents($version);
        $components->headers->encSalt = $encSalt;
        $components->headers->hmacSalt = $hmacSalt;
        $components->headers->iv = $iv;

        $encKey = $this->makeKey($components->headers->encSalt, $password);
        $hmacKey = $this->makeKey($components->headers->hmacSalt, $password);

        return $this->encryptFromComponents($plaintext, $components, $encKey, $hmacKey, $base64Encode);
    }

    public function encryptWithArbitraryKeys(
        $plaintext,
        $encKey,
        $hmacKey,
        $iv,
        $version = Cryptor::DEFAULT_SCHEMA_VERSION,
        $base64Encode = true
    ) {
        $this->configure($version);

        $this->config->options = 0;

        $components = $this->makeComponents($version);
        $components->headers->iv = $iv;

        return $this->encryptFromComponents($plaintext, $components, $encKey, $hmacKey, $base64Encode);
    }

    private function makeComponents($version)
    {
        $components = new stdClass;
        $components->headers = new stdClass;
        $components->headers->version = chr($version);
        $components->headers->options = chr($this->config->options);

        return $components;
    }

    private function encryptFromComponents($plaintext, stdClass $components, $encKey, $hmacKey, $base64encode = true)
    {
        $iv = $components->headers->iv;
        if ($this->config->mode == 'ctr') {
            $components->ciphertext = $this->aesCtrLittleEndianCrypt($plaintext, $encKey, $iv);
        } else {
            $components->ciphertext = $this->encryptInternal($encKey, $plaintext, 'cbc', $iv);
        }

        $data = $components->headers->version
            . $components->headers->options
            . ($components->headers->encSalt ?? '')
            . ($components->headers->hmacSalt ?? '')
            . $components->headers->iv
            . $components->ciphertext
            . $this->makeHmac($components, $hmacKey);

        return ($base64encode ? base64_encode($data) : $data);
    }

    private function makeSalt()
    {
        return $this->makeIv($this->config->saltLength);
    }

    private function makeIv($blockSize)
    {
        return openssl_random_pseudo_bytes($blockSize);
    }
}
