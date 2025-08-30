<?php

namespace App\Http\Controllers\Api\V10;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\PushNotificationService;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends Controller
{
    protected $pushNotificationService;
    public function __construct(PushNotificationService $pushNotificationService )
    {
        $this->pushNotificationService = $pushNotificationService;
    }

    public function fcmSubscribe(Request $request)
    {
        $validation = Validator::make($request->all(),  [
            'device_token' => 'required',
            'topic'        => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validation->errors(),
            ], 422);
        }

        try {         
            $user = User::find(auth()->id());
            $user->device_token = $request->device_token;
            $user->save();
            return $this->pushNotificationService->fcmSubscribe($request);
        } catch (\Throwable $th) { 
            return response()->json([
                'status'  => 422,
                'message' => __('hub.error_msg'),
            ], 422); 
        }
 
    }

    public function fcmUnsubscribe(Request $request)
    {
        return $this->pushNotificationService->fcmUnsubscribe($request);
    }

}
