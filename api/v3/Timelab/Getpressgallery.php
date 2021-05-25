<?php

function _civicrm_api3_timelab_Getpressgallery_spec(&$spec) {
  $spec['password']['api.required'] = 1;
}

function civicrm_api3_timelab_Getpressgallery($params) {
  if($params['password'] != 'ItWill4llDisappearInTim3') {
    throw new API_Exception("Invalid password", 1000);
  }
  try {
    // get pictures
    $sql = "
              select
                f.uri as src,
                f.mime_type as mime_type,
                c.id as project_id,
                c.display_name as project_name
              from
                civicrm_value_foto_gallery_32 as fg
              left join
                civicrm_file as f on f.id = fg.foto_52
              left join
                civicrm_contact as c on c.id = fg.entity_id
              where
                c.id = 2402";

    $images = [];
    $dao = CRM_Core_DAO::executeQuery($sql, []);
    while ($dao->fetch()) {
      $images[] = $dao->toArray();
    }

    return civicrm_api3_create_success($images, $params, 'Timelab', 'Getpressgallery');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

