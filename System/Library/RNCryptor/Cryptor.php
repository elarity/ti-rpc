<?php

namespace System\Library\RNCryptor;


use stdClass;

class Cryptor
{
    const DEFAULT_SCHEMA_VERSION = 3;

    protected $config;

    public function generateKey($salt, $password, $version = self::DEFAULT_SCHEMA_VERSION)
    {
        $this->configure($version);

        return $this->makeKey($salt, $password);
    }

    protected function aesCtrLittleEndianCrypt($payload, $key, $iv)
    {
        $numOfBlocks = ceil(strlen($payload) / strlen($iv));
        $counter = '';
        for ($i = 0; $i < $numOfBlocks; ++$i) {
            $counter .= $iv;

            // Yes, the next line only ever increments the first character
            // of the counter string, ignoring overflow conditions.  This
            // matches CommonCrypto's behavior!
            $iv[0] = chr(ord($iv[0]) + 1);
        }

        return $payload ^ $this->encryptInternal($key, $counter, 'ecb');
    }

    protected function encryptInternal($key, $payload, $mode, $iv = null)
    {
        return openssl_encrypt($payload, $this->config->algorithm . $mode, $key, OPENSSL_RAW_DATA, (string)$iv);
    }

    protected function makeHmac(stdClass $components, $hmacKey)
    {
        $hmacMessage = '';
        if ($this->config->hmac->includesHeader) {
            $hmacMessage .= ''
                . $components->headers->version
                . $components->headers->options
                . (isset($components->headers->encSalt) ? $components->headers->encSalt : '')
                . (isset($components->headers->hmacSalt) ? $components->headers->hmacSalt : '')
                . $components->headers->iv;
        }

        $hmacMessage .= $components->ciphertext;

        $hmac = hash_hmac($this->config->hmac->algorithm, $hmacMessage, $hmacKey, true);

        if ($this->config->hmac->includesPadding) {
            $hmac = str_pad($hmac, $this->config->hmac->length, chr(0));
        }
    
        return $hmac;
    }

    protected function makeKey($salt, $password)
    {
        if ($this->config->truncatesMultibytePasswords) {
            $utf8Length = mb_strlen($password, 'utf-8');
            $password = substr($password, 0, $utf8Length);
        }

        $algo = $this->config->pbkdf2->prf;
        $iterations = $this->config->pbkdf2->iterations;
        $length = $this->config->pbkdf2->keyLength;

        return hash_pbkdf2($algo, $password, $salt, $iterations, $length, true);
    }

    protected function configure($version)
    {
        $config = new stdClass;

        $config->algorithm = 'aes-256-';
        $config->saltLength = 8;
        $config->ivLength = 16;

        $config->pbkdf2 = new stdClass;
        $config->pbkdf2->prf = 'sha1';
        $config->pbkdf2->iterations = 10000;
        $config->pbkdf2->keyLength = 32;

        $config->hmac = new stdClass();
        $config->hmac->length = 32;

        if (!$version) {
            $this->configureVersionZero($config);
        } elseif ($version <= 3) {
            $config->mode = 'cbc';
            $config->options = 1;
            $config->hmac->algorithm = 'sha256';
            $config->hmac->includesPadding = false;

            switch ($version) {
                case 1:
                    $config->hmac->includesHeader = false;
                    $config->truncatesMultibytePasswords = true;
                    break;

                case 2:
                    $config->hmac->includesHeader = true;
                    $config->truncatesMultibytePasswords = true;
                    break;

                case 3:
                    $config->hmac->includesHeader = true;
                    $config->truncatesMultibytePasswords = false;
                    break;
            }
        } else {
            throw new \RuntimeException('Unsupported schema version ' . $version);
        }

        $this->config = $config;
    }

    private function configureVersionZero(stdClass $config)
    {
        $config->mode = 'ctr';
        $config->options = 0;
        $config->hmac->includesHeader = false;
        $config->hmac->algorithm = 'sha1';
        $config->hmac->includesPadding = true;
        $config->truncatesMultibytePasswords = true;
    }
}
