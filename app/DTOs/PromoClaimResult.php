<?php

namespace App\DTOs;

class PromoClaimResult
{
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $reason = null,
        public readonly ?float $bonusAmount = null,
        public readonly ?float $balance = null,
    ) {}

    public static function success(float $bonusAmount, float $balance): self
    {
        return new self(
            success: true,
            message: 'Промокод успішно застосовано.',
            bonusAmount: $bonusAmount,
            balance: $balance,
        );
    }

    public static function failure(string $reason, string $message): self
    {
        return new self(success: false, message: $message, reason: $reason);
    }

    public function httpStatus(): int
    {
        return match ($this->reason) {
            'not_found' => 404,
            'expired' => 410,
            'already_used' => 409,
            default => 422,
        };
    }
}
