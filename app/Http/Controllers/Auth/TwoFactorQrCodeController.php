<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class TwoFactorQrCodeController extends Controller
{
    public function show(Request $request)
    {
        if (is_null($request->user()->two_factor_secret)) {
            return [];
        }

        return Response::make($request->user()->twoFactorQrCodeSvg(), 200, ['Content-Type' => 'image/svg+xml']);
    }
}