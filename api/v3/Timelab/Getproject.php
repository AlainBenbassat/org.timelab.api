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

        return civicrm_api3_create_success($project, $params, 'Timelab', 'Getproject');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}

