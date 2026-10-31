<?php

namespace Tests\Feature;

use App\Services\AppleSignedTransaction;
use Illuminate\Foundation\Testing\TestCase;
use RuntimeException;

class AppleSignedTransactionTest extends TestCase
{
    private array $files = [];

    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) unlink($file);
        parent::tearDown();
    }

    private function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'apple-test-');
        file_put_contents($path, $contents);
        $this->files[] = $path;
        return $path;
    }

    private function fixture(array $overrides = []): array
    {
        $config = $this->file("[req]\ndistinguished_name=dn\n[dn]\n[ca]\nbasicConstraints=critical,CA:true\nkeyUsage=critical,keyCertSign,cRLSign\nsubjectKeyIdentifier=hash\n[intermediate]\nbasicConstraints=critical,CA:true,pathlen:0\nkeyUsage=critical,keyCertSign,cRLSign\nsubjectKeyIdentifier=hash\nauthorityKeyIdentifier=keyid\n1.2.840.113635.100.6.2.1=ASN1:NULL\n[leaf]\nbasicConstraints=critical,CA:false\nkeyUsage=critical,digitalSignature\nsubjectKeyIdentifier=hash\nauthorityKeyIdentifier=keyid\n1.2.840.113635.100.6.11.1=ASN1:NULL\n");
        $certs = [];
        $issuer = null;
        $issuerKey = null;
        foreach (['ca', 'intermediate', 'leaf'] as $index => $section) {
            $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'private_key_bits' => 2048, 'curve_name' => 'prime256v1', 'config' => $config]);
            $csr = openssl_csr_new(['commonName' => 'Test '.$section], $key, ['config' => $config, 'digest_alg' => 'sha256']);
            $cert = openssl_csr_sign($csr, $issuer, $issuerKey ?: $key, 2,
                ['config' => $config, 'x509_extensions' => $section, 'digest_alg' => 'sha256'], $index + 1);
            $this->assertNotFalse($cert);
            openssl_x509_export($cert, $pem);
            $certs[] = $pem;
            $issuer = $cert;
            $issuerKey = $key;
        }
        $root = $this->file($certs[0]);
        $chain = array_map(fn ($pem) => preg_replace('/-----[^\n]+-----|\s/', '', $pem), array_reverse($certs));
        $encode = fn ($value) => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $body = $encode(json_encode(['alg' => 'ES256', 'x5c' => $chain])) . '.' . $encode(json_encode(array_merge([
            'bundleId' => config('services.store_iap.apple_bundle_id'), 'environment' => 'Production', 'transactionId' => '123',
        ], $overrides)));
        openssl_sign($body, $der, $key, OPENSSL_ALGO_SHA256);
        $rLength = ord($der[3]);
        $r = substr($der, 4, $rLength);
        $sLength = ord($der[5 + $rLength]);
        $s = substr($der, 6 + $rLength, $sLength);
        $signature = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT)
            . str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $verifier = new class($root) extends AppleSignedTransaction {
            private string $root;
            public function __construct(string $root) { $this->root = $root; }
            protected function trustedRootPath(): string { return $this->root; }
        };
        return [$verifier, $body . '.' . $encode($signature)];
    }

    public function test_signed_chain_and_signature_are_verified(): void
    {
        [$verifier, $jws] = $this->fixture();
        $this->assertSame('123', $verifier->decode($jws)['transactionId']);
        $parts = explode('.', $jws);
        $parts[1] = rtrim(strtr(base64_encode('{"transactionId":"tampered"}'), '+/', '-_'), '=');
        $this->expectException(RuntimeException::class);
        $verifier->decode(implode('.', $parts));
    }

    public function test_caller_supplied_root_is_not_trusted(): void
    {
        [, $jws] = $this->fixture();
        $this->expectException(RuntimeException::class);
        (new AppleSignedTransaction())->decode($jws);
    }

    public function test_another_apps_transaction_is_rejected(): void
    {
        [$verifier, $jws] = $this->fixture(['bundleId' => 'another.app']);
        $this->expectException(RuntimeException::class);
        $verifier->decode($jws);
    }
}
