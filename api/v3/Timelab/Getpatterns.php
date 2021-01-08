<?php

function _civicrm_api3_timelab_Getpatterns_spec(&$spec) {
}

function civicrm_api3_timelab_Getpatterns($params) {
  try {
    $sql = "select
    c.id,
    c.display_name,
    c.image_URL as image,
    o.ordering_value_67 as ordering_value
  from
    civicrm_contact as c
  left join
    civicrm_value_ordering_37 as o on c.id = o.entity_id
  where
    c.is_deleted = 0
    and c.contact_type = 'Organization'
    and c.contact_sub_type LIKE '_patterns_'
  group by
    c.id
  order by
    IF(o.ordering_value_67 IS NULL OR o.ordering_value_67 = '', c.sort_name, o.ordering_value_67)";

    $events = CRM_Core_DAO::executeQuery($sql, [1 => [CRM_Utils_System::baseURL(), 'String']]);
    $events = $events->fetchAll();

    return civicrm_api3_create_success($events, $params, 'Timelab', 'GetPatterns');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

