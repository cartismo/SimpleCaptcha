<?php

namespace Modules\SimpleCaptcha\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SimpleCaptcha\Services\SimpleCaptchaService;

class CaptchaController extends Controller
{
    public function __construct(
        protected SimpleCaptchaService $captchaService
    ) {}

    /**
     * Generate a new captcha challenge
     */
    public function generate(): JsonResponse
    {
        if (!$this->captchaService->isEnabled()) {
            return response()->json([
                'enabled' => false,
            ]);
        }

        $captcha = $this->captchaService->generate();

        return response()->json([
            'enabled' => true,
            ...$captcha,
        ]);
    }

    /**
     * Verify captcha response
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|string',
            'answer' => 'required|string',
        ]);

        $valid = $this->captchaService->verify(
            $request->input('id'),
            $request->input('answer')
        );

        return response()->json([
            'valid' => $valid,
        ]);
    }
}