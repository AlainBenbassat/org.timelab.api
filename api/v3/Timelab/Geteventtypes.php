<?php

function _civicrm_api3_timelab_Geteventtypes_spec(&$spec) {
}

function civicrm_api3_timelab_Geteventtypes($params) {
    try {
        return civicrm_api3('OptionValue', 'get', [
            'sequential' => 1,
            'return' => ["id", "label", "value", "description", "is_active"],
            'option_group_id' => "event_type",
            'options' => ['sort' => "weight"],
        ]);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
  //$participantEventType = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Event", $id, 'event_type_id', 'id');
}

