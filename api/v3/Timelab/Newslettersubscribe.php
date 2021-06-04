<?php

function _civicrm_api3_timelab_Newslettersubscribe_spec(&$spec) {
  $spec['email']['api.required'] = 1;
}

function civicrm_api3_timelab_Newslettersubscribe($params) {
  try {
    // check the params
    if (!array_key_exists('email', $params)) {
      throw new Exception('email is required');
      $params['email'] = strtolower(trim($params['email']));
    }

    /* TODO: test this
    $emailValidator = \Zend\Validator\EmailAddress();
    if(!$emailValidator->isValid($params['email'])){
      throw new Exception('email is not valid');
    }
    */

    $params['first_name'] = trim($params['first_name'] ?? '');
    $params['last_name'] = trim($params['last_name'] ?? '');

    $contacts = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'email' => $params['email']
    ]);
    if($contacts['is_error']){
      throw new Exception('Error when fetching contact data: '.$contacts['error_message']);
    }
    $contact = null;
    foreach($contacts['values'] as $possibleContact){
      $possibleContact['first_name'] = trim($possibleContact['first_name'] ?? '');
      $possibleContact['last_name'] = trim($possibleContact['last_name'] ?? '');
      if(strtolower($possibleContact['first_name']) == strtolower($params['first_name'])
      || strtolower($possibleContact['first_name']) == $params['email']){
        if(strtolower($possibleContact['last_name']) == strtolower($params['last_name'])){
          $contact = $possibleContact;
          break;
        }
      }
    }
    if(!$contact) {
      if($params['first_name'] == '') {
        $params['first_name'] = $params['email'];
      }
      $result = civicrm_api3('Contact', 'create', [
        'contact_type' => "Individual",
        'first_name' => $params['email'] ,
        'last_name' => $params['last_name'],
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

