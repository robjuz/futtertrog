<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use NotificationChannels\WebPush\PushSubscription;

class PushSubscriptionController extends Controller
{
    /**
     * Update user's subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate(['endpoint' => 'required']);

        $request->user()
            ->updatePushSubscription(
                $request->endpoint,
                $request->key,
                $request->token
            );

        return response()->json(null, Response::HTTP_OK);
    }

    /**
     * Delete the specified subscription.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required']);

        PushSubscription::findByEndpoint($request->endpoint)->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
