<?php

function _civicrm_api3_timelab_Getevent_spec(&$spec) {
  $spec['event_id']['api.required'] = 1;
}

function civicrm_api3_timelab_Getevent($params) {
  try {
    // check the params
    if (!array_key_exists('event_id', $params) || !is_numeric($params['event_id'])) {
      throw new Exception('event_id is required and must be numeric');
    }

    $eventHelper = new CRM_Timelab_Event();
    $eventDetails = $eventHelper->getEventDetails($params['event_id'], isset($params['fetch_participants']) && $params['fetch_participants']);

    return civicrm_api3_create_success($eventDetails, $params, 'Timelab', 'getEvent');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

