<?php

function _civicrm_api3_timelab_Updateperson_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
  $spec['project_api_key']['api.required'] = 1;
}

function civicrm_api3_timelab_Updateperson($params) {
  if(empty($params['contact_id'])){
    throw new API_Exception("Person can only be edited when ID is provided");
  }
  if(empty($params['project_api_key'])){
    throw new API_Exception("Person can only be edited when a project API Key is provided");
  }

  unset($params['check_permissions']);

  $sql = "select r.contact_id_a from
            civicrm_relationship as r
          left join
            civicrm_contact as b
            on b.id = r.contact_id_b
          where
            b.api_key = %1
            and r.contact_id_a = %2";
  $sqlParams = [
    1 => [$params['project_api_key'], 'String'],
    2 => [$params['contact_id'], 'Integer'],
  ];
  $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
  if($dao->fetchValue()) {
    $contact = CRM_Contact_BAO_Contact::create($params);
    if (is_a($contact, 'CRM_Core_Error')) {
      throw new API_Exception($contact->_errors[0]['message']);
    }
    else {
      $values = [];
      _civicrm_api3_object_to_array_unique_fields($contact, $values[$contact->id]);
      return civicrm_api3_create_success($values, $params, 'Contact', 'create');
    }

  }
  else{
    throw new API_Exception("Contact ID & project API Key combination is invalid");
  }
}

