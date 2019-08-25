<?php

function _civicrm_api3_timelab_Getpeople_spec(&$spec) {
}

function civicrm_api3_timelab_Getpeople($params) {
    try {
        $sqlParams = [];
        $extrajoins  = '';
        $extrafields = '';
        $filterProjects = "";
        if (array_key_exists('project', $params) || !array_key_exists('relationship_type', $params)) {
            if (array_key_exists('project', $params)) {
              $filterProjects .= 'and r.contact_id_b = ' . intval($params['project']);
            }
            if($params['project'] == 2402) { // timelab
                $extrafields .= ', GROUP_CONCAT(DISTINCT(e.email)) as email, GROUP_CONCAT(DISTINCT(tel.phone)) as phone';
                $extrajoins .= ' left join civicrm_phone as tel on tel.contact_id = c.id left join civicrm_email as e on e.contact_id = c.id';
            }
            else {
                $filterProjects .= 'and cb.is_deleted = 0 and cb.contact_type = \'Organization\' ' .
                                  'and cb.contact_sub_type = \'Project\' ';
                $extrajoins .= '';
            }
        }
        $filterRelationshipType = "";
        if (array_key_exists('relationship_type', $params)) {
          if(!array_key_exists('IN', $params['relationship_type'])) {
            $filterRelationshipType = 'and r.relationship_type_id = ' . intval($params['relationship_type']);
          }
          else{
            $sqlParams[1] = [implode(',', $params['relationship_type']['IN']), 'CommaSeparatedIntegers'];
            $filterRelationshipType = 'and r.relationship_type_id IN (%1) ';
          }
          $extrafields .= ', GROUP_CONCAT(DISTINCT(r.relationship_type_id)) AS relationship_type';
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
          inner join
            civicrm_contact as cb on r.contact_id_b = cb.id
          $extrajoins
          where
            c.is_deleted = 0
            and (r.end_date IS NULL or r.end_date > NOW()) 
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

