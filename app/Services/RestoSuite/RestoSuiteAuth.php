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
    private string $secretKey;   // raw Secret Key from portal
    private string $secretCode;  // sha256(secretKey) hex
    private int $corpId;

    private string $tokenKey;
    private string $refreshKey;
    private string $expiresAtKey;
    private string $lockKey;

    private string $cooldownKey; // unix timestamp (int)
    private int $safetySeconds;

    public function __construct()
    {
        $this->baseUrl   = rtrim((string) config('restosuite.base_url'), '/');
        $this->appKey    = trim((string) config('restosuite.app_key'));
        $this->secretKey = (string) config('restosuite.secret_key');
        $this->corpId    = (int) config('restosuite.corporation_id');

        $this->tokenKey     = (string) config('restosuite.cache.token_key', 'restosuite.token');
        $this->refreshKey   = (string) config('restosuite.cache.refresh_key', 'restosuite.refresh_token');
        $this->lockKey      = (string) config('restosuite.cache.lock_key', 'restosuite.token.lock');
        $this->expiresAtKey = $this->tokenKey . '.expires_at';

        $this->cooldownKey    = $this->tokenKey . '.cooldown_until';
        $this->safetySeconds  = (int) config('restosuite.token_safety_seconds', 120);

        // SHA256(secretKey) hex (64 chars)
        $this->secretCode = hash('sha256', (string) $this->secretKey);

        $this->assertConfigured();
    }

    public function getValidToken(): string
    {
        // 1) fast path: cached token still valid
        $token     = (string) Cache::get($this->tokenKey, '');
        $expiresAt = (int) Cache::get($this->expiresAtKey, 0);
        if ($token !== '' && $expiresAt > time()) {
            return $token;
        }

        // 2) lock so multi-process does not spam getToken
        return Cache::lock($this->lockKey, 20)->block(15, function () {
            // re-check after lock
            $token     = (string) Cache::get($this->tokenKey, '');
            $expiresAt = (int) Cache::get($this->expiresAtKey, 0);
            if ($token !== '' && $expiresAt > time()) {
                return $token;
            }

            // try refresh first
            $refresh = (string) Cache::get($this->refreshKey, '');
            if ($refresh !== '') {
                try {
                    return $this->refreshToken($refresh);
                } catch (Throwable $e) {
                    // refresh failed -> fallback to getToken
                    $this->clearTokenCache();
                }
            }

            // only check cooldown right before requesting NEW token
            $this->guardCooldownOrThrow();

            return $this->requestNewToken();
        });
    }

    private function guardCooldownOrThrow(): void
    {
        $cooldownUntil = (int) Cache::get($this->cooldownKey, 0);
        if ($cooldownUntil > time()) {
            throw new RestoSuiteException(
                'TOKEN_COOLDOWN',
                'Token endpoint rate-limited. Wait and retry.',
                [
                    'cooldown_until' => date('Y-m-d H:i:s', $cooldownUntil),
                    'seconds_left'   => $cooldownUntil - time(),
                ]
            );
        }
    }

    public function requestNewToken(): string
    {
        $path = (string) config('restosuite.auth.get_token', '/oauth/getToken');

        $payload = [
            'appKey'     => $this->appKey,
            'secretCode' => $this->secretCode,
            'grantType'  => 'app_secret',
        ];

        $resp = $this->postJson($this->baseUrl . $path, $payload);
        $json = $this->safeJson($resp);

        // If vendor rate limits token endpoint, store cooldown + throw
        $this->handleTokenCooldownIfAny($json);

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

        // Rare, but if they rate-limit refresh too, respect it.
        $this->handleTokenCooldownIfAny($json);

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

    private function handleTokenCooldownIfAny(array $json): void
    {
        $code = (string) ($json['openapi-code'] ?? '');

        // Case A: openapi-code=5, msg="Forbidden to get token frequently"
        if ($code === '5') {
            // default 60s
            Cache::put($this->cooldownKey, time() + 60, 60);
            return;
        }

        // Case B: openapi-code=TOKEN_COOLDOWN with detail.cooldown_until
        if ($code === 'TOKEN_COOLDOWN') {
            $detail = $json['detail'] ?? $json['openapi-error-detail'] ?? null;

            $cooldownUntilStr = null;
            if (is_array($detail)) {
                $cooldownUntilStr = $detail['cooldown_until'] ?? $detail['cooldownUntil'] ?? null;
            }

            if (is_string($cooldownUntilStr) && $cooldownUntilStr !== '') {
                $ts = strtotime($cooldownUntilStr);
                if ($ts !== false) {
                    $ttl = max(1, $ts - time());
                    Cache::put($this->cooldownKey, $ts, $ttl);
                    return;
                }
            }

            // fallback: 60s if vendor didn't send a parseable time
            Cache::put($this->cooldownKey, time() + 60, 60);
        }
    }

    public function clearTokenCache(): void
    {
        Cache::forget($this->tokenKey);
        Cache::forget($this->refreshKey);
        Cache::forget($this->expiresAtKey);
        Cache::forget($this->cooldownKey);
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

        // clear cooldown since we got a token successfully
        Cache::forget($this->cooldownKey);
    }

    private function assertConfigured(): void
    {
        $missing = [];
        if ($this->baseUrl === '') $missing[] = 'RESTOSUITE_BASE_URL';
        if ($this->appKey === '') $missing[] = 'RESTOSUITE_APP_KEY';
        if (trim($this->secretKey) === '') $missing[] = 'RESTOSUITE_SECRET_KEY';
        if ($this->corpId <= 0) $missing[] = 'RESTOSUITE_CORP_ID';

        if (!preg_match('/^[a-f0-9]{64}$/i', $this->secretCode)) {
            throw new RestoSuiteException(
                'SECRET_CODE_INVALID',
                'secretCode must be SHA256(secretKey) hex (64 chars). If your secret contains $ or #, wrap it in single quotes in .env.',
                ['len' => strlen($this->secretCode)]
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
