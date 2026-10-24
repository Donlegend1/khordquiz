<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Support\MobilePasswordReset;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function store(ForgotPasswordRequest $request, MobilePasswordReset $resets): JsonResponse
    {
        $resets->send($request->string('email')->toString());

        return response()->json([
            'message' => "If an account exists for that email, we've sent a 6-digit code.",
        ]);
    }

    public function verify(VerifyOtpRequest $request, MobilePasswordReset $resets): JsonResponse
    {
        $resetToken = $resets->verify(
            $request->string('email')->toString(),
            $request->string('code')->toString(),
        );

        return response()->json([
            'message' => 'Code verified.',
            'reset_token' => $resetToken,
        ]);
    }

    public function update(ResetPasswordRequest $request, MobilePasswordReset $resets): JsonResponse
    {
        $resets->reset(
            $request->string('email')->toString(),
            $request->string('reset_token')->toString(),
            $request->string('password')->toString(),
        );

        return response()->json([
            'message' => 'Your password has been reset. Sign in with your new password.',
        ]);
    }
}
