<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PaytimeEstablishment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): RedirectResponse
    {
        return redirect('/app/forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = $this->resolveResetPasswordUser((string) $request->string('email'));

        $status = $user === null
            ? Password::INVALID_USER
            : Password::sendResetLink([
                'email' => $user->email,
            ]);

        if ($request->expectsJson()) {
            return $status === Password::RESET_LINK_SENT
                ? response()->json([
                    'message' => __('passwords.sent'),
                ])
                : response()->json([
                    'message' => __($status),
                ], 422);
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    private function resolveResetPasswordUser(string $email): ?User
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user instanceof User) {
            return $user;
        }

        $establishment = PaytimeEstablishment::query()
            ->where('email', $email)
            ->first();

        if (! $establishment instanceof PaytimeEstablishment) {
            return null;
        }

        return DB::transaction(function () use ($establishment): User {
            $user = User::create([
                'name' => $this->resolveUserName($establishment),
                'trade_name' => filled($establishment->fantasy_name) ? $establishment->fantasy_name : null,
                'email' => $establishment->email,
                'password' => Str::random(32),
                'nivel_acesso' => 'vendedor',
                'email_verified_at' => now(),
            ]);

            $user->vendedor()->create([
                'estabelecimento_id' => $establishment->id,
                'sub_nivel' => 'admin_loja',
                'telefone' => $establishment->phone_number,
                'endereco' => json_encode($establishment->address_json ?? []),
                'status' => 'ativo',
                'must_change_password' => true,
            ]);

            return $user;
        });
    }

    private function resolveUserName(PaytimeEstablishment $establishment): string
    {
        $name = trim(sprintf('%s %s', (string) $establishment->first_name, (string) $establishment->last_name));

        if ($name !== '') {
            return $name;
        }

        return filled($establishment->fantasy_name)
            ? (string) $establishment->fantasy_name
            : 'Sem Nome';
    }
}
