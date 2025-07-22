<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RecaptchaBlockedIp;

class RecaptchaBlockController extends Controller
{
    public function index()
    {
        $blockedIps = RecaptchaBlockedIp::latest('blocked_at')->paginate(25);
        return view('admin.recaptcha-blocks.index', compact('blockedIps'));
    }

    public function destroy($ip)
    {
        RecaptchaBlockedIp::where('ip', $ip)->delete();

        return redirect()->route('admin.recaptcha-blocks.index')
            ->with('status', "La IP {$ip} ha sido desbloqueada.");
    }
}

