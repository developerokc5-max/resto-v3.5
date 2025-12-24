<?php

namespace App\Services\RestoSuite;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class RestoSuiteAuth
{
    private string $baseUrl;
    private string $appKey;
    private string $secretKey;   // raw secret key from portal
    private string $secretCode;  // sha256(secretKey)
    private int $corpId;

    private string $tokenKey;
    private string $refreshKey;
    private string $expiresAtKey;
    private string $lockKey;

    private int $safetySeconds;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('restosuite.base_url'), '/');
        $this->appKey    = trim((string) config('restosuite.app_key'));
        $this->secretKey = (string) config('restosuite.secret_key');
        $this->corpId    = (int) config('restosuite.corporation_id');

        $this->tokenKey   = (string) config('restosuite.cache.token_key', 'restosuite.token');
        $this->refreshKey = (string) config('restosuite.cache.refresh_key', 'restosuite.refresh_token');
        $this->lockKey    = (string) config('restosuite.cache.lock_key', 'restosuite.token.lock');
        $this->expiresAtKey = $this->tokenKey . '.expires_at';

        $this->safetySeconds = (int) config('restosuite.token_safety_seconds', 120);

        // Generate secretCode EXACTLY as doc: SHA256(secretKey) hex
        $this->secretCode = hash('sha256', $this->secretKey);

        $this->assertConfigured();
    }

    public function getValidToken(): string
    {
        $token     = (string) Cache::get($this->tokenKey, '');
        $expiresAt = (int) Cache::get($this->expiresAtKey, 0);

        if ($token !== '' && $expiresAt > time()) {
            return $token;
        }

        return Cache::lock($this->lockKey, 15)->block(12, function () {
            $token     = (string) Cache::get($this->tokenKey, '');
            $expiresAt = (int) Cache::get($this->expiresAtKey, 0);

            if ($token !== '' && $expiresAt > time()) {
                return $token;
            }

            $refresh = (string) Cache::get($this->refreshKey, '');
            if ($refresh !== '') {
                try {
                    return $this->refreshToken($refresh);
                } catch (Throwable $e) {
                    $this->clearTokenCache();
                }
            }

            return $this->requestNewToken();
        });
    }

    public function requestNewToken(): string
    {
        $path = (string) config('restosuite.auth.get_token', '/oauth/getToken');

        $payload = [
            'appKey'     => $this->appKey,
            'secretCode' => $this->secretCode, // MUST be sha256(secretKey)
            'grantType'  => 'app_secret',
        ];

        $resp = $this->postJson($this->baseUrl . $path, $payload);
        $json = $this->safeJson($resp);

        $this->throwIfOpenApiError($resp, $json, 'GetToken failed');

        $biz = $json['biz-data'] ?? [];
        $token = (string) ($biz['token'] ?? '');
        $refresh = (string) ($biz['refreshToken'] ?? '');
        $expiresSecond = (int) ($biz['expiresSecond'] ?? 0);

        if ($token === '' || $expiresSecond <= 0) {
            throw new RestoSuiteException('TOKEN_EMPTY', 'Token response missing token/expiresSecond', $json, $resp->status());
        }

        $this->storeToken($token, $refresh, $expiresSecond);
        return $token;
    }

    public function refreshToken(string $refreshToken): string
    {
        $path = (string) config('restosuite.auth.refresh_token', '/oauth/refreshToken');

        $payload = [
            'appKey'       => $this->appKey,
            'refreshToken' => $refreshToken,
            'grantType'    => 'refresh_token',
        ];

        $resp = $this->postJson($this->baseUrl . $path, $payload);
        $json = $this->safeJson($resp);

        $this->throwIfOpenApiError($resp, $json, 'RefreshToken failed');

        $biz = $json['biz-data'] ?? [];
        $token = (string) ($biz['token'] ?? '');
        $refresh = (string) ($biz['refreshToken'] ?? $refreshToken);
        $expiresSecond = (int) ($biz['expiresSecond'] ?? 0);

        if ($token === '' || $expiresSecond <= 0) {
            throw new RestoSuiteException('TOKEN_EMPTY', 'Refresh response missing token/expiresSecond', $json, $resp->status());
        }

        $this->storeToken($token, $refresh, $expiresSecond);
        return $token;
    }

    public function clearTokenCache(): void
    {
        Cache::forget($this->tokenKey);
        Cache::forget($this->refreshKey);
        Cache::forget($this->expiresAtKey);
    }

    private function storeToken(string $token, string $refresh, int $expiresSecond): void
    {
        $ttl = max(60, $expiresSecond - $this->safetySeconds);
        $expiresAt = time() + $ttl;

        Cache::put($this->tokenKey, $token, $ttl);
        Cache::put($this->expiresAtKey, $expiresAt, $ttl);

        if ($refresh !== '') {
            Cache::put($this->refreshKey, $refresh, max(3600, $ttl));
        }
    }

    private function assertConfigured(): void
    {
        $missing = [];
        if ($this->baseUrl === '') $missing[] = 'RESTOSUITE_BASE_URL';
        if ($this->appKey === '') $missing[] = 'RESTOSUITE_APP_KEY';
        if ($this->secretKey === '') $missing[] = 'RESTOSUITE_SECRET_KEY';
        if ($this->corpId <= 0) $missing[] = 'RESTOSUITE_CORP_ID';

        // secretCode must be 64 hex chars
        if (!preg_match('/^[a-f0-9]{64}$/i', $this->secretCode)) {
            throw new RestoSuiteException(
                'SECRET_CODE_INVALID',
                'secretCode must be SHA256(secretKey) hex (64 chars). Your secret key may be wrong or not loaded.',
                ['secretCode' => $this->secretCode, 'len' => strlen($this->secretCode)]
            );
        }

        if (!empty($missing)) {
            throw new RestoSuiteException('CONFIG_MISSING', 'Missing .env: ' . implode(', ', $missing));
        }
    }

    private function postJson(string $url, array $payload): Response
    {
        return Http::timeout(20)
            ->acceptJson()
            ->contentType('application/json')
            ->post($url, $payload);
    }

    private function safeJson(Response $resp): array
    {
        $json = $resp->json();
        return is_array($json) ? $json : [];
    }

    private function throwIfOpenApiError(Response $resp, array $json, string $fallbackMsg): void
    {
        $httpStatus = $resp->status();
        $code = $json['openapi-code'] ?? null;

        if ($httpStatus >= 400) {
            throw new RestoSuiteException(
                is_string($code) ? $code : ('HTTP_' . $httpStatus),
                (string) ($json['openapi-msg'] ?? $fallbackMsg),
                $json['openapi-error-detail'] ?? $json,
                $httpStatus
            );
        }

        if ($code !== '0' && $code !== 0) {
            throw new RestoSuiteException(
                (string) $code,
                (string) ($json['openapi-msg'] ?? $fallbackMsg),
                $json['openapi-error-detail'] ?? $json,
                $httpStatus
            );
        }
    }
}
