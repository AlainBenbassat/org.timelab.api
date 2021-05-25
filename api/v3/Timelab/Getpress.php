<?php

function _civicrm_api3_timelab_Getpress_spec(&$spec) {
}

function civicrm_api3_timelab_Getpress($params) {
  try {
    $sql = "
      select
        e.id,
        e.title,
        e.start_date as `date`,
        concat(%1, 'sites/all/files/civicrm/custom/', f.uri) as image,
        f.id as image_file_id,
        p.medium_73 as medium
      from
        civicrm_event e
      left outer join
        civicrm_value_img_9 i on i.entity_id = e.id
      left outer join
        civicrm_file f on i.featured_image_25 = f.id
      left outer join
        civicrm_value_press_fields_40 p on p.entity_id = e.id
      where
        e.is_active = 1 AND
        e.event_type_id = 58 AND
        e.is_public = 1
      order by
        e.start_date DESC";

    $events = CRM_Core_DAO::executeQuery($sql, [1 => [CRM_Utils_System::baseURL(), 'String']]);
    $events = $events->fetchAll();

    return civicrm_api3_create_success($events, $params, 'Timelab', 'Getpress');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

