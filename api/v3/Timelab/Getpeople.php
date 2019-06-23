<?php

function _civicrm_api3_timelab_Getpeople_spec(&$spec) {
}

function civicrm_api3_timelab_Getpeople($params) {
    try {
        $sqlParams = [];
        $filterProjects = "";
        if (array_key_exists('project', $params)) {
            $filterProjects = 'and r.contact_id_b = '.intval($params['project']);
        }
        $filterRelationshipType = "";
        if (array_key_exists('relationship_type', $params)) {
            $filterRelationshipType = 'and r.relationship_type_id = '.intval($params['relationship_type']);
        }

        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image
          from
            civicrm_contact as c
          inner join
            civicrm_relationship as r on r.contact_id_a = c.id
          where
            is_deleted = 0
            and (r.end_date IS NULL or r.end_date > NOW())
            $filterProjects
            $filterRelationshipType
          group by
            r.contact_id_a
          order by
            sort_name
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

