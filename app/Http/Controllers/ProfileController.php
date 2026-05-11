<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form in dashboard style.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Todos os usuários logados usam o template do dashboard
        return view('profile.dashboard.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $previousLogoPath = $user->company_logo_path;

        $user->fill($validated);

        if ($request->hasFile('company_logo')) {
            $user->company_logo_path = $request->file('company_logo')->store('company-logos', 'public');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (filled($previousLogoPath) && $previousLogoPath !== $user->company_logo_path) {
            Storage::disk('public')->delete($previousLogoPath);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Perfil atualizado com sucesso.',
                'redirect' => '/app/perfil',
                'profile' => [
                    'avatar_url' => $user->avatar_url,
                ],
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Conta removida com sucesso.',
                'redirect' => '/app/login',
            ]);
        }

        return Redirect::to('/');
    }
}
