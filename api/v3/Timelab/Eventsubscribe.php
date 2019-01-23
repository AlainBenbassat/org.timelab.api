<?php

function _civicrm_api3_timelab_Eventsubscribe_spec(&$spec) {
  $spec['event_id']['api.required'] = 1;
  $spec['first_name']['api.required'] = 1;
  $spec['last_name']['api.required'] = 1;
  $spec['email']['api.required'] = 1;
  $spec['tel']['api.required'] = 1;
}

function civicrm_api3_timelab_Eventsubscribe($params) {
  try {
    // check the params
    if (!array_key_exists('event_id', $params) || !is_numeric($params['event_id'])) {
      throw new Exception('event_id is required and must be numeric');
    }

    foreach(['first_name', 'last_name', 'email', 'tel'] as $field) {
      $exceptionMsg = $field.' is required and must not be empty';
      if (!array_key_exists($field, $params)) {
        throw new Exception($exceptionMsg);
      }
      $params[$field] = trim($params[$field]);
      if (strlen($params[$field]) == 0) {
        throw new Exception($exceptionMsg);
      }
    }

    $params['email']= strtolower($params['email']);
    /* TODO: test this
    $emailValidator = \Zend\Validator\EmailAddress();
    if(!$emailValidator->isValid($params['email'])){
      throw new Exception('email is not valid');
    }
    */

    $contacts = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'email' => $params['email']
    ]);
    if($contacts['is_error']){
      throw new Exception('Error when fetching contact data: '.$contacts['error_message']);
    }
    $contact = null;
    foreach($contacts['values'] as $possibleContact){
      if(!$possibleContact['first_name'] || strtolower($possibleContact['first_name']) == strtolower($params['first_name'])){
        if(!$possibleContact['last_name'] || strtolower($possibleContact['last_name']) == strtolower($params['last_name'])){
          $ok = !$possibleContact['tel'];
          if(!$ok) {
            $ctel = preg_replace('/[^0-9]/', '', $possibleContact['tel']);
            $ptel = preg_replace('/[^0-9]/', '', $params['tel']);
            $cmplen = max(strlen($ctel), strlen($ptel)) - 4;
            $ok = (substr($ctel, -$cmplen) == substr($ptel, -$cmplen));
          }
          if($ok){
            $contact = $possibleContact;
            break;
          }
        }
      }
    }
    if(!$contact){
      $result = civicrm_api3('Contact', 'create', [
        'contact_type' => "Individual",
        'first_name' => $possibleContact['first_name'],
        'last_name' => $possibleContact['last_name'],
      ]);
      if($result['is_error']){
        throw new Exception('Error when creating contact: '.$result['error_message']);
      }
      else{
        $contact = reset($result['values']);
        $result = civicrm_api3('Email', 'create', [
          'contact_id' => $contact['id'],
          'email' => $params['email'],
        ]);
        if($result['is_error']){
          throw new Exception('Error when creating email: '.$result['error_message']);
        }

        $result = civicrm_api3('Phone', 'create', [
          'contact_id' => $contact['id'],
          'tel' => $params['tel'],
        ]);
        if($result['is_error']){
          throw new Exception('Error when creating telephone number: '.$result['error_message']);
        }
      }
    }

    return civicrm_api3('Participant', 'create', [
      'contact_id' => $contact['id'],
      'event_id' => $params['event_id'],
      'custom_31' => $params['invoice_needed'] ?? null,
      'custom_32' => $params['invoice_company'] ?? null,
      'custom_36' => $params['invoice_vat'] ?? null,
      'custom_33' => $params['invoice_street_address'] ?? null,
      'custom_35' => $params['invoice_postalcode'] ?? null,
      'custom_34' => $params['invoice_city'] ?? null
    ]);
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

