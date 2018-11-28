<?php

class CRM_Timelab_Event {
  private $timelabURL;

  public function __construct() {
    $this->timelabURL = CRM_Utils_System::baseURL();
  }

  public function getEventDetails($id) {
    $sql = "
      select
        e.id
        , e.title
        , e.start_date
        , e.end_date
        , e.summary
        , e.description
        , e.event_type_id
        , ov.label as event_type
        , concat(%2, 'civicrm/file?reset=1&filename=', f.uri, '&mime-type=', f.mime_type) as image
        , e.is_monetary
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
      where 
        e.id = %1
    ";
    $sqlParams = [
      1 => [$id, 'Integer'],
      2 => [$this->timelabURL, 'String'],
    ];

    $event = [];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      $event['id'] = $dao->id;
      $event['title'] = $dao->title;
      $event['start_date'] = $dao->start_date;
      $event['end_date'] = $dao->end_date;
      $event['summary'] = $dao->summary;
      $event['description'] = $dao->description;
      $event['event_type'] = [
        'id' => $dao->event_type_id,
        'name' => $dao->event_type,
      ];
      $event['image'] = $dao->image;
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
        and
          pfv.is_active = 1
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

    return $event;
  }

  public function getEventParticipants($id) {
    $sql = "
      select
        c.display_name
        , c.first_name
        , c.last_name
        , c.organization_name
        , ov.label role
      from
        civicrm_event e
      inner join
        civicrm_participant p on e.id = p.event_id          
      inner join 
        civicrm_contact c on c.id = p.contact_id
      inner join 
        civicrm_participant_status_type st on st.id = p.status_id
      inner join
        civicrm_option_value ov on ov.value = p.role_id
      inner join 
        civicrm_option_group og on ov.option_group_id = og.id and og.name = 'participant_role'      
      where 
        e.id = %1
      and
        e.is_active = 1
      and
        e.is_public = 1
      and 
        st.is_counted = 1
      order by 
        c.sort_name
    ";
    $sqlParams = [
      1 => [$id, 'Integer'],
    ];

    $participants = [];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    while ($dao->fetch()) {
      $participant = [];

      $participant['display_name'] = $dao->display_name;
      $participant['first_name'] = $dao->first_name;
      $participant['last_name'] = $dao->last_name;
      $participant['organization'] = $dao->organization;
      $participant['role'] = $dao->role;

      $participants[] = $participant;
    }

    return $participants;
  }

  public function getEventList($fromDate, $toDate) {
    $sql = "
      select
        e.id
        , e.title
        , e.start_date
        , e.end_date
        , e.summary
        , e.event_type_id
        , ov.name as event_type
        , concat(%3, 'civicrm/file?reset=1&filename=', f.uri, '&mime-type=', f.mime_type) as image
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
      where 
        e.is_active = 1
      and
        e.is_public = 1
      and 
        e.start_date between %1 and %2
      order by 
        e.start_date 
    ";
    $sqlParams = [
      1 => [$fromDate . ' 00:00:00', 'String'],
      2 => [$toDate . ' 23:59:59', 'String'],
      3 => [$this->timelabURL, 'String'],
    ];

    $events = [];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    while ($dao->fetch()) {
      $event = [];

      $event['id'] = $dao->id;
      $event['title'] = $dao->title;
      $event['start_date'] = $dao->start_date;
      $event['end_date'] = $dao->end_date;
      $event['summary'] = $dao->summary;
      $event['description'] = $dao->description;
      $event['event_type'] = [
        'id' => $dao->event_type_id,
        'name' => $dao->event_type,
      ];
      $event['image'] = $dao->image;

      $events[] = $event;
    }

    return $events;
  }
}