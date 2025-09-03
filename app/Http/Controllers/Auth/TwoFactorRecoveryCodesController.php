<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TwoFactorRecoveryCodesController extends Controller
{
    public function index(Request $request)
    {
        if (is_null($request->user()->two_factor_secret) ||
            is_null($request->user()->two_factor_confirmed_at)) {
            return [];
        }

        return Response::json(json_decode(decrypt($request->user()->two_factor_recovery_codes), true));
    }
}