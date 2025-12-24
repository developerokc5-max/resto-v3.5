<?php

namespace App\Services\RestoSuite;

use RuntimeException;
use Throwable;

class RestoSuiteException extends RuntimeException
{
    public readonly ?string $openapiCode;
    public readonly ?string $openapiMsg;
    public readonly mixed $detail;
    public readonly int $httpStatus;

    public function __construct(
        ?string $openapiCode = null,
        ?string $openapiMsg = null,
        mixed $detail = null,
        int $httpStatus = 0,
        ?Throwable $previous = null
    ) {
        $this->openapiCode = $openapiCode;
        $this->openapiMsg  = $openapiMsg;
        $this->detail      = $detail;
        $this->httpStatus  = $httpStatus;

        $message = $this->buildMessage($openapiCode, $openapiMsg, $detail, $httpStatus);

        // Use HTTP status as exception code if available, else 0
        parent::__construct($message, $httpStatus > 0 ? $httpStatus : 0, $previous);
    }

    private function buildMessage(?string $code, ?string $msg, mixed $detail, int $httpStatus): string
    {
        $base = trim(($msg ?: 'RestoSuite API error'));

        $parts = [];

        if (!empty($code)) {
            $parts[] = "openapi-code={$code}";
        }

        if ($httpStatus > 0) {
            $parts[] = "http={$httpStatus}";
        }

        if ($detail !== null && $detail !== '') {
            $parts[] = "detail=" . $this->safeJson($detail);
        }

        return $parts ? ($base . ' | ' . implode(' | ', $parts)) : $base;
    }

    private function safeJson(mixed $value): string
    {
        // If already string, keep it short
        if (is_string($value)) {
            return mb_substr($value, 0, 2000);
        }

        // Convert array/object to JSON safely
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '[unserializable detail]';
        }

        return mb_substr($json, 0, 2000);
    }

    public function context(): array
    {
        // Useful for logger()->error('...', $e->context());
        return [
            'openapiCode' => $this->openapiCode,
            'openapiMsg'  => $this->openapiMsg,
            'httpStatus'  => $this->httpStatus,
            'detail'      => $this->detail,
        ];
    }
}
