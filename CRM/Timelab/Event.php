<?php


class CRM_Timelab_Event {
  private $timelabURL;

  public function __construct() {
    $this->timelabURL = CRM_Utils_System::baseURL();
  }

  public function getEventDetails($id, $fetchParticipants = false) {
    $sql = "
      select
        e.id
        , e.title
        , e.start_date
        , e.end_date
        , e.is_monetary
        , e.summary
        , e.description
        , e.event_type_id
        , e.registration_link_text
        , ov.label as event_type
        , concat(%2, 'sites/all/files/civicrm/custom/', f.uri) as image
        , e.is_monetary
        , e.is_online_registration
        , i.stroom_43 as stroom
        , i.project_45 as project
        , sv.label as stream_label
      from
        civicrm_event e
      inner join
        civicrm_option_value ov on ov.value = e.event_type_id
      inner join
        civicrm_option_group og on ov.option_group_id = og.id and og.name = 'event_type'
      left outer join
        civicrm_value_img_9 i on i.entity_id = e.id
      left outer join
        civicrm_file f on i.featured_image_25 = f.id
      left join
        civicrm_option_value as sv
        on sv.option_group_id = 132 and sv.value = i.stroom_43
      where
        e.id = %1
    ";
    $sqlParams = [
      1 => [$id, 'Integer'],
      2 => [$this->timelabURL, 'String'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      $event = $dao->toArray();
      $event['event_type'] = [
        'id' => $dao->event_type_id,
        'name' => $dao->event_type,
      ];
    }

    // add the price
    if ($dao->is_monetary) {
      $event['event_prices'] = [];

      $sql = "
        select
          pf.label price_field_label,
          pfv.label price_value_label,
          pfv.amount
        from
          civicrm_event e
        left outer JOIN
          civicrm_price_set_entity pe on pe.entity_id = e.id and pe.entity_table = 'civicrm_event'
        left outer JOIN
          civicrm_price_set ps on ps.id = pe.price_set_id
        left outer JOIN
          civicrm_price_field pf on pf.price_set_id = ps.id
        left outer JOIN
          civicrm_price_field_value pfv on pfv.price_field_id = pf.id
        where
          e.id = %1
        and
          pf.is_active = 1
        order by
          pf.weight
      ";
      $sqlParams = [
        1 => [$id, 'Integer'],
      ];

      $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
      while ($dao->fetch()) {
        if (!array_key_exists($dao->price_field_label, $event['event_prices'])) {
          $event['event_prices'][$dao->price_field_label] = [];
        }

        $event['event_prices'][$dao->price_field_label][$dao->price_value_label] = $dao->amount;
      }
    }

    // add registration profiles
    if ($event['is_online_registration']) {
      $sql = "
        SELECT g.* FROM civicrm_uf_group AS g
        LEFT JOIN civicrm_uf_join AS j ON j.uf_group_id = g.id
        WHERE j.entity_id = {$event['id']}
        AND j.entity_table = 'civicrm_event'
        AND g.is_active = 1
        ORDER BY j.weight
      ";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $event['ufgroups'] = $dao->fetchAll();
      foreach($event['ufgroups'] as &$g){
        $g['fields'] = [];
        $sql = "
          SELECT * FROM civicrm_uf_field
          WHERE uf_group_id = {$g['id']}
          AND is_active = 1
          ORDER BY weight
        ";
        $dao = CRM_Core_DAO::executeQuery($sql);
        $g['fields'] = $dao->fetchAll();
      }
    }

    if($fetchParticipants) {
      $event['participants'] = $this->getEventParticipants($id);
    }

    return $event;
  }

  public function getEventParticipants($id) {
    $sql = "
      select
        c.id,
        c.display_name,
        c.image_URL as image,
        cov.label as role,
        gdpr.may_be_shown_on_site__54 as may_be_shown_on_site
      from
        civicrm_event e
      inner join
        civicrm_participant p on e.id = p.event_id
      inner join
        civicrm_contact c on c.id = p.contact_id
      inner join
        civicrm_participant_status_type st on st.id = p.status_id
      inner join
        civicrm_option_value cov on cov.value = p.role_id
      left join
        civicrm_value_gdpr_34 as gdpr on c.id = gdpr.entity_id
      where
        e.id = %1
      and
        c.is_deleted = 0
      and
        e.is_active = 1
      and
        e.is_public = 1
      and
        st.is_counted = 1
      and
        cov.option_group_id=13
      and
        (gdpr.may_be_shown_on_site__54 IS NULL or gdpr.may_be_shown_on_site__54 != 'no')
      order by
        role,
        c.sort_name
    ";
    $sqlParams = [
      1 => [$id, 'Integer'],
    ];

    $participants = [];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    while ($dao->fetch()) {
      $participants[] = $dao->toArray();
    }

    return $participants;
  }

  public function getEventList($fromDate, $toDate, $limit = null, $page = 1, $onlyTypes = [], $exceptTypes = [], $stromen = [], $projects = [], $orderdirection = 'ASC') {
    $sqlParams = [
      1 => [$fromDate . (strpos($fromDate,':') === false ? ' 00:00:00' : ''), 'String'],
      2 => [$toDate . (strpos($toDate,':') === false ? ' 23:59:59' : ''), 'String'],
      3 => [$this->timelabURL, 'String']
    ];
    if(count($exceptTypes)){
      $sqlParams[4] = [implode(',', $exceptTypes), 'CommaSeparatedIntegers'];
    }
    if(count($stromen)){
      $sqlParams[5] = [implode(',', $stromen), 'CommaSeparatedIntegers'];
    }
    if(count($projects)) {
      if (!isset($projects['type'])) {
        $sqlParams[6] = [implode(',', $projects), 'CommaSeparatedIntegers'];
      } else {
        $sqlParams[8] = [$projects['type'], "String", CRM_Core_DAO::QUERY_FORMAT_WILDCARD];
      }
    }
    if(count($onlyTypes)){
      $sqlParams[9] = [implode(',', $onlyTypes), 'CommaSeparatedIntegers'];
    }
    if($limit == 1 && is_numeric($orderdirection)){
      $sqlParams[7] = [intval($orderdirection), 'Integer'];
    }

    $sql = "
      select distinct
        e.id
        , e.title
        , e.start_date
        , e.end_date
        , e.summary
        , e.event_type_id
        , e.is_monetary
        , e.is_online_registration
        , e.registration_start_date
        , e.registration_end_date
        , ov.label as event_type
        , concat(%3, 'sites/all/files/civicrm/custom/', f.uri) as image
        , f.id as image_file_id
        , i.stroom_43 as stroom
      from
        civicrm_event e
      inner join
        civicrm_option_value ov on ov.value = e.event_type_id
      inner join
        civicrm_option_group og on ov.option_group_id = og.id and og.name = 'event_type'
      left outer join
        civicrm_value_img_9 i on i.entity_id = e.id
      left outer join
        civicrm_file f on i.featured_image_25 = f.id
      left outer JOIN
        civicrm_price_set_entity pe on pe.entity_id = e.id and pe.entity_table = 'civicrm_event'
      left outer JOIN
        civicrm_price_set ps on ps.id = pe.price_set_id
      left outer JOIN
        civicrm_price_field pf on pf.price_set_id = ps.id
      left outer JOIN
        civicrm_price_field_value pfv on pfv.price_field_id = pf.id";
    if(isset($sqlParams[8])){
      $sql .= "
      inner JOIN
        civicrm_contact c on c.id = i.project_45";
    }
    $sql .= "
      where
        e.is_active = 1
      and
        e.is_public = 1
      and
        (pfv.is_active IS NULL or pfv.is_active = 1)
      and
        ((e.end_date IS NULL and e.start_date between %1 and %2) or (e.end_date >= %1 and e.start_date <= %2)) ".
      (count($exceptTypes) ? " and e.event_type_id NOT IN (%4)" : "").
      (count($onlyTypes) ? " and e.event_type_id IN (%9)" : "").
      (count($stromen) ? " and i.stroom_43 IN (%5)" : "").
      (isset($sqlParams[6]) ? " and i.project_45 IN (%6)" : "").
      (isset($sqlParams[8]) ? " and c.contact_sub_type LIKE %8" : "").
      (($limit == 1 && is_numeric($orderdirection)) ? " and e.id = %7 " : '').
      (is_numeric($orderdirection) ? '' :  " order by ".($orderdirection == 'DESC' ? "
        IF(e.end_date IS NOT NULL, e.end_date, e.start_date) $orderdirection," : '')." e.start_date $orderdirection
      ");
    if($limit){
      if($page > 1) {
        $lstart = ($page-1)*$limit;
        $sql .= " limit $lstart,$limit";
      }
      else {
        $sql .= " limit $limit";
      }
    }

    $events = [];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    while ($dao->fetch()) {
      $event = $dao->toArray();
      $event['event_type'] = [
        'id' => $dao->event_type_id,
        'name' => $dao->event_type,
      ];
      $events[] = $event;
    }

    return $events;
  }


}
