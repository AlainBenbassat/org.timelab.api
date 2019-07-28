<?php

function _civicrm_api3_timelab_Getpeople_spec(&$spec) {
}

function civicrm_api3_timelab_Getpeople($params) {
    try {
        $sqlParams = [];
        $extrajoins  = '';
        $extrafields = '';
        $filterProjects = "";
        if (array_key_exists('project', $params)) {
            $filterProjects = 'and r.contact_id_b = '.intval($params['project']);
            $extrajoins .= ' inner join civicrm_contact as cb on r.contact_id_b = cb.id';
        }
        $filterRelationshipType = "";
        if (array_key_exists('relationship_type', $params)) {
            $filterRelationshipType = 'and r.relationship_type_id = '.intval($params['relationship_type']);
        }

        if($params['project'] == 2402) { // timelab
            $extrafields .= ', e.email as email, tel.phone as phone';
            $extrajoins .= ' left join civicrm_phone as tel on tel.contact_id = c.id, left join civicrm_email as e on e.contact_id = c.id';
        }

        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image,
            p.bio_15 as bio
            $extrafields
          from
            civicrm_contact as c
          left join
            civicrm_value_public_5 as p on c.id = p.entity_id
          inner join
            civicrm_relationship as r on r.contact_id_a = c.id
          $extrajoins
          where
            c.is_deleted = 0
            and (r.end_date IS NULL or r.end_date > NOW()) " .
          ($filterProjects ? "" : ("and cb.is_deleted = 0 and cb.contact_type = 'Organization' and cb.contact_sub_type = 'Project'")) . "
            $filterProjects
            $filterRelationshipType
          group by
            r.contact_id_a
          order by
            c.sort_name
        ";

        $people = [];

        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $people[] = $dao->toArray();
        }

        return civicrm_api3_create_success($people, $params, 'Timelab', 'getPeople');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}

