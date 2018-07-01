<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pmSubscriptions;

class subscriptionApi extends Controller
{
    /*-- Private Functions --*/

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
        if(sizeof($error_messages) === sizeof($optional) && !empty($optional)) {
            return $error_messages;
        }

        return true;
    }

    private function expandMsisdn($msisdn)
    {
        if(is_array($msisdn) || substr($msisdn, 0, 1) === 'A') return $msisdn;

        $alias = file_get_contents('http://interview.pmcservices.co.uk/alias/lookup?msisdn=' . $msisdn);

        $temp = [$msisdn];

        if(is_string($alias) && strlen($alias) === 13) {
            $temp[] = $alias;
        }

        if(strpos($msisdn, '+44') === 0) {
            $converted_msisdn = str_replace('+44', '0', $msisdn);

            $temp[] = $converted_msisdn;
            $alias = file_get_contents('http://interview.pmcservices.co.uk/alias/lookup?msisdn=' . $converted_msisdn);
        }
        else if(substr($msisdn, 0, 1) === '0') {
            $converted_msisdn = '+44' . substr($msisdn, 1);

            $temp[] = $converted_msisdn;
            $alias = file_get_contents('http://interview.pmcservices.co.uk/alias/lookup?msisdn=' . $converted_msisdn);
        }

        if(is_string($alias) && strlen($alias) === 13 && !in_array($alias, $temp)) {
            $temp[] = $alias;
        }

        return $temp;
    }

    private function saveData($data)
    {
        $search_data = $data;

        if(isset($search_data['msisdn'])) {
            $search_data['msisdn'] = $this->expandMsisdn($search_data['msisdn']);
        }

        $doesExist = $this->searchForData($search_data, 'AND', true);

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

    private function deleteData($data)
    {
        if(isset($data['msisdn'])) {
            $data['msisdn'] = $this->expandMsisdn($data['msisdn']);
        }

        $records = $this->searchForData($data, 'AND', true);

        if(sizeof($records) === 0) {
            return [
                'response' => 'failed',
                'error' => 'no record with these details exists'
           ];
        }

        if(sizeof($records) > 1 && isset($data['product_id'])) {
            return [
                'response' => 'failed',
                'error' => 'more than one record with these details exists'
           ];
        }

        $subscription = new pmSubscriptions();
        $subscription->where('id', $records[0]->id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return [
            'response' => 'subscription deleted'
        ];
    }

    private function searchForData($data, $operator = 'OR', $bypass_error = false)
    {
        $subscription = new pmSubscriptions();

        if(isset($data['msisdn'])) {
            $data['msisdn'] = $this->expandMsisdn($data['msisdn']);
        }

        foreach($data as $field => $value) {
            if($value !== null) {
                if(stripos($subscription->toSql(), 'where') === false || $operator === 'AND') {
                    $subscription = $subscription->where($field, $value);
                }
                else {
                    $subscription = $subscription->orWhere($field, $value);
                }
            }
        }
        if($bypass_error) $subscription = $subscription->whereNull('deleted_at');

        $results = $subscription->get();

        if(sizeof($results) === 0 && !$bypass_error) {
            return [
                'respose' => 'failed',
                'error' => 'No results found'
            ];
        }
        return $results;
    }

    private function coolSearchForData($data)
    {
        $subscription = new pmSubscriptions();

        if(isset($data['msisdn'])) {
            $data['msisdn'] = $this->expandMsisdn($data['msisdn']);
        }

        foreach($data as $field => $value) {
            if($value !== null) {
                if(stripos($subscription->toSql(), 'where') === false) {
                    $subscription = $subscription->where($field, 'like', '%'. $value .'%');
                }
                else {
                    $subscription = $subscription->orWhere($field, 'like', '%'. $value .'%');
                }
            }
        }
        $subscription = $subscription->whereNull('deleted_at');

        $doesExist = $subscription->get();
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

    /*-- Public Functions --*/

    public function addSubscription(Request $request)
    {
        $data = [
            'msisdn' => $request->input('msisdn'),
            'product_id' => $request->input('product_id')
        ];

        $response = $this->needToErrorOut($this->validateUriParams($data));

        if($response !== null) return $response;

        return response()->json($this->saveData($data));
    }

    public function search(Request $request)
    {
        $data = [
            'msisdn' => $request->input('msisdn'),
            'product_id' => $request->input('product_id')
        ];

        $response = $this->needToErrorOut($this->validateUriParams([], $data));

        if($response !== null) return $response;

        return response()->json($this->searchForData($data));
    }

    public function deleteSubscription(Request $request)
    {
        $manditory_data = [
            'msisdn' => $request->input('msisdn'),
        ];

        $optional_data = [
            'product_id' => $request->input('product_id')
        ];

        $response = $this->needToErrorOut($this->validateUriParams($manditory_data, $optional_data));

        $data = $this->arrayMergeNotNull($manditory_data, $optional_data);

        dd($data);

        if($response !== null) return $response;

        return response()->json($this->deleteData($data));
    }
}
