<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pmSubscriptions extends Model
{
    public function validateUriParams($manditory = [], $optional = [])
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
        if(sizeof($error_messages) === sizeof($optional) && !empty($optional) && empty($manditory)) {
            return $error_messages;
        }

        return true;
    }

    public function getAlias($msisdn)
    {
        $alias = file_get_contents('http://interview.pmcservices.co.uk/alias/lookup?msisdn=' . $msisdn);

        if(substr($alias, 0, 1) === 'A') return $alias;
        return null;
    }

    private function expandMsisdn($msisdn)
    {
        if(is_array($msisdn)) return $msisdn;

        $alias = $this->getAlias($msisdn);

        $temp = [$msisdn];

        if(is_string($alias) && strlen($alias) === 13) {
            $temp[] = $alias;
        }

        if(strpos($msisdn, '+44') === 0) {
            $converted_msisdn = str_replace('+44', '0', $msisdn);

            $temp[] = $converted_msisdn;
            $alias = $this->getAlias($converted_msisdn);
        }
        else if(substr($msisdn, 0, 1) === '0') {
            $converted_msisdn = '+44' . substr($msisdn, 1);

            $temp[] = $converted_msisdn;
            $alias = $this->getAlias($converted_msisdn);
        }
        else if(substr($msisdn, 0, 1) === 'A') {
            $sub = new pmSubscriptions();

            $records = $sub->where('alias', $msisdn)
                ->whereNull('deleted_at')
                ->get();

            $sizeof_records = sizeof($records);
            if($sizeof_records > 0) {
                for($j = 0; $j < $sizeof_records; $j++) {
                    $temp[] = $records[$j]->msisdn;
                }
            }
        }

        if(is_string($alias) && strlen($alias) === 13 && !in_array($alias, $temp)) {
            $temp[] = $alias;
        }

        return $temp;
    }

    public function saveData($data)
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

        if(!isset($data['alias'])) {
            $data['alias'] = $this->getAlias($data['msisdn']);
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

    public function deleteData($data)
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

        $ids = [];

        foreach($records as $record) {
            $ids[] = $record->id;
        }

        $subscription = new pmSubscriptions();
        $subscription->whereIn('id', $ids)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        return [
            'response' => 'subscription deleted'
        ];
    }

    public function searchForData($data, $operator = 'OR', $bypass_error = false)
    {
        $subscription = new pmSubscriptions();

        if(isset($data['msisdn'])) {
            $data['msisdn'] = $this->expandMsisdn($data['msisdn']);
        }

        $not_fields = [
            'start_date' => '>=',
            'end_date' => '<='
        ];

        foreach($data as $field => $value) {
            if(isset($not_fields[$field])) {
                continue;
            }

            if($value !== null) {
                if(stripos($subscription->toSql(), 'where') === false || $operator === 'AND') {
                    $subscription = $subscription->where($field, $value);
                }
                else {
                    $subscription = $subscription->orWhere($field, $value);
                }
            }
        }

        foreach($not_fields as $index => $operator) {
            if(isset($data[$index]) && !empty($data[$index])) {
                if(!is_numeric($data[$index])) {
                    $data[$index] = strtotime(str_replace('/', '-', $data[$index]));
                }

                $subscription = $subscription->whereRaw("UNIX_TIMESTAMP(created_at) $operator $data[$index]");
            }
        }

        if($bypass_error) {
            $subscription = $subscription->whereNull('deleted_at');
        }

        $results = $subscription->get();

        if(sizeof($results) === 0 && !$bypass_error) {
            return [
                'respose' => 'failed',
                'error' => 'No results found'
            ];
        }
        return $results;
    }
}
