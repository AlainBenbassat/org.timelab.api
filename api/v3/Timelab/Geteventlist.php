<?php

function _civicrm_api3_timelab_Geteventlist_spec(&$spec) {
}

function civicrm_api3_timelab_Geteventlist($params) {
  try {
    // check if from date was specified
    if (array_key_exists('from_date', $params)) {
      $fromDate = $params['from_date'];
    }
    else {
      $fromDate = date('Y-m-d');
    }

    // check if to date was specified
    if (array_key_exists('to_date', $params)) {
      $toDate = $params['to_date'];
    }
    else {
      $toDate = '2999-12-31';
    }

    // check if to date was specified
    if (array_key_exists('except_types', $params)) {
      $exceptTypes = $params['except_types'];
    }
    else {
      $exceptTypes = [];
    }

    // check if to date was specified
    if (array_key_exists('stromen', $params)) {
      $stromen = $params['stromen'];
    }
    else {
        $stromen = [];
    }

    // check if to date was specified
    if (array_key_exists('limit', $params)) {
      $limit = intval($params['limit']);
    }
    else {
      $limit = null;
    }

    $eventHelper = new CRM_Timelab_Event();
    $events = $eventHelper->getEventList($fromDate, $toDate, $limit, $exceptTypes, $stromen);

    return civicrm_api3_create_success($events, $params, 'Timelab', 'getEventList');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

