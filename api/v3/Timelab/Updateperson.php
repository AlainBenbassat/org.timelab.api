<?php

require_once 'Getpeople.php';

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

  $sql = "select r.contact_id_b from
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
  if($project = $dao->fetchValue()) {
    if(strpos($params['image'], 'data:') === 0){
      $splitpk = strpos($params['image'], ';', 5);
      $mimetype = substr($params['image'], 5, $splitpk - 5);
      if(strpos(substr($params['image'], $splitpk+1), 'base64,') === 0) {
        $data = base64_decode(substr($params['image'], $splitpk+7));
        $file = 'contact_' . $params['contact_id'];
        switch($mimetype){
          case 'image/jpeg':
            $file .= '.jpg';
            break;
          case 'image/png':
            $file .= '.png';
            break;
          case 'image/svg+xml':
            $file .= '.svg';
            break;
          case 'image/gif':
            $file .= '.gif';
            break;
          case 'image/webp':
            $file .= '.webp';
            break;
        }
        $path = realpath(CRM_Core_Config::singleton()->customFileUploadDir) . DIRECTORY_SEPARATOR . $file;
        global $base_root;
        $base_root_url = str_replace(array('http://', 'https://'), '//', $base_root);
        $drupal_root_path = realpath(DRUPAL_ROOT);
        $url = $base_root_url . substr($path, strlen($drupal_root_path));
        if(file_put_contents($path, $data)) {
          $params['image_URL'] = $url;
        }
        else{
          throw new API_Exception("Failed to save image");
        }
      }
      else{
        $params['image_URL'] = $params['image'];
      }
    }

    $contact = CRM_Contact_BAO_Contact::create($params);
    if (is_a($contact, 'CRM_Core_Error')) {
      throw new API_Exception($contact->_errors[0]['message']);
    }
    else {
      $sql = "UPDATE civicrm_value_public_5 SET bio_15 = %1 WHERE entity_id = %2";
      $sqlParams = [
        1 => [$params['bio'], 'String'],
        2 => [$params['contact_id'], 'Integer']
      ];
      CRM_Core_DAO::executeQuery($sql, $sqlParams);

      $keys = ['email', 'phone', 'website'];
      foreach($keys as $key) {
        if (array_key_exists($key, $params)) {
          foreach ($params[$key] as $e) {
            if (array_key_exists('delete', $e) && $e['delete']) {
              $sql = "DELETE FROM civicrm_$key where id = %1";
              $sqlParams = [
                1 => [$e['id'], 'Integer'],
              ];
              CRM_Core_DAO::executeQuery($sql, $sqlParams);
            }
            else if (array_key_exists('id', $e)) {
              $sqlParams = [
                1 => [$e['id'], 'Integer']
              ];
              if($key == 'website') {
                $sql = "UPDATE civicrm_$key SET url = %2, website_type_id = %3 WHERE id = %1";
                $sqlParams[2] = [$e['url'], 'String'];
                $sqlParams[3] = [$e['website_type_id'], 'Integer'];
              }
              else if($key == 'phone') {
                $sql = "UPDATE civicrm_$key SET phone = %2, phone_numeric = %3 WHERE id = %1";
                $sqlParams[2] = [$e['phone'], 'String'];
                $sqlParams[3] = [preg_replace('/[^0-9]/', '', $e['phone']), 'String'];
              }
              else{
                $sql = "UPDATE civicrm_$key SET $key = %2 WHERE id = %1";
                $sqlParams[2] = [$e[$key], 'String'];
              }
              CRM_Core_DAO::executeQuery($sql, $sqlParams);
            }
            // this is cone by the CRM_Contact_BAO_Contact::create command
            /*else {
              $sqlParams = [
                1 => [$params['contact_id'], 'Integer']
              ];
              if($key == 'website') {
                $sql = "INSERT INTO civicrm_website (contact_id, url, website_type_id) VALUES (%1, %2, %3)";
                $sqlParams[2] = [$e['url'], 'String'];
                $sqlParams[3] = [$e['website_type_id'], 'Integer'];
              }
              else if($key == 'phone') {
                $sql = "INSERT INTO civicrm_phone (contact_id, phone, phone_numeric) VALUES (%1, %2, %3)";
                $sqlParams[2] = [$e['phone'], 'String'];
                $sqlParams[3] = [preg_replace('/[^0-9]/', '', $e['phone']), 'String'];
              }
              else{
                $sql = "INSERT INTO civicrm_$key (contact_id, $key) VALUES (%1, %2)";
                $sqlParams[2] = [$e[$key], 'String'];
              }
              CRM_Core_DAO::executeQuery($sql, $sqlParams);
            }*/
          }
        }
      }

      return civicrm_api3_timelab_Getpeople(['project_api_key' => $params['project_api_key'], 'project' => $project], ' and c.id = '.$params['contact_id']);
    }

  }
  else{
    throw new API_Exception("Contact ID & project API Key combination is invalid");
  }
}

