<?php

function timelab_cleanCivicrmUrl($url) {
  if(strpos($url, "/civicrm/contact/imagefile?photo=") !== false){
    $url = CIVICRM_UF_BASEURL . 'sites/all/files/civicrm/custom/' . substr($url, strpos($url, '?photo=') + 7);
  }
  else {
    if (strpos($url, 'https://timelab.org') === 0) {
      $url = CIVICRM_UF_BASEURL . substr($url, 20);
    } else if (strpos($url, 'http://timelab.org') === 0) {
      $url = CIVICRM_UF_BASEURL . + substr($url,19);
    } else if (strpos($url, 'http://www.timelab.org') === 0) {
      $url = CIVICRM_UF_BASEURL . substr($url, 23);
    }
  }
  return $url;
}

function timelab_getProjectTypesSQL($types = null, $tableAlias = 'civicrm_concat') {
  if($types === null) {
    $types = ['Project_timelab', 'Project'];
  }
  $typestring = '';
  if(count($types)) {
    foreach ($types as $k => $t) {
      $types[$k] = addslashes(trim($t));
      if($k != 0) {
        $typestring .= " OR ";
      }
      $typestring .= $tableAlias.'.contact_sub_type LIKE "%' . $types[$k] . '%"';
    }
    $typestring = "AND ($typestring)";
  }
  return $typestring;
}
