<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;

class TwoFactorAuthenticationController extends Controller
{    
    public function store(Request $request)
    {
        $user = $request->user();

        $enableAction = app(EnableTwoFactorAuthentication::class);
        $enableAction($user);

        return back()->with('status', 'two-factor-authentication-enabled');
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $confirmAction = app(ConfirmTwoFactorAuthentication::class);

        $confirmAction($request->user(), $request->code);

        return back()->with('status', 'two-factor-authentication-confirmed');
    }

    public function destroy(Request $request)
    {
        // Usamos la acción de Fortify directamente
        $disableAction = app(DisableTwoFactorAuthentication::class);
        $disableAction($request->user());

        return back()->with('status', 'two-factor-authentication-disabled');
    }
}