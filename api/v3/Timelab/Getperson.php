<?php

function _civicrm_api3_timelab_Getperson_spec(&$spec) {
    $spec['id']['api.required'] = 1;
}

function civicrm_api3_timelab_Getperson($params) {
    try {
        // check the params
        if (!array_key_exists('id', $params) || !is_numeric($params['id'])) {
            throw new Exception('id is required and must be numeric');
        }

        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image,
            p.bio_15 as bio
          from
            civicrm_contact as c
          left join
            civicrm_value_public_5 as p
            on c.id = p.entity_id
          where
            c.id = %1
          order by
            c.sort_name
          limit 1
        ";
        $sqlParams = [
            1 => [$params['id'], 'Integer']
        ];

        $person = [];

        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $person[] = $dao->toArray();
        }

        // get projects
        $sql = "
          select
            cb.id,
            cb.display_name,
            cb.image_URL as image,
            group_concat(rt.label_a_b) as label_a_b
          from
            civicrm_relationship as r
          left join
            civicrm_relationship_type as rt
            on r.relationship_type_id = rt.id
          left join
            civicrm_contact as cb
            on cb.id = r.contact_id_b
          where
            r.contact_id_a = %1
            and (r.end_date IS NULL or r.end_date > NOW())
            and cb.is_deleted = 0
            and cb.contact_type = 'Organization'
          GROUP BY
            cb.id";
        $sqlParams = [
            1 => [$person[0]['id'], 'Integer']
        ];

        $projects = [];
        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $projects[] = $dao->toArray();
        }
        $person[0]['projects'] = $projects;

        // get participations
        $sql = "
          select
              e.id
            , e.title
            , e.start_date
            , e.end_date
            , e.summary
            , e.event_type_id
            , e.is_monetary
            , ov.label as event_type
            , concat(%2, 'sites/all/files/civicrm/custom/', f.uri) as image
            , i.stroom_43 as stroom
          from
            civicrm_participant as p
          left join
            civicrm_event as e on e.id = p.event_id
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
            p.contact_id = %1
          order by
            e.start_date DESC";
        $sqlParams = [
            1 => [$person[0]['id'], 'Integer'],
            2 => [CRM_Utils_System::baseURL(), 'String']
        ];

        $events = [];
        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $events[] = $dao->toArray();
        }
        $person[0]['events'] = $events;


        return civicrm_api3_create_success($person, $params, 'Timelab', 'getPerson');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}

