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
    $sql = "
      SELECT
        id
        , title
        , start_date
        , end_date
      FROM
        civicrm_event
      WHERE
        start_date between %1 and %2
    ";
    $sqlParams = [
      1 => [$fromDate . ' 00:00:00', 'String'],
      2 => [$toDate . ' 23:59:59', 'String'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    return $dao->fetchAll();
  }
}