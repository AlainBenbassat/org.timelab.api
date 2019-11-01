<?php

require_once 'Getpeople.php';

function _civicrm_api3_timelab_Updateevent_spec(&$spec) {
  //$spec['event_id']['api.required'] = 1;
  $spec['project_api_key']['api.required'] = 1;
}

function getFileUploadDirUrl() {
  $path = realpath(CRM_Core_Config::singleton()->customFileUploadDir);
  global $base_root;
  $base_root_url = str_replace(array('http://', 'https://'), '//', $base_root);
  $drupal_root_path = realpath(DRUPAL_ROOT);
  return $base_root_url . substr($path, strlen($drupal_root_path));
}

function handle_api_image($data, $filebasename, $return = 'customfield'){
  if(strpos($data, 'data:') === 0){
    $splitpk = strpos($data, ';', 5);
    $mimetype = substr($data, 5, $splitpk - 5);
    if(strpos(substr($data, $splitpk+1), 'base64,') === 0) {
      $data = base64_decode(substr($data, $splitpk+7));
      $file = $filebasename;
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
      $url = getFileUploadDirUrl() . '/' . $file;
      if(file_put_contents($path, $data)) {
        switch($return) {
          case 'basename':
            return $file;
          case 'path':
            return $path;
          case 'customfield':
            return [
              'name' => $path,
              'type'=> $mimetype
            ];
          default:
            return $url;
        }
      }
      else{
        throw new API_Exception("Failed to save image");
      }
    }
    else{
      throw new API_Exception("Cannot decode datastream");
    }
  }
  else{
    switch($return) {
      case 'customfield':
        $file = $data;
        if(strpos($data, getFileUploadDirUrl()) === 0){
          $file = realpath(CRM_Core_Config::singleton()->customFileUploadDir) . DIRECTORY_SEPARATOR . substr($file, strlen(getFileUploadDirUrl()));
          $parts = explode('.', basename($file));
          if(count($file) > 1){
            $ext = strtolower($file[-1]);
            switch($ext){
              case 'png':
                $mimetype = 'image/png';
                break;
              case 'jpg':
              case 'jpeg':
                $mimetype = 'image/jpeg';
                break;
              case 'svg':
                $mimetype = 'image/svg+xml';
                break;
              case 'gif':
                $mimetype = 'image/gif';
                break;
              case 'webp':
                $mimetype = 'image/webp';
                break;
            }
          }
        }
        return [
          'name' => $file,
          'type'=> $mimetype
        ];
      default:
        return $data;
    }
  }
}

function civicrm_api3_timelab_Updateevent($params) {
  /*if(empty($params['event_id'])){
    throw new API_Exception("Event can only be edited when ID is provided");
  }*/
  if(empty($params['project_api_key'])){
    throw new API_Exception("Person can only be edited when a project API Key is provided");
  }

  unset($params['check_permissions']);

  $sql = "select c.id from
            civicrm_event as e
          left join
            civicrm_value_img_9 i
            on i.entity_id = e.id
          left join
            civicrm_contact as c
            on c.id = i.project_45
          where
            c.api_key = %1";
  $sqlParams = [
    1 => [$params['project_api_key'], 'String'],
  ];
  if(array_key_exists($params,'event_id')){
    $sql .= " and e.id = %2";
    $sqlParams[2] = [$params['event_id'], 'Integer'];
  }
  $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
  if($project = $dao->fetchValue()) {
    if($params['image'] != $params['original_image']){
      $params['custom_25'] = handle_api_image($params['image'], 'event_'.$params['event_id'], 'customfield');
    }
    $params['custom_43'] = $params['stroom'];
    $params['custom_45'] = $project;
    $params['is_public'] = 1;
    $params['is_monetary'] = $params['is_monetary'] ? 1 : 0;
    $params['is_online_registration'] = $params['is_online_registration'] ? 1 : 0;
    if(array_key_exists($params,'event_id')) {
      $params['id'] = $params['event_id'];
    }

    $event = civicrm_api3('Event', 'create', $params);

    if (is_a($event, 'CRM_Core_Error')) {
      throw new API_Exception($event->_errors[0]['message']);
    }
    else{
      $eventHelper = new CRM_Timelab_Event();
      $event = $eventHelper->getEventList($params['start_date'], $params['end_date'], 1, [], [], [$project], array_keys($event['values'])[0]);

      return civicrm_api3_create_success($event, $params, 'Timelab', 'getEventList');
    }

  }
  else{
    throw new API_Exception("Event ID & project API Key combination is invalid");
  }
}

