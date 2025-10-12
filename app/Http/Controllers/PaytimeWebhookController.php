<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessPaytimeBilletStatusChange;
use App\Jobs\ProcessPaytimeEstablishmentStatusChange;
use App\Jobs\ProcessCreatePaytimeEstablishment;

class PaytimeWebhookController extends Controller
{

    public function createEstablishment(Request $request)
    {
        $data = $request->all();

        if (! $this->isAuthorized($request)) {
            Log::info("createEstablishment request unauthorized with data: ", $data);
            return response()->json(['message' => 'Create establishment unauthorized'], 401);
        }

        Queue::push(new ProcessCreatePaytimeEstablishment($data));
        return response()->json(['message' => 'Create establishment webhook received'], 200);
    }


    public function updateEstablishmentStatus(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: update establishment status'], 401);
        }

        Log::info('Paytime update establishment webhook received', $request->all());
        Queue::push(new ProcessPaytimeEstablishmentStatusChange($request->all()));
        return response()->json(['message' => 'Update establishment status webhook received'], 200);
    }


    public function updateEstablishmentData(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: update establishment data'], 401);
        }

        Log::info(__CLASS__.'#updateEstablishmentData');
        Log::info('Paytime update establishment data webhook received', $request->all());
        // Queue::push(new ProcessUpdatePaytimeEstablishmentData($request->all()));
        return response()->json(['message' => 'Update establishment data webhook received'], 200);
    }


    public function newSubTransaction(Request $request){
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: new transaction'], 401);
        }

        Log::info('Paytime transaction status changed webhook received', $request->all());
        // Queue::push(new ProcessPaytimeEstablishmentStatusChange($request->all()));
        return response()->json(['message' => 'New transaction webhook received'], 200);
    }


    public function updatedSubTransaction(Request $request){
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: update transaction'], 401);
        }

        Log::info('Paytime transaction status changed webhook received', $request->all());
        // Queue::push(new ProcessPaytimeEstablishmentStatusChange($request->all()));
        return response()->json(['message' => 'Update transaction webhook received'], 200);
    }


    public function newBillet(Request $request){
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: new billet'], 401);
        }

        Log::info('Paytime transaction status changed webhook received', $request->all());
        // Queue::push(new ProcessPaytimeEstablishmentStatusChange($request->all()));
        return response()->json(['message' => 'New billet webhook received'], 200);
    }


    public function updateBilletStatus(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: update billet'], 401);
        }

        Log::info('Paytime update billet webhook received', $request->all());
        Queue::push(new ProcessPaytimeBilletStatusChange($request->all()));
        return response()->json(['message' => 'Update billet webhook received'], 200);
    }


    public function newSubSplit(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: new sub split'], 401);
        }

        Log::info('Paytime update billet webhook received', $request->all());
        // Queue::push(new ProcessPaytimeBilletStatusChange($request->all()));
        return response()->json(['message' => 'New sub split webhook received'], 200);
    }


    public function canceledSubSplit(Request $request)
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized: canceled sub split'], 401);
        }

        Log::info('Paytime update billet webhook received', $request->all());
        // Queue::push(new ProcessPaytimeBilletStatusChange($request->all()));
        return response()->json(['message' => 'Canceled sub split webhook received'], 200);
    }


    protected function isAuthorized(Request $request): bool
    {
        $user = env('PAYTIME_WEBHOOK_USER');
        $pass = env('PAYTIME_WEBHOOK_PASS');

        $hasAuth = $request->getUser() && $request->getPassword();
        $pass_key = $request->getPassword();
        $user_key = $request->getUser();

        return $hasAuth &&
            $user_key === $user &&
            $pass_key === $pass;
    }
}