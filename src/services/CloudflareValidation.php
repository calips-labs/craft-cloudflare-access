<?php

namespace calips\cfaccess\services;

use calips\cfaccess\CloudflareAccess;
use calips\cfaccess\verification\VerificationResult;
use CoderCat\JWKToPEM\Exception\Base64DecodeException;
use CoderCat\JWKToPEM\Exception\JWKConverterException;
use CoderCat\JWKToPEM\JWKConverter;
use Craft;
use craft\helpers\App;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use yii\base\Component;

/**
 * Cloudflare Validation service
 */
class CloudflareValidation extends Component
{
    private array $keys = [];

    public function getJwtFromHeaders(): ?string
    {
        return Craft::$app->request->headers->get('Cf-Access-Jwt-Assertion');
    }

    public function verifyJwt($jwt): VerificationResult
    {
        $this->downloadKeys();

        $result = new VerificationResult();
        $result->valid = false;

        // Retrieve key from raw JWT:
        $idToken = rawurldecode($jwt);
        $signature = json_decode(base64_decode(explode('.', $idToken)[0]), true);

        if ($signature == null || !isset($signature['kid'])) {
            $result->failureReason = VerificationResult::FAILURE_INVALID_JWT;
            Craft::warning("Login failure: Invalid JWT signature or no key ID found in JWT signature", 'cloudflare-access');
            return $result;
        }

        $keyId = $signature['kid'];

        // Check for key:
        if (!isset($this->keys[$keyId])) {
            $result->failureReason = VerificationResult::FAILURE_INVALID_KEY;
            Craft::warning("Login failure: Key {$keyId} sent by user in JWT not found in downloaded keys", 'cloudflare-access');
            return $result;
        }

        $key = $this->keys[$keyId];

        // Configure checker:
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($key),
            InMemory::plainText($key),
        );

        // Parse token:
        $token = $configuration->parser()->parse($jwt);

        // Check whether it is issued for this app:
        if (!$token->isPermittedFor(CloudflareAccess::getInstance()->settings->getAud())) {
            $result->failureReason = VerificationResult::FAILURE_WRONG_AUD;
            Craft::warning("Login failure: AUD not found in permitted audiences", 'cloudflare-access');
            return $result;
        }

        // Check whether it is issued by Cloudflare:
        if (!$token->hasBeenIssuedBy($this->getIssuerUrl())) {
            $result->failureReason = VerificationResult::FAILURE_WRONG_ISSUER;
            Craft::warning("Login failure: JWT was not signed by isser {$this->getIssuerUrl()}", 'cloudflare-access');
            return $result;
        }

        // Check whether we have an email:
        if (!$token->claims()->has('email')) {
            $result->failureReason = VerificationResult::FAILURE_NO_EMAIL;
            Craft::warning("Login failure: JWT does not contain an e-mail address", 'cloudflare-access');
            return $result;
        }

        $email = $token->claims()->get('email');
        $result->username = $email;
        $result->valid = true;

        Craft::debug("Valid token for {$email}", 'cloudflare-access');

        return $result;
    }

    protected function getIssuerUrl(): string
    {
        $issuer = CloudflareAccess::getInstance()->settings->issuer;

        if (!str_starts_with($issuer, 'https://')) {
            $issuer = 'https://' . $issuer;
        }

        return $issuer;
    }

    protected function getJwksUrl(): string
    {
        return $this->getIssuerUrl() . '/cdn-cgi/access/certs';
    }

    /**
     * Download the keys from
     * @return void
     * @throws \Exception
     */
    protected function downloadKeys(): void
    {
        $jwksUrl = $this->getJwksUrl();

        $client = new Client();

        try {
            $result = $client->request('GET', $jwksUrl);
        } catch (GuzzleException $exception) {
            throw new \Exception('Could not fetch JWKS: Guzzle error', 0, $exception);
        }

        $json = $result->getBody();
        $jwks = json_decode($json);

        foreach ($jwks->keys as $key) {
            $jwkConverter = new JWKConverter();
            try {
                $decodedKey = $jwkConverter->toPEM((array)$key);
            } catch (Base64DecodeException $e) {
                throw new \Exception('Could not decode base-64 encoded keys');
            } catch (JWKConverterException $e) {
                throw new \Exception('JWKS conversion error');
            }

            $this->keys[$key->kid] = $decodedKey;
        }
    }
}
