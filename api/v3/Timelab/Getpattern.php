<?php

function _civicrm_api3_timelab_Getpattern_spec(&$spec) {
    $spec['id']['api.required'] = 1;
}

function civicrm_api3_timelab_Getpattern($params) {
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
            and c.is_deleted = 0
            and c.contact_type = 'Organization'
            and c.contact_sub_type LIKE '_patterns_'
          limit 1
        ";
        $sqlParams = [
            1 => [$params['id'], 'Integer']
        ];

        $pattern = null;

        $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
        if ($dao->fetch()) {
          $pattern = $dao->toArray();
        }

        return civicrm_api3_create_success($pattern, $params, 'Timelab', 'getPattern');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}

