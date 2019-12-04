<?php

function _civicrm_api3_timelab_Getprojects_spec(&$spec) {
}

function civicrm_api3_timelab_Getprojects($params, $extraWhere = '') {
    $types = ['Project_timelab', 'Project'];
    if (array_key_exists('types', $params)){
        $types = $params['types'];
    }
    try {
        foreach($types as $k => $t) {
          $types[$k] = addslashes(trim($t));
        }
        $typestring = "'".implode("','",$types)."'";
        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image,
            c.contact_sub_type as type,
            p.bio_15 as bio,
            s.stroom_44 as stream,
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
          where
            c.is_deleted = 0
            and c.contact_type = 'Organization'
            and contact_sub_type in($typestring)
          group by
            c.id
          order by
            c.sort_name
        ";

        $projects = [];
        $dao = CRM_Core_DAO::executeQuery($sql, []);
        while ($dao->fetch()) {
            $projects[] = $dao->toArray();
        }

        return civicrm_api3_create_success($projects, $params, 'Timelab', 'getProjects');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}