<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pmSubscriptions;

class subscriptionApi extends Controller
{
    private function validateUriParams($manditory = [], $optional = [])
    {
        $error_messages = [];

        if(sizeof($manditory) === 0 && sizeof($optional) === 0) {
            $error_messages[] = "no attribute data passed through in request";
            return $error_messages;
        }

        foreach($manditory as $index => $value) {
            if($value === null) {
              $error_messages[] = "manditory attribute, $index, not set";
            }
        }
        if(sizeof($error_messages) !== 0) {
            return $error_messages;
        }

        foreach($optional as $index => $value) {
            if($value === null) {
              $error_messages[] = "optional attribute, $index, not set";
            }
        }
        if(sizeof($error_messages) === sizeof($optional)) {
            return $error_messages;
        }

        return true;
    }

    private function saveData($data)
    {
        $subscription = new pmSubscriptions();

        foreach($data as $field => $value) {
            $subscription = $subscription->where($field, $value);
        }

        $doesExist = $subscription->get();

        if(sizeof($doesExist) > 0) {
            return [
                'response' => 'failed',
                'error' => 'a record with these exact details already exists'
           ];
        }

        $subscription = new pmSubscriptions();

        foreach($data as $field => $value) {
            $subscription->{$field} = $value;
        }
        $subscription->created_at = time();
        $subscription->save();

        return [
            'response' => 'subscription added'
       ];
    }

    public function addSubscription(Request $request)
    {
        $data = [
            'msisdn' => $request->input('msisdn'),
            'product_id' => $request->input('product_id')
        ];

        $response = $this->validateUriParams($data);

        if($response !== true) {
            $return = [
              'response' => 'failed',
              'error_messages' => $response
            ];

            return response()->json($return);
        }

        $return = $this->saveData($data);

        return response()->json($return);
    }
}
