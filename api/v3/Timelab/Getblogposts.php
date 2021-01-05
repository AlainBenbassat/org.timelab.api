<?php

function _civicrm_api3_timelab_Getblogposts_spec(&$spec) {
}

function civicrm_api3_timelab_Getblogposts($params) {
  try {
    $sql = "
      select distinct
        e.id,
        e.title,
        e.start_date as date,
        concat(%3, 'sites/all/files/civicrm/custom/', f.uri) as image,
        f.id as image_file_id
      from
        civicrm_event e
      inner join
        civicrm_file f on i.featured_image_25 = f.id
      where
        e.is_active = 1 AND
        e.event_type_id = 23
      order by
        e.start_date DESC";

    $events = CRM_Core_DAO::executeQuery($sql)->fetchAll();

    return civicrm_api3_create_success($events, $params, 'Timelab', 'Getblogposts');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

