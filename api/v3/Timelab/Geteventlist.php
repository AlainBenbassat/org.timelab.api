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

    // check if type limitations were specified
    if (array_key_exists('only_types', $params)) {
      $onlyTypes = $params['only_types'];
    }
    else {
      $onlyTypes = [];
    }

    // check if to type exclusions were specified
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

    // check if a limit was specified
    if (array_key_exists('limit', $params)) {
      $limit = intval($params['limit']);

      // check if a page was specified
      if (array_key_exists('page', $params)) {
        $page = intval($params['page']);
      }
      else {
        $page = 1;
      }
    }
    else {
      $limit = null;
      $page = 1;
    }

    // check if to date was specified
    if (array_key_exists('projects', $params)) {
      $projects = $params['projects'];
    }
    else if (array_key_exists('project_types', $params)) {
      $projects = ['type' => $params['project_types']];
    }
    else if(array_key_exists('project', $params)) {
      $projects = [intval($params['project'])];
    }
    else {
      $projects = [];
    }

    if(array_key_exists('orderdirection', $params) && strtoupper($params['orderdirection']) == 'DESC') {
      $orderdirection = 'DESC';
    }
    else{
      $orderdirection = 'ASC';
    }

    $eventHelper = new CRM_Timelab_Event();
    $events = $eventHelper->getEventList($fromDate, $toDate, $limit, $page, $onlyTypes, $exceptTypes, $stromen, $projects, $orderdirection);

    return civicrm_api3_create_success($events, $params, 'Timelab', 'getEventList');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}

