<?php

require_once __DIR__  . '/../../../timelabfunctions.php';

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
              $filterProjects .= ' and cb.id = ' . intval($params['project']) . ' and c.id != ' . intval($params['project']);

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
                $extrajoins .= '
                  inner join
                    civicrm_relationship_type as rt on rt.id = r.relationship_type_id
                ';
                $extrafields .= '
                           , GROUP_CONCAT(DISTINCT(r.relationship_type_id)) AS relationship_type
                           , GROUP_CONCAT(DISTINCT(r.description)) AS project_roles
                           , GROUP_CONCAT(DISTINCT(rt.label_b_a)) as label ';
              }
            }
            if(!$params['project_api_key']) {
              if ($params['project'] == 2402) { // timelab
                $extrafields .= ', GROUP_CONCAT(DISTINCT(e.email)) as email';
                $extrajoins .= ' left join civicrm_email as e on e.contact_id = c.id  and e.email LIKE "%@timelab.org" ';
              }
              else {
                $filterProjects .= ' and cb.is_deleted = 0 and cb.contact_type = \'Organization\' ';
                if(!array_key_exists('project', $params)){
                  $subTypes = ['Project', 'Project_timelab'];
                  if(array_key_exists('include_archived', $params) && $params['include_archived'] == true) {
                    $subTypes[] = 'Project_onhold';
                  }
                  $filterProjects .= ' and cb.contact_sub_type IN (\''.implode('\',\'', $subTypes).'\') ';
                }
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
          $extrafields .= ', GROUP_CONCAT(DISTINCT(r.relationship_type_id)) AS relationship_type
                           , GROUP_CONCAT(DISTINCT(r.description)) AS project_roles';
        }

        if(array_key_exists('contact_type', $params) || array_key_exists('types', $params)) {
          if(!array_key_exists('contact_type', $params)) {
            $params['contact_type'] = [];
          }
          else if(!is_array($params['contact_type'])) {
            $params['contact_type'] = [$params['contact_type']];
          }

          if(array_key_exists('types', $params)) {
            if(!is_array($params['types'])) {
              $params['contact_type'][] = $params['types'];
            }
            else{
              $params['contact_type'] = array_merge($params['contact_type'], $params['types']);
            }
          }

          if(count($params['contact_type']) > 0) {
            foreach ($params['contact_type'] as $k => $type) {
              $params['contact_type'][$k] = "'" . addslashes($type) . "'";
            }

            $filterProjects .= ' and c.contact_type IN('.implode(',', $params['contact_type']).') ';
          }
        }

        $sql = "
          select
            c.id,
            c.display_name,
            c.image_URL as image,
            p.bio_15 as bio,
            c.contact_type,
            gdpr.may_be_shown_on_site__54 as may_be_shown_on_site,
            o.ordering_value_67 as ordering_value
            $extrafields
          from
            civicrm_contact as c
          left join
            civicrm_value_public_5 as p on c.id = p.entity_id
          left join
            civicrm_value_gdpr_34 as gdpr on c.id = gdpr.entity_id
          inner join
            civicrm_relationship as r on r.contact_id_a = c.id OR r.contact_id_b = c.id
          inner join
            civicrm_contact as cb on r.contact_id_b = cb.id OR r.contact_id_a = cb.id
          left join
            civicrm_value_ordering_37 as o on c.id = o.entity_id
          $extrajoins
          where
            c.is_deleted = 0
            and (r.end_date IS NULL or r.end_date > NOW())
            and (c.contact_type != 'Individual' or gdpr.may_be_shown_on_site__54 IS NULL or gdpr.may_be_shown_on_site__54 != 'no')
            and (c.contact_sub_type IS NULL or c.contact_sub_type NOT LIKE \"%Project%\")
            $extraWhere
            $filterProjects
            $filterRelationshipType
          group by
            c.id
          order by
            IF(o.ordering_value_67 IS NULL OR o.ordering_value_67 = '', c.display_name, o.ordering_value_67)
        ";

        $people = [];

        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $arr = $dao->toArray();
            if(!empty($arr['image'])) {
              $arr['image'] = timelab_cleanCivicrmUrl($arr['image']);
            }
            $people[] = $arr;
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
