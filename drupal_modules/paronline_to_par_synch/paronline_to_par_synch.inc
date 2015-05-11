<?php

function make_entry_in_par_log( $op, $params, $log_action = NULL ) {
  if (!empty($params['primary_contact_id'])) {
    $externalIdentifier = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $params['primary_contact_id'], 'external_identifier');
    $extra = explode("-", $externalIdentifier); 
    if (isset($extra['2'])) {
      if (isset($params['par_donor_name'])) {
        $op = 'Update';
        $params = array( 
          'par_donor_name' => $params['par_donor_name'],
          'external_identifier' => $params['external_identifier']
        );
      }
      else {
        return;
      }
    }
  }
  if (!empty($params['par_donor_name'])) {
    $params['par_donor_name'] = CRM_Core_DAO::escapeString($params['par_donor_name']);
  }
  if ( $op == 'Update' || $op == 'Delete' ) {
    if ( array_key_exists('external_identifier' , $params) ) {
      $where = "WHERE external_identifier = '".$params['external_identifier']."'";
    } else {
      $where = "WHERE primary_contact_id = ".$params['primary_contact_id'];
    }
    $update = "SET log_action = '". (($log_action) ? $log_action : $op) ."', log_time = now()," ;
    foreach ( $params as $key => $value ) {
      if ('primary_contact_id' == $key ) {
        //$update .= $key." = ".$value.", ";
      } else {
        $update .= $key." = '".$value."', ";
      }
    }
    $query = "UPDATE civicrm_log_par_donor ".rtrim( $update, ", " )." ". $where;
     
  } else if ( $op == 'Insert' ) {
    if ( array_key_exists( 'primary_contact_id' , $params) ) {
      $contact_id = $params['primary_contact_id'];
      unset($params['primary_contact_id']);
    }
    $query = "INSERT INTO civicrm_log_par_donor (".implode(", " ,array_keys($params) ).", primary_contact_id, log_action ) VALUES ( '".implode("', '" ,$params )."', ".$contact_id.", 'INSERT'  )";
  }  else if ( $op == 'Delete' ) {
    $update = "SET log_action = '".$op."', log_time = now(),";
    foreach ( $params as $key => $value ) {
      if ('primary_contact_id' == $key ) {
        //$update .= $key." = ".$value.", ";
      } else {
        $update .= $key." = '".$value."', ";
      }
    }
    $query = "UPDATE civicrm_log_par_donor ".rtrim( $update, ", " )." ". $where;
  }
  
  $result = CRM_Core_DAO::singleValueQuery($query);
} 