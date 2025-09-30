<?php

use App\Models\DeliveryMan;
use App\Models\Shipment;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('shipment.{id}', function ($user, $id) {
    $shipment = Shipment::find($id);

    return $user && $user->can('view', $shipment);
});

Broadcast::channel('driver.{id}', function ($user, $id) {
    return $user && $user->id === (int) $id && DeliveryMan::where('user_id', $user->id)->exists();
});

Broadcast::channel('track.{token}', function ($token) {
    // Public channel, signed token validation can be done in event or elsewhere
    return true;
});
