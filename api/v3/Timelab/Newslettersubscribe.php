<?php

function _civicrm_api3_timelab_Newslettersubscribe_spec(&$spec) {
  $spec['email']['api.required'] = 1;
  $spec['first_name']['api.required'] = 1;
  $spec['last_name']['api.required'] = 1;
  $spec['captcha']['api.required'] = 1;
}

function civicrm_api3_timelab_Newslettersubscribe($params) {
  try {//6LfUVw0cAAAAAOUdI7GsRdhFrrEuofMvweVlg5wg
    // check the params
    if (!array_key_exists('email', $params)) {
      throw new Exception('email is required');
      $params['email'] = strtolower(trim($params['email']));
    }

    // check the params
    if (!array_key_exists('first_name', $params)) {
      throw new Exception('first name is required');
      $params['first_name'] = trim($params['first_name']);
    }
    // check the params
    if (!array_key_exists('last_name', $params)) {
      throw new Exception('last name is required');
      $params['last_name'] = trim($params['last_name']);
    }

    $emailValidator = \Zend\Validator\EmailAddress();
    if(!$emailValidator->isValid($params['email'])){
      throw new Exception('email is not valid');
    }

    $contacts = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'email' => $params['email']
    ]);
    if($contacts['is_error']){
      throw new Exception('Error when fetching contact data: '.$contacts['error_message']);
    }
    if($contacts['count'] == 0) {
      $result = civicrm_api3('Contact', 'create', [
        'contact_type' => "Individual",
        'first_name' => $params['first_name'],
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
    else {
      throw new Exception('E-mailadress has already been subscribed');
    }
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

