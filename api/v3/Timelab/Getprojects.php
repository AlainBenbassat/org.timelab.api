<?php

function _civicrm_api3_timelab_Getprojects_spec(&$spec) {
}

function civicrm_api3_timelab_Getprojects($params, $extraWhere = '') {
    $types = ['Project_timelab', 'Project'];
    if (array_key_exists('types', $params)){
        $types = $params['types'];
    }
    try {
        $typestring = '';
        if(count($types)) {
          foreach ($types as $k => $t) {
            $types[$k] = addslashes(trim($t));
            if($k != 0) {
              $typestring .= " OR ";
            }
            $typestring .= 'contact_sub_type LIKE "%' . $types[$k] . '%"';
          }
          $typestring = "AND ($typestring)";
        }
        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image,
            c.contact_sub_type as type,
            p.bio_15 as bio,
            s.stroom_44 as stream,
            o.ordering_value_67 as ordering_value,
            GROUP_CONCAT(sv.label) as stream_label
          from
            civicrm_contact as c
          left join
            civicrm_value_public_5 as p
            on c.id = p.entity_id
          left join
            civicrm_value_extra_project_23 as s
            on c.id = s.entity_id
          left join
            civicrm_option_value as sv
            on sv.option_group_id = 132 and sv.value = s.stroom_44
          left join
            civicrm_value_ordering_37 as o
            on c.id = o.entity_id
          where
            c.is_deleted = 0
            and c.contact_type = 'Organization'
            $typestring
          group by
            c.id
          order by
            IF(o.ordering_value_67 IS NULL OR o.ordering_value_67 = '', c.sort_name, o.ordering_value_67)
        ";

        $projects = [];
        $dao = CRM_Core_DAO::executeQuery($sql, []);
        while ($dao->fetch()) {
            $p = $dao->toArray();
            $p['type'] = explode('' , $p['type']);
            foreach($p['type'] as $k => $t) {
              if(trim($t) == '') {
                unset($p['type'][$k]);
              }
            }
            $p['type'] = array_values($p['type']);
            $projects[] = $p;
        }

        return civicrm_api3_create_success($projects, $params, 'Timelab', 'getProjects');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}
