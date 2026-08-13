<?php

namespace App\Services;

use App\DTOs\PromoClaimResult;
use App\DTOs\PromoRevokeResult;
use App\Models\PromoCode;
use App\Models\PromoCodeClaim;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    public function claim(User $user, string $rawCode): PromoClaimResult
    {
        $code = strtoupper($rawCode);

        return DB::transaction(function () use ($user, $code) {
            $promoCode = PromoCode::where('code', $code)->lockForUpdate()->first();

            if (! $promoCode) {
                $this->logRejected($user, $code, null, 'not_found');

                return PromoClaimResult::failure('not_found', 'Промокод не знайдено.');
            }

            if ($promoCode->isExpired()) {
                $this->logRejected($user, $code, $promoCode, 'expired');

                return PromoClaimResult::failure('expired', 'Термін дії промокоду закінчився.');
            }

            $alreadyUsed = PromoCodeClaim::query()
                ->where('user_id', $user->id)
                ->where('promo_code_id', $promoCode->id)
                ->whereIn('status', [PromoCodeClaim::STATUS_APPLIED, PromoCodeClaim::STATUS_REVOKED])
                ->exists();

            if ($alreadyUsed) {
                $this->logRejected($user, $code, $promoCode, 'already_used');

                return PromoClaimResult::failure('already_used', 'Ви вже використали цей промокод раніше.');
            }

            User::whereKey($user->id)->lockForUpdate()->increment('balance', $promoCode->bonus_amount);
            $user->refresh();

            PromoCodeClaim::create([
                'user_id' => $user->id,
                'promo_code_id' => $promoCode->id,
                'code' => $promoCode->code,
                'status' => PromoCodeClaim::STATUS_APPLIED,
                'bonus_amount' => $promoCode->bonus_amount,
            ]);

            return PromoClaimResult::success((float) $promoCode->bonus_amount, (float) $user->balance);
        });
    }

    public function revoke(User $user, int $claimId): PromoRevokeResult
    {
        return DB::transaction(function () use ($user, $claimId) {
            $claim = PromoCodeClaim::where('id', $claimId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $claim) {
                return PromoRevokeResult::failure('not_found', 'Нарахування не знайдено.');
            }

            if ($claim->status === PromoCodeClaim::STATUS_REVOKED) {
                return PromoRevokeResult::failure('already_revoked', 'Це нарахування вже було скасовано.');
            }

            if ($claim->status !== PromoCodeClaim::STATUS_APPLIED) {
                return PromoRevokeResult::failure('not_applied', 'Скасувати можна лише застосований промокод.');
            }

            User::whereKey($user->id)->lockForUpdate()->decrement('balance', $claim->bonus_amount);

            $claim->update([
                'status' => PromoCodeClaim::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);

            $user->refresh();

            return PromoRevokeResult::success((float) $user->balance);
        });
    }

    private function logRejected(User $user, string $code, ?PromoCode $promoCode, string $reason): void
    {
        PromoCodeClaim::create([
            'user_id' => $user->id,
            'promo_code_id' => $promoCode?->id,
            'code' => $code,
            'status' => PromoCodeClaim::STATUS_REJECTED,
            'reason' => $reason,
        ]);
    }
}
