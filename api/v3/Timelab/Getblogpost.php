<?php

function _civicrm_api3_timelab_Getblogpost_spec(&$spec) {
  $spec['post_id']['api.required'] = 1;
}

function civicrm_api3_timelab_Getblogpost($params) {
  try {
    // check the params
    if (!array_key_exists('post_id', $params) || !is_numeric($params['post_id'])) {
      throw new Exception('post_id is required and must be numeric');
    }

    $sql = "
      select
        e.id,
        e.title,
        e.start_date as `date`,
        e.description,
        concat(%2, 'sites/all/files/civicrm/custom/', f.uri) as image,
        f.id as image_file_id
      from
        civicrm_event e
      left outer join
        civicrm_value_img_9 i on i.entity_id = e.id
      left outer join
        civicrm_file f on i.featured_image_25 = f.id
      where
        e.id = %1
      limit 1";

    $event = [];
    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$params['post_id'], 'Integer'],
      2 => [CRM_Utils_System::baseURL(), 'String']
    ]);
    if($dao->fetch()) {
      $event = $dao->toArray();
    }

    return civicrm_api3_create_success($event, $params, 'Timelab', 'Getblogpost');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

