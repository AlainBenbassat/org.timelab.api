<?php
require_once __DIR__  . '/../../../timelabfunctions.php';

function _civicrm_api3_timelab_Getgallery_spec(&$spec) {
}

function civicrm_api3_timelab_Getgallery($params) {
    try {
        $typestring = timelab_getProjectTypesSQL(array_key_exists('types', $params) ? $params['types'] : null, 'c');

        // get pictures
        $sql = "
              select
                f.uri as src,
                f.mime_type as mime_type,
                c.id as project_id,
                c.display_name as project_name
              from
                civicrm_value_foto_gallery_32 as fg
              left join
                civicrm_file as f on f.id = fg.foto_52
              left join
                civicrm_contact as c on c.id = fg.entity_id
              where
                c.is_deleted = 0
                and c.contact_type = 'Organization'
                $typestring";

        $images = [];
        $dao = CRM_Core_DAO::executeQuery($sql, []);
        while ($dao->fetch()) {
          $images[] = $dao->toArray();
        }

        return civicrm_api3_create_success($images, $params, 'Timelab', 'getGallery');
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}
