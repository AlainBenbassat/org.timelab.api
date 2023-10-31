<?php

require_once __DIR__  . '/../../../timelabfunctions.php';

function _civicrm_api3_timelab_Getpeople_spec(&$spec) {
}

function civicrm_api3_timelab_Getpeople($params, $extraWhere = '') {
    try {
        $sqlParams = [];
        $extrajoins  = '';
        $extrafields = '';
        $cbIds = '';
        if (array_key_exists('project', $params) || !array_key_exists('relationship_type', $params)) {
            if (array_key_exists('project', $params) && $params['project'] != 'any') {
              $cbIds = intval($params['project']) ;
              $extraWhere .= " and c.id != $cbIds and (r.contact_id_a = $cbIds OR r.contact_id_b = $cbIds)";

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
                $cbIdsSql = 'SELECT id FROM civicrm_contact WHERE is_deleted = 0 and contact_type = \'Organization\' ';
                if(!array_key_exists('project', $params) || $params['project'] == 'any'){
                  $subTypes = ['Project', 'Project_timelab'];
                  if(array_key_exists('include_archived', $params) && $params['include_archived'] == true) {
                    $subTypes[] = 'Project_onhold';
                  }
                  $cbIdsSql .= ' and contact_sub_type IN (\''.implode('\',\'', $subTypes).'\') ';
                }
                $dao = CRM_Core_DAO::executeQuery($cbIdsSql, []);
                $cbIds = [2402];
                while ($dao->fetch()) {
                  $arr = $dao->toArray();
                  $cbIds[] = $arr['id'];
                }
                $cbIds = implode(',', $cbIds);

                $extrajoins .= '';
              }
            }
        }

        $exclude_relationship_type = [];
        if(array_key_exists('exclude_relationship_type', $params)) {
          if(!array_key_exists('IN', $params['exclude_relationship_type'])) {
            $exclude_relationship_type = [$params['exclude_relationship_type']];
          }
          else {
            $exclude_relationship_type = $params['exclude_relationship_type']['IN'];
          }
        }

        $filterRelationshipType = "";
        if (array_key_exists('relationship_type', $params)) {
          if(!array_key_exists('IN', $params['relationship_type'])) {
            $params['relationship_type'] = ['IN' => [intval($params['relationship_type'])]];
          }
          $in = array_merge($params['relationship_type']['IN'], $exclude_relationship_type);
          $sqlParams[1] = [implode(',', $in), 'CommaSeparatedIntegers'];
          $filterRelationshipType = ' and r.relationship_type_id IN (%1) ';
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

            $extraWhere .= ' and c.contact_type IN('.implode(',', $params['contact_type']).') ';
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
            civicrm_value_gdpr_34 as gdpr on
                c.id = gdpr.entity_id
                and (c.contact_type != 'Individual' or gdpr.may_be_shown_on_site__54 IS NULL or gdpr.may_be_shown_on_site__54 != 'no')
          inner join
            civicrm_relationship as r on
                (r.end_date IS NULL or r.end_date > NOW())
                and r.is_active = 1
                and (r.contact_id_a = c.id OR r.contact_id_b = c.id)
                and (r.contact_id_b IN ($cbIds) or r.contact_id_a IN ($cbIds))
                $filterRelationshipType
          left join
            civicrm_value_ordering_37 as o on c.id = o.entity_id
          $extrajoins
          where
            c.is_deleted = 0
            and (c.contact_sub_type IS NULL or c.contact_sub_type NOT LIKE \"%Project%\")
            $extraWhere
          group by
            c.id
          order by
            IF(o.ordering_value_67 IS NULL OR o.ordering_value_67 = '', c.display_name, o.ordering_value_67)
        ";

        $people = [];

        //var_dump($sql, $sqlParams); die();
        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        while ($dao->fetch()) {
            $arr = $dao->toArray();
            if(!empty($arr['image'])) {
              $arr['image'] = timelab_cleanCivicrmUrl($arr['image']);
            }
            $relationship_types = explode(',', $arr['relationship_type']);
            $exclude = false;
            foreach($exclude_relationship_type as $ert) {
              if(in_array($ert, $relationship_types)) {
                $exclude = true;
                break;
              }
            }
            if(!$exclude) {
              $people[] = $arr;
            }
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
