<?php

namespace App\Http\Services;

use Google\Client as GoogleClient;

class PushNotificationService
{
    public function fcmAccessToken()
    {
        // Load the Firebase JSON credentials
        $pathToServiceAccount = config_path('FirebasePrivateKey.json');
        // Create a new Google client
        $client = new GoogleClient;
        $client->setAuthConfig($pathToServiceAccount);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        // Get the access token
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        return $accessToken;
    }

    public function sendPushNotification($data, $device_token, $type)
    {

        $accessToken = $this->fcmAccessToken();
        // FCM API URL
        $url = 'https://fcm.googleapis.com/v1/projects/'.env('FCM_PROJECT_ID').'/messages:send';  // Replace YOUR_PROJECT_ID

        $final = [
            'message' => [
                'token' => $device_token,
                'notification' => [
                    'title' => $data->title,
                    'body' => $data->description,
                    'image' => $data->image,

                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'android' => [
                    'priority' => 'high',
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($final));
        $result = curl_exec($ch);
        if ($result === false) {
            throw new \Exception('Curl failed: '.curl_error($ch));
        }
        curl_close($ch);

        return $result;
    }

    public function sendStatusPushNotification($parcel, $device_token, $msg, $type)
    {

        if (isset($type) && $type == 'create') {
            $title = 'Your parcel created sucessfully. Tracking ID : #'.$parcel->tracking_id;
        } else {
            $title = 'Your parcel #'.$parcel->tracking_id.' status updated '.trans('parcelStatus.'.$parcel->status);
        }
        $accessToken = $this->fcmAccessToken();

        // FCM API URL
        $url = 'https://fcm.googleapis.com/v1/projects/'.env('FCM_PROJECT_ID').'/messages:send';  // Replace YOUR_PROJECT_ID

        $final = [
            'message' => [
                'token' => $device_token,
                'notification' => [
                    'title' => $title,
                    'body' => $msg,
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
                'android' => [
                    'priority' => 'high',
                ],
            ],
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer '.$accessToken,
            'Content-Type: application/json',
        ]);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($final));
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;

    }

    public function fcmSubscribe($request)
    {

        $deviceToken = $request->device_token;
        $topic = env('FCM_TOPIC').'_'.str_replace(['@', '.', '+'], ['_', '_', ''], $request->topic);

        $headers = [
            'Authorization: key='.env('FCM_SECRET_KEY'),
            'Content-Type: application/json',
        ];
        $this->fcmGlobalSubscribe($request);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, []);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            return response()->json([
                'status' => 200,
                'message' => 'Subscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 401,
                'message' => $exception,
            ], 401);
        }
    }

    public function fcmGlobalSubscribe($request)
    {
        $deviceToken = $request->device_token;
        $topic = env('FCM_TOPIC');

        $headers = [
            'Authorization: key='.env('FCM_SECRET_KEY'),
            'Content-Type: application/json',
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/iid/v1/$deviceToken/rel/topics/$topic");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, []);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            return response()->json([
                'status' => 200,
                'message' => 'Global Subscription',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 401,
                'message' => $exception,
            ], 401);
        }
    }

    public function fcmUnsubscribe($request)
    {
        $request->validate([
            'device_token' => 'required',
            'topic' => 'nullable',
        ]);

        $deviceToken = $request->token;

        $headers = [
            'Authorization: key='.env('FCM_SECRET_KEY'),
            'Content-Type: application/json',
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://iid.googleapis.com/v1/web/iid/$deviceToken");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            return response()->json([
                'status' => 200,
                'message' => 'Unsubscribed',
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 401,
                'message' => $exception,
            ], 401);
        }
    }

    public function sendWebNotification($data, $notification, $type, $FcmToken)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        if (! blank($FcmToken)) {

            $serverKey = env('FCM_SECRET_KEY');
            if ($notification) {
                $pushData = [
                    'registration_ids' => $FcmToken,
                    'notification' => [
                        'title' => 'New Parcel #'.$data->id,
                        'body' => 'A new parcel has been placed '.$data->merchant->title.' The parcel amount is '.$data->cash_collection,
                        'sound' => 'default', // Optional
                        'icon' => public_path('images/fav.png'),
                    ],
                ];
            } else {
                $pushData = [
                    'registration_ids' => $FcmToken,
                    'notification' => [
                        'title' => $data->title,
                        'body' => $data->description,
                        'sound' => 'default', // Optional
                        'icon' => $data->image,
                    ],
                ];
            }

            $encodedData = json_encode($pushData);

            $headers = [
                'Authorization:key='.$serverKey,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
            // Execute post
            $result = curl_exec($ch);
            // Close connection
            curl_close($ch);

            // FCM response
            return true;
        } else {
            return true;
        }
    }
}
