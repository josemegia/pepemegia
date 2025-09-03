<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetResponse;

class InvalidPasswordResetTokenResponse implements FailedPasswordResetResponse
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @param  string  $status
     * @return void
     */
    public function __construct(string $status)
    {
        $this->status = $status;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toResponse($request): RedirectResponse
    {
        // Esta es la lógica que redirige al usuario si el token es inválido.
        return redirect()->route('password.request')
            ->withErrors(['email' => __($this->status)]);
    }
}