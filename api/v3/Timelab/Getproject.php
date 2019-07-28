<?php

function _civicrm_api3_timelab_Getproject_spec(&$spec) {
    $spec['id']['api.required'] = 1;
}

function civicrm_api3_timelab_Getproject($params) {
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
            p.bio_15 as bio,
            GROUP_CONCAT(w.url) as websites
          from
            civicrm_contact as c
          left join
            civicrm_value_public_5 as p
            on c.id = p.entity_id
          left join
            civicrm_website as w
            on c.id = w.contact_id
          where
            c.id = %1
          group by
            c.id
          order by
            c.sort_name
          limit 1
        ";
        $sqlParams = [
            1 => [$params['id'], 'Integer']
        ];

        $project = [];

        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $p = $dao->toArray();
            if(strlen($p['websites']) > 0) {
                $p['websites'] = explode(',', $p['websites']);
            }
            $project[] = $p;
        }

        // get people
        $sql = "
          select
            ca.id,
            ca.display_name,
            ca.image_URL as image,
            group_concat(rt.label_b_a) as label_b_a
          from
            civicrm_relationship as r
          left join
            civicrm_relationship_type as rt
            on r.relationship_type_id = rt.id
          left join
            civicrm_contact as ca
            on ca.id = r.contact_id_a
          where
            r.contact_id_b = %1
            and (r.end_date IS NULL or r.end_date > NOW())
            and ca.is_deleted = 0
          GROUP BY
            ca.id";
        $sqlParams = [
            1 => [$project[0]['id'], 'Integer']
        ];

        $people = [];
        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $people[] = $dao->toArray();
        }
        $project[0]['people'] = $people;

        // get events
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
            i.project_45 = %1
          order by
            e.start_date DESC";
        $sqlParams = [
            1 => [$project[0]['id'], 'Integer'],
            2 => [CRM_Utils_System::baseURL(), 'String']
        ];

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
        $project[0]['events'] = $events;

        // get documents
        $sql = "
          select
            pd.korte_omschrijving_document_48 as description,
            f.uri as document,
            f.mime_type as mime_type
          from
            civicrm_value_projectdocume_28 as pd
          left join
            civicrm_file as f on f.id = pd.document_49
          where 
            pd.entity_id = %1";
        $sqlParams = [
            1 => [$project[0]['id'], 'Integer'],
        ];

        $docs = [];
        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $docs[] = $dao->toArray();
        }
        $project[0]['docs'] = $docs;

        return civicrm_api3_create_success($project, $params, 'Timelab', 'Getproject');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}

