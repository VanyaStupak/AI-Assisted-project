<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClaimPromoCodeRequest;
use App\Http\Resources\PromoCodeClaimResource;
use App\Models\PromoCodeClaim;
use App\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromoCodeController extends Controller
{
    public function __construct(private readonly PromoCodeService $promoCodeService) {}

    public function claim(ClaimPromoCodeRequest $request): JsonResponse
    {
        $result = $this->promoCodeService->claim($request->user(), $request->validated('code'));

        if (! $result->success) {
            return response()->json([
                'message' => $result->message,
                'error' => $result->reason,
            ], $result->httpStatus());
        }

        return response()->json([
            'message' => $result->message,
            'bonus_amount' => $result->bonusAmount,
            'balance' => $result->balance,
        ]);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');

        $query = $request->user()->promoCodeClaims()->latest();

        if (in_array($status, [PromoCodeClaim::STATUS_APPLIED, PromoCodeClaim::STATUS_REJECTED, PromoCodeClaim::STATUS_REVOKED], true)) {
            $query->where('status', $status);
        }

        return PromoCodeClaimResource::collection($query->paginate(10)->withQueryString());
    }

    public function revoke(Request $request, int $claim): JsonResponse
    {
        $result = $this->promoCodeService->revoke($request->user(), $claim);

        if (! $result->success) {
            return response()->json([
                'message' => $result->message,
                'error' => $result->reason,
            ], $result->httpStatus());
        }

        return response()->json([
            'message' => $result->message,
            'balance' => $result->balance,
        ]);
    }
}
