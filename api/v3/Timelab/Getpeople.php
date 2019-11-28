<?php

function _civicrm_api3_timelab_Getpeople_spec(&$spec) {
}

function civicrm_api3_timelab_Getpeople($params, $extraWhere = '') {
    try {
        $sqlParams = [];
        $extrajoins  = '';
        $extrafields = '';
        $filterProjects = "";
        if (array_key_exists('project', $params) || !array_key_exists('relationship_type', $params)) {
            if (array_key_exists('project', $params)) {
              $filterProjects .= ' and r.contact_id_b = ' . intval($params['project']);

              if(array_key_exists('project_api_key', $params)){
                $sql = "select id from civicrm_contact a where api_key = %1 limit 1";
                $sqlParams = [
                  1 => [$params['project_api_key'], 'String'],
                ];
                $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
                if($dao->fetchValue()) {
                  $extrafields .= ', c.first_name, c.last_name, c.organization_name, c.job_title, c.birth_date, c.gender_id ';
                }
                else{
                  unset($params['project_api_key']);
                }
              }
            }
            if(!$params['project_api_key']) {
              if ($params['project'] == 2402) { // timelab
                $extrafields .= ', GROUP_CONCAT(DISTINCT(e.email)) as email';
                $extrajoins .= ' left join civicrm_email as e on e.contact_id = c.id  and e.email LIKE "%@timelab.org" ';
              }
              else {
                $filterProjects .= ' and cb.is_deleted = 0 and cb.contact_type = \'Organization\' ' .
                  'and cb.contact_sub_type = \'Project\' ';
                $extrajoins .= '';
              }
            }
        }

        $filterRelationshipType = "";
        if (array_key_exists('relationship_type', $params)) {
          if(!array_key_exists('IN', $params['relationship_type'])) {
            $filterRelationshipType = ' and r.relationship_type_id = ' . intval($params['relationship_type']);
          }
          else{
            $sqlParams[1] = [implode(',', $params['relationship_type']['IN']), 'CommaSeparatedIntegers'];
            $filterRelationshipType = ' and r.relationship_type_id IN (%1) ';
          }
          $extrafields .= ', GROUP_CONCAT(DISTINCT(r.relationship_type_id)) AS relationship_type';
        }

        if(array_key_exists('contact_type', $params)) {
          $filterProjects .= ' and c.contact_type = %2 ';
          $sqlParams[2] = [$params['contact_type'], 'String'];
        }

        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image,
            p.bio_15 as bio,
            c.contact_type,
            gdpr.may_be_shown_on_site__54 as may_be_shown_on_site
            $extrafields
          from
            civicrm_contact as c
          left join
            civicrm_value_public_5 as p on c.id = p.entity_id
          left join
            civicrm_value_gdpr_34 as gdpr on c.id = gdpr.entity_id
          inner join
            civicrm_relationship as r on r.contact_id_a = c.id
          inner join
            civicrm_contact as cb on r.contact_id_b = cb.id
          $extrajoins
          where
            c.is_deleted = 0
            and (r.end_date IS NULL or r.end_date > NOW())
            and (gdpr.may_be_shown_on_site__54 IS NULL or gdpr.may_be_shown_on_site__54 != 'no')
            $extraWhere
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

        if(array_key_exists('project_api_key', $params)){
          foreach($people as $pi => $p) {
            $people[$pi]['email'] = [];
            $sql = "select * from civicrm_email where contact_id = %1";
            $sqlParams = [
              1 => [$p['id'], 'Integer'],
            ];
            $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
            while ($dao->fetch()) {
              $people[$pi]['email'][] = $dao->toArray();
            }
          }
          foreach($people as $pi => $p) {
            $people[$pi]['phone'] = [];
            $sql = "select * from civicrm_phone where contact_id = %1";
            $sqlParams = [
              1 => [$p['id'], 'Integer'],
            ];
            $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
            while ($dao->fetch()) {
              $people[$pi]['phone'][] = $dao->toArray();
            }
          }
          foreach($people as $pi => $p) {
            $people[$pi]['website'] = [];
            $sql = "select * from civicrm_website where contact_id = %1";
            $sqlParams = [
              1 => [$p['id'], 'Integer'],
            ];
            $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
            while ($dao->fetch()) {
              $people[$pi]['website'][] = $dao->toArray();
            }
          }
          foreach($people as $pi => $p) {
            $people[$pi]['address'] = [];
            $sql = "select * from civicrm_address where contact_id = %1";
            $sqlParams = [
              1 => [$p['id'], 'Integer'],
            ];
            $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
            while ($dao->fetch()) {
              $people[$pi]['address'][] = $dao->toArray();
            }
          }
        }

        return civicrm_api3_create_success($people, $params, 'Timelab', 'getPeople');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}
