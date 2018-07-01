<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pmSubscriptions;

class subscriptionApi extends Controller
{
    public function addSubscription(Request $request)
    {
        $data = [
            'msisdn' => $request->input('msisdn'),
            'product_id' => $request->input('product_id')
        ];

        $sub = new pmSubscriptions();
        $response = $this->needToErrorOut($sub->validateUriParams($data));

        if($response !== null) return $response;

        return response()->json($sub->saveData($data));
    }

    public function search(Request $request)
    {
        $data = [
            'msisdn' => $request->input('msisdn'),
            'product_id' => $request->input('product_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date')
        ];

        $sub = new pmSubscriptions();
        $response = $this->needToErrorOut($sub->validateUriParams([], $data));

        if($response !== null) return $response;

        return response()->json($sub->searchForData($data));
    }

    public function deleteSubscription(Request $request)
    {
        $manditory_data = [
            'msisdn' => $request->input('msisdn'),
        ];

        $optional_data = [
            'product_id' => $request->input('product_id')
        ];

        $sub = new pmSubscriptions();
        $response = $this->needToErrorOut($sub->validateUriParams($manditory_data, $optional_data));

        $data = $this->arrayMergeNotNull($manditory_data, $optional_data);

        dd($data);

        if($response !== null) return $response;

        return response()->json($sub->deleteData($data));
    }

    private function needToErrorOut($response)
    {
        if($response !== true) {
            $return = [
              'response' => 'failed',
              'error_messages' => $response
            ];

            return response()->json($return);
        }

        return null;
    }

    private function arrayMergeNotNull()
    {
        $temp = [];

        foreach(func_get_args() as $param) {
            foreach($param as $index => $value) {
                if($value !== null) {
                    $temp[$index] = $value;
                }
            }
        }

        return $temp;
    }
}
