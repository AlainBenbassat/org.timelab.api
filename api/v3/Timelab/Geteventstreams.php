<?php

function _civicrm_api3_timelab_Geteventstreams_spec(&$spec) {
}

function civicrm_api3_timelab_Geteventstreams($params) {
    try {
        return civicrm_api3('OptionValue', 'get', [
            'sequential' => 1,
            'return' => ["id", "label", "value", "description", "is_active"],
            'option_group_id' => "stroom_20190208143953",
            'options' => ['sort' => "weight"],
        ]);
    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());
    }
}

