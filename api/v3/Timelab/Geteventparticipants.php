<?php

function _civicrm_api3_timelab_Geteventparticipants_spec(&$spec) {
  $spec['event_id']['api.required'] = 1;
}

function civicrm_api3_timelab_Geteventparticipants($params) {
  try {
    // check the params
    if (!array_key_exists('event_id', $params) || !is_numeric($params['event_id'])) {
      throw new Exception('event_id is required and must be numeric');
    }

    $eventHelper = new CRM_Timelab_Event();
    $eventParticipants = $eventHelper->getEventParticipants($params['event_id']);

    return civicrm_api3_create_success($eventParticipants, $params, 'Timelab', 'getEventParticipants');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

