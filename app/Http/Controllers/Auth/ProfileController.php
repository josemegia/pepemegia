<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // Para manejar la subida de archivos
use Illuminate\Validation\Rule; // Para la validación de email único
use Illuminate\Support\Facades\Hash; // ¡Nuevo! Para verificar y hashear contraseñas
use Illuminate\Validation\ValidationException; // ¡Nuevo! Para errores de validación personalizados

class ProfileController extends Controller
{
    /**
     * Muestra el formulario de edición de perfil.
     */
    public function edit()
    {
        return view('auth.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Actualiza la información del perfil del usuario.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Reglas de validación para la información del perfil
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'max:2048'], // Validar que sea imagen y tamaño máximo 2MB
        ]);

        // Manejar la subida de la foto de perfil
        if ($request->hasFile('profile_photo')) {
            // Eliminar la foto anterior si existe
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            // Guardar la nueva foto en el disco 'public' dentro de la carpeta 'profile-photos'
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        // Rellenar los campos del usuario con los datos del request
        $user->fill($request->only([
            'name',
            'email',
            'address',
            'city',
            'country',
            'phone_number',
        ]));

        // Si el email ha cambiado, marcarlo como no verificado (si usas verificación de email)
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', '¡Perfil actualizado con éxito!');
    }

    /**
     * Actualiza la contraseña del usuario.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'], // min:8, confirmed para que coincida con password_confirmation
        ]);

        // Verificar que la contraseña actual sea correcta
        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('La contraseña actual proporcionada es incorrecta.')],
            ]);
        }

        // Actualizar la contraseña
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile.edit')->with('status', '¡Contraseña actualizada con éxito!');
    }

    /**
     * Elimina la cuenta del usuario.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string'], // Campo 'password' para confirmar la eliminación
        ]);

        $user = Auth::user();

        // Verificar que la contraseña ingresada coincide con la del usuario
        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => [__('Esta contraseña no coincide con nuestros registros y es necesaria para confirmar la eliminación de la cuenta.')],
            ]);
        }

        // ¡Opcional! Eliminar la foto de perfil del almacenamiento si existe
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        // Eliminar el usuario de la base de datos
        $user->delete();

        // Cerrar la sesión del usuario
        Auth::guard('web')->logout();

        // Invalidar la sesión y regenerar el token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Tu cuenta ha sido eliminada exitosamente.');
    }
}