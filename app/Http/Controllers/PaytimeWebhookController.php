<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessPaytimeBilletStatusChange;
use App\Jobs\ProcessPaytimeEstablishmentStatusChange;
use App\Jobs\ProcessPaytimeEstablishmentCreation;

class PaytimeWebhookController extends Controller
{
    public function updateEstablishmentStatus(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: update establishment'], 401);
        }

        Log::info('Paytime update establishment webhook received', $request->all());
        Queue::push(new ProcessPaytimeEstablishmentStatusChange($request->all()));
        return response()->json(['message' => 'Update establishment webhook received'], 200);
    }

    public function updateBilletStatus(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: update billet'], 401);
        }

        Log::info('Paytime update billet webhook received', $request->all());
        Queue::push(new ProcessPaytimeBilletStatusChange($request->all()));
        return response()->json(['message' => 'Refund webhook received'], 200);
    }

    public function createEstablishment(Request $request)
    {
       
        if (!$this->isAuthorized($request)) {
            Log::info('Unauthorized: create establishment', $request->all());
            return response()->json(['message' => 'Unauthorized: create establishment'], 401);
        }

        Log::info('Paytime create establishment webhook received', $request->all());
        Queue::push(new ProcessPaytimeEstablishmentCreation($request->all()));
        return response()->json(['message' => 'Create establishment webhook received'], 200);
    }

    public function testWebhook(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            Log::info(' TESTE WEBHOOK - UNAUTHORIZED!', [
                'timestamp' => now(),
                'headers' => $request->headers->all(),
                'payload_completo' => $request->all(),
                'auth_user' => $request->getUser(),
                'auth_pass' => $request->getPassword(),
                'expected_user' => env('PAYTIME_WEBHOOK_USER'),
                'expected_pass' => env('PAYTIME_WEBHOOK_PASS'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            return response()->json(['message' => 'Unauthorized: test webhook'], 200);
        }

        Log::info(' TESTE WEBHOOK - AUTORIZADO!', [
            'timestamp' => now(),
            'headers' => $request->headers->all(),
            'payload_completo' => $request->all(),
            'event' => $request->input('event', 'SEM_EVENTO'),
            'data' => $request->input('data', 'SEM_DADOS'),
            'auth_user' => $request->getUser(),
            'auth_pass' => $request->getPassword(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return response()->json(['message' => 'Test webhook received with auth'], 200);
    }

    protected function isAuthorized(Request $request): bool
    {
        $user = env('PAYTIME_WEBHOOK_USER');
        $pass = env('PAYTIME_WEBHOOK_PASS');

        $hasAuth = $request->getUser() && $request->getPassword();

        return $hasAuth &&
            $request->getUser() === $user &&
            $request->getPassword() === $pass;
    }
}