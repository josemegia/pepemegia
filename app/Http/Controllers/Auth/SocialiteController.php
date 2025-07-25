<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialiteController extends Controller
{
    /**
     * Redirige al usuario al proveedor de OAuth.
     *
     * @param string $provider El nombre del proveedor (ej. 'google', 'microsoft', 'twitter-oauth-2').
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToProvider(string $provider)
    {
        $allowedProviders = array_keys(config('socialite'));

        // Seguridad: solo permitir proveedores conocidos
        if (!in_array($provider, $allowedProviders)) {
            abort(403, 'Proveedor no permitido');
        }

        // Seguridad: validar que la configuración del proveedor exista
        if (!config("services.$provider")) {
            return redirect('/login')->withErrors("No hay configuración válida para $provider.");
        }

        try {
            $socialiteDriver = Socialite::driver($provider);

            if ($provider === 'google') {
                $socialiteDriver->scopes(config('services.google.scopes'))
                                ->with([
                                    'access_type' => 'offline',
                                    'prompt' => 'consent',
                                ]);
            }

            return $socialiteDriver->redirect();

        } catch (\Exception $e) {
            report($e); // Registra el error en laravel.log
            return redirect('/login')->withErrors('No se pudo conectar con ' . ucfirst($provider) . '.');
        }
    }

    /**
     * Maneja la respuesta del proveedor de OAuth después de la autenticación.
     *
     * @param string $provider El nombre del proveedor.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            // Manejar error si la autenticación falló (ej. usuario denegó permisos).
            return redirect('/login')->withErrors('Error al autenticar con ' . ucfirst($provider) . '.');
        }

        // Buscar si ya existe un usuario con este email o con el mismo social_provider_id
        $user = User::where('email', $socialUser->getEmail())
                    ->orWhere(function ($query) use ($provider, $socialUser) {
                        $query->where('social_provider_name', $provider)
                              ->where('social_provider_id', $socialUser->getId());
                    })
                    ->first();

        if ($user) {
            // Si el usuario ya existe, actualizar sus datos sociales y loguearlo
            $user->social_provider_name = $provider;
            $user->social_provider_id = $socialUser->getId();
            $user->social_provider_token = $socialUser->token;
            $user->social_provider_refresh_token = $socialUser->refreshToken; // Puede ser null
            $user->save();

            Auth::login($user, true); // true para recordar sesión
        } else {
            // Si el usuario no existe, crear uno nuevo
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname(), // Usar nickname si no hay nombre
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(uniqid()), // Generar una contraseña aleatoria y fuerte (no se usará si siempre se loguea por social)
                'email_verified_at' => now(), // Asumimos verificado por el proveedor
                'social_provider_name' => $provider,
                'social_provider_id' => $socialUser->getId(),
                'social_provider_token' => $socialUser->token,
                'social_provider_refresh_token' => $socialUser->refreshToken, // Puede ser null
            ]);

            Auth::login($user, true);
        }

        // Redirigir al dashboard o a donde sea después del login
        return redirect('/'); // Ajusta tu ruta de redirección post-login
    }
}
