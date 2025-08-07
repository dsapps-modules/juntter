<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessPaytimeBilletStatusChange;
use App\Jobs\ProcessPaytimeEstablishmentStatusChange;

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
