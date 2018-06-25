<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class subscriptionApi extends Controller
{
    public function addSubscription(Request $request)
    {
        $return = [
            'response' => 'working'
        ];

        return response()->json($return);
    }
}
