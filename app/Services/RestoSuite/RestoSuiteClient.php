<?php

namespace App\Services\RestoSuite;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RestoSuiteClient
{
    public function __construct(private readonly RestoSuiteAuth $auth) {}

    private function baseUrl(): string
    {
        return rtrim((string) config('restosuite.base_url'), '/');
    }

    private function headerName(string $key, string $fallback): string
    {
        return (string) config("restosuite.headers.$key", $fallback);
    }

    private function buildHeaders(): array
    {
        $corpId = (string) config('restosuite.corporation_id');
        $appKey = (string) config('restosuite.app_key');

        if ($corpId === '' || $appKey === '' || (int)$corpId <= 0) {
            throw new RestoSuiteException(
                'CONFIG_MISSING',
                'Missing/invalid RESTOSUITE_CORP_ID or RESTOSUITE_APP_KEY',
                ['corporation_id' => $corpId, 'app_key_set' => $appKey !== ''],
                0
            );
        }

        $timestamp = (string) ((int) floor(microtime(true) * 1000));
        $traceId   = md5($timestamp . '|' . Str::random(16));

        return [
            'Content-Type' => 'application/json',

            $this->headerName('corporation', 'Corporation-Id')      => $corpId,
            $this->headerName('app_key', 'RS-OpenAPI-AppKey')       => $appKey,
            $this->headerName('grant_type', 'RS-OpenAPI-GrantType') => 'token',
            $this->headerName('token', 'RS-OpenAPI-Token')          => $this->auth->getValidToken(),
            $this->headerName('timestamp', 'RS-OpenAPI-Timestamp')  => $timestamp,
            $this->headerName('trace_id', 'RS-OpenAPI-TraceId')     => $traceId,
        ];
    }

    private function post(string $endpoint, array $body): array
    {
        $url = $this->baseUrl() . $endpoint;

        $resp = Http::timeout(25)
            ->acceptJson()
            ->contentType('application/json')
            ->withHeaders($this->buildHeaders())
            ->post($url, $body);

        $json = $resp->json();
        if (!is_array($json)) {
            $json = [
                'openapi-code' => 'NON_JSON',
                'openapi-msg'  => 'Non-JSON response from RestoSuite',
                'raw'          => $resp->body(),
            ];
        }

        // retry once if token problem
        if ($this->isTokenProblem($resp->status(), $json)) {
            $this->auth->clearTokenCache();

            $resp = Http::timeout(25)
                ->acceptJson()
                ->contentType('application/json')
                ->withHeaders($this->buildHeaders())
                ->post($url, $body);

            $json = $resp->json();
            if (!is_array($json)) {
                $json = [
                    'openapi-code' => 'NON_JSON',
                    'openapi-msg'  => 'Non-JSON response from RestoSuite (after retry)',
                    'raw'          => $resp->body(),
                ];
            }
        }

        $this->throwIfError($resp->status(), $json, 'RestoSuite request failed');

        return $json;
    }

    private function throwIfError(int $httpStatus, array $json, string $fallbackMsg): void
    {
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

    private function isTokenProblem(int $httpStatus, array $json): bool
    {
        if (in_array($httpStatus, [401, 403], true)) return true;

        $code = (string) ($json['openapi-code'] ?? '');
        $msg  = strtolower((string) ($json['openapi-msg'] ?? ''));

        $tokenCodes = ['1001','1010','1011','2001','3001','TOKEN_EXPIRED','ILLEGAL_TOKEN'];

        if (in_array($code, $tokenCodes, true)) return true;

        return str_contains($msg, 'token') && (str_contains($msg, 'illegal') || str_contains($msg, 'expire'));
    }

    public function getShops(int $pageNo = 1, int $pageSize = 50): array
    {
        $endpoint = (string) config('restosuite.endpoints.shop_list', '/api/v1/bo/shop/queryShopList');

        $json = $this->post($endpoint, [
            'pageNo'   => $pageNo,
            'pageSize' => $pageSize,
        ]);

        return $json['biz-data']['list'] ?? [];
    }

    public function getItems(string $shopId, int $pageNo = 1, int $pageSize = 100): array
    {
        $endpoint = (string) config('restosuite.endpoints.item_list', '/api/v1/bo/menu/queryItemList');

        $corpId = (int) config('restosuite.corporation_id');

        $json = $this->post($endpoint, [
            'corporationId' => $corpId,
            'shopId'        => (int) $shopId,
            'page' => [
                'pageNo'   => $pageNo,
                'pageSize' => $pageSize,
            ],
        ]);

        return $json['biz-data']['list'] ?? [];
    }
}
