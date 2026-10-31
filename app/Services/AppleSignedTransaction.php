<?php

namespace App\Services;

use RuntimeException;

/** Verifies StoreKit 2 JWS transactions against Apple's bundled trust anchor. */
class AppleSignedTransaction
{
    public function decode(string $jws): array
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid signed transaction.');
        }
        $header = json_decode($this->decodeBase64($parts[0]), true, 32, JSON_THROW_ON_ERROR);
        $chain = $header['x5c'] ?? [];
        if (($header['alg'] ?? null) !== 'ES256' || !is_array($chain) || count($chain) !== 3) {
            throw new RuntimeException('Invalid Apple signing certificate chain.');
        }
        $leaf = $this->certificate($chain[0]);
        $intermediate = $this->certificate($chain[1]);
        $leafDetails = openssl_x509_parse($leaf);
        $intermediateDetails = openssl_x509_parse($intermediate);
        if (!isset($leafDetails['extensions']['1.2.840.113635.100.6.11.1'])
            || !isset($intermediateDetails['extensions']['1.2.840.113635.100.6.2.1'])) {
            throw new RuntimeException('Certificate is not an App Store signing certificate.');
        }

        // Never trust the root supplied by the caller. OpenSSL validates the chain,
        // CA constraints and certificate validity against our Apple root only.
        $chainFile = tempnam(sys_get_temp_dir(), 'apple-chain-');
        if ($chainFile === false) {
            throw new RuntimeException('Cannot verify Apple certificate chain.');
        }
        try {
            file_put_contents($chainFile, $intermediate);
            $valid = openssl_x509_checkpurpose($leaf, X509_PURPOSE_ANY,
                [$this->trustedRootPath()], $chainFile);
            if ($valid !== true && $valid !== 1) {
                throw new RuntimeException('Untrusted or expired Apple certificate.');
            }
        } finally {
            unlink($chainFile);
        }
        $key = openssl_pkey_get_public($leaf);
        $keyDetails = $key ? openssl_pkey_get_details($key) : false;
        if (($keyDetails['ec']['curve_name'] ?? null) !== 'prime256v1') {
            throw new RuntimeException('Invalid Apple signing key.');
        }
        $signature = $this->decodeBase64($parts[2]);
        if (strlen($signature) !== 64) {
            throw new RuntimeException('Invalid Apple signature.');
        }
        $integers = '';
        foreach (str_split($signature, 32) as $integer) {
            $integer = ltrim($integer, "\x00") ?: "\x00";
            if (ord($integer[0]) > 127) $integer = "\x00" . $integer;
            $integers .= "\x02" . chr(strlen($integer)) . $integer;
        }
        $der = "\x30" . chr(strlen($integers)) . $integers;
        if (openssl_verify($parts[0] . '.' . $parts[1], $der, $key, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Invalid Apple signature.');
        }
        $payload = json_decode($this->decodeBase64($parts[1]), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($payload)
            || ($payload['bundleId'] ?? null) !== config('services.store_iap.apple_bundle_id')
            || !in_array($payload['environment'] ?? null, ['Production', 'Sandbox'], true)) {
            throw new RuntimeException('Apple transaction belongs to another app or environment.');
        }
        return $payload;
    }

    private function decodeBase64(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) throw new RuntimeException('Invalid transaction encoding.');
        return $decoded;
    }

    protected function trustedRootPath(): string
    {
        return resource_path('certificates/AppleRootCA-G3.pem');
    }

    private function certificate(string $value): string
    {
        return "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(base64_encode($this->decodeBase64($value)), 64, "\n")
            . "-----END CERTIFICATE-----\n";
    }
}
