<?php

class CRM_Timelab_Event {
  public function getEventDetails($id) {
    $params = [
      'sequential' => 1,
      'id' => $id,
    ];

    $e = civicrm_api3('Event', 'getsingle', $params);
    return $e;
  }

  public function getEventList($fromDate, $toDate) {
    $params = [
      'sequential' => 1,
      'start_date' => ['BETWEEN' => [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']],
      'options' => ['sort' => 'start_date ASC'],
      'return' => ['id', 'title', 'start_date', 'end_date', 'custom_25']
    ];

    $e = civicrm_api3('Event', 'get', $params);
    return $e;
  }
}