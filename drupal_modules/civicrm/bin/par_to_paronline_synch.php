<?php
class CRM_import {
  function __construct( ) {
    // you can run this program either from an apache command, or from the cli
    $this->initialize( );
    $config = CRM_Core_Config::singleton();
  }
    
  function initialize( ) {
        
    require_once '../civicrm.config.php';
    require_once 'CRM/Core/Config.php';
        
    $config = CRM_Core_Config::singleton();
  }
    
  public function generateSQL( ) {
        
    $config = CRM_Core_Config::singleton();
    $getDBdetails = explode( '/',  $config->dsn);
    $dbName       = explode( '?',  $getDBdetails[3]);
    $this->_dbName       = $dbName[0];
    $userName     = explode( '@', $getDBdetails[2] );
    $userName    = explode( ':', $userName[0] );
    $this->_pass         = $userName[1];
    $this->_userName     = $userName[0];
    require_once 'CRM/Core/DAO.php';
    
    $getTable = "show tables like 'civicrm_temp_import'";
    $table = CRM_Core_DAO::executeQuery($getTable);
    if ( !empty( $table->N ) ) {
      $deleteTable = "DROP TABLE ".$this->_dbName.".`civicrm_temp_import`";
      CRM_Core_DAO::executeQuery($deleteTable);
    }
        
    $createTable = "CREATE TABLE ".$this->_dbName.".`civicrm_temp_import` (`par_donor_id` INT(10) NOT NULL, `par_donor_bank_id` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, `par_donor_branch_id` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, `par_donor_account` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, `par_donor_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, `par_donor_type` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, `par_donor_owner_id` INT(10) NOT NULL, `par_donor_envelope` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL, `par_donor_nsf` TINYINT(4) NULL DEFAULT NULL, `par_donor_removed` TINYINT(4) NULL DEFAULT NULL, `par_donor_ms_amount` DECIMAL(20,2) NOT NULL, `par_donor_cong_amount` DECIMAL(20,2) NOT NULL, `par_donor_other_amount` DECIMAL(20,2) NOT NULL, `par_donor_update_date` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, `par_donor_ms_number` INT(10) UNSIGNED NOT NULL, `par_donor_audit_number` INT(10) UNSIGNED NOT NULL, `organization_name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL )";
        
    $result = CRM_Core_DAO::executeQuery($createTable);
        
    $read  = fopen('par_donor.csv', 'r' );
    $write  = fopen('par_donor.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $count = $others = 0;
    static $id_no ='';
    while ( $rows = fgetcsv( $read ) ) {
      //if( $rows['22'] == 1) {
        $insert = "INSERT INTO civicrm_temp_import( par_donor_id, par_donor_bank_id, par_donor_branch_id, par_donor_account, par_donor_name, par_donor_type, par_donor_owner_id, par_donor_envelope, par_donor_nsf, par_donor_removed, par_donor_ms_amount, par_donor_cong_amount, par_donor_other_amount, par_donor_update_date, par_donor_ms_number, organization_name ) VALUES ( {$rows[0]}, '{$rows[1]}', '{$rows[3]}', '{$rows[5]}', '{$rows[6]}', '{$rows[7]}', {$rows[8]}, '{$rows[9]}', '{$rows[11]}', '{$rows[12]}', {$rows[13]}, {$rows[14]}, {$rows[15]}, '{$rows[16]}', {$rows[18]}, '{$rows[21]}' );\n";
              
        fwrite($write,$insert);
        //}
    }
    fclose($read);
    fclose($write);
    $cmd  = "mysql -u{$this->_userName} -p{$this->_pass} {$this->_dbName} < par_donor.sql";
    $test =  shell_exec( $cmd );

  }
  public function importRecords( ) {
    $recordsQuery = "SELECT * FROM civicrm_temp_import";
    $getRecords = CRM_Core_DAO::executeQuery( $recordsQuery );
    while ( $getRecords->fetch( ) ) {
      $nameArray = null;
      if ( strstr( $getRecords->par_donor_name, ';' ) ) {
        $names = explode( "; ", $getRecords->par_donor_name );
        foreach ( $names as $value ) {
          if ( strstr( $value, '&' ) ) {
            $andNames = explode( ", ", $value );
            $commaNames = explode( " & ", $andNames[0] );
            foreach( $commaNames as $commaValues ) {
              $nameArray[][$andNames[1]] = $commaValues;
            }
          } else {
            $name = explode( ", ", $value );
            $nameArray[][$name[1]] = $name[0];
          }
        }
      } else {
        if ( strstr( $getRecords->par_donor_name, '&' ) ) {
          $andNames = explode( ", ", $getRecords->par_donor_name );
          $commaNames = explode( " & ", $andNames[0] );
          foreach( $commaNames as $commaValues ) {
            $nameArray[][$andNames[1]] = $commaValues;
          }
        } else {
          $name = explode( ", ", $getRecords->par_donor_name );
          $nameArray[][$name[1]] = $name[0];
        }
      }
            
      foreach ( $nameArray as $nameKey => $nameValue ) {
        $params = null;
        if ( $nameKey == 0 )  {
          $params['external_identifier'] = 'D-'.$getRecords->par_donor_id;
          $params['version']             = 3;
          require_once 'api/api.php';
          $getContact = civicrm_api( 'contact','get',$params );
          $params['first_name']   = $nameValue[key($nameValue)];
          $params['last_name']    = key($nameValue);
          $params['sort_name']    = key($nameValue).', '.$nameValue[key($nameValue)];
          $params['display_name'] = key($nameValue).', '.$nameValue[key($nameValue)];
          $params['contact_type'] = 'Individual';
          if ( !empty($getContact['values']) ) {
            $params['id']           = $getContact['id'];
          }
          $createContactA = civicrm_api( 'contact','create',$params );
          
          if ( !empty( $createContactA['values']) && ( $getRecords->par_donor_ms_amount || $getRecords->par_donor_cong_amount || $getRecords->par_donor_other_amount ) ) {
            require_once 'CRM/Core/PseudoConstant.php';
            //$createContactA['id'] = 8392;
            require_once 'CRM/Utils/Money.php';
            $totalAmount = CRM_Utils_Money::format( $getRecords->par_donor_ms_amount + $getRecords->par_donor_cong_amount + $getRecords->par_donor_other_amount, null, null, true );
                        
            CRM_Core_PseudoConstant::populate( &$recurContribution, 'CRM_Contribute_DAO_ContributionRecur', true, 'max(id)', false, " contact_id = {$createContactA['id']} AND contribution_status_id = 5 AND amount = {$totalAmount}", 'id' );
            $contributionParams['total_amount']           = CRM_Utils_Money::format( $totalAmount, null, null, true );
            $contributionParams['contact_id']             = $createContactA['id'];
            $contributionParams['version']                = 3;
            $contributionParams['contribution_recur_id']  = current($recurContribution);
            $contributionParams['contribution_status_id'] = 1;
            $getContribution = civicrm_api( 'contribution','get', $contributionParams);
            // if ( !empty( $getContribution['values'] ) ) {
            //   print_r($getRecords->par_donor_update_date);print_r($getContribution['values'][$getContribution['id']]['receive_date']);
            //   if ( $getRecords->par_donor_update_date == $getContribution['values'][$getContribution['id']]['receive_date'] ) {
            //     $contributionParams['id']  = $getContribution['id'];
            //   }
            // } 
            // exit;
            require_once 'CRM/Price/BAO/Set.php';
            $priceSetDetails = CRM_Price_BAO_Set::getSetDetail( 2 );
            $fields       = $priceSetDetails[ 2 ][ 'fields' ];
            CRM_Core_PseudoConstant::populate( &$priceField, 'CRM_Price_DAO_Field', true, 'id', false, " price_set_id = 2", 'id' );
            $lineItem[0] =  $getRecords->par_donor_cong_amount;
            $lineItem[1] =  $getRecords->par_donor_ms_amount;
            $lineItem[2] =  $getRecords->par_donor_other_amount;
            $count = 0;
            foreach( $priceField as $fieldId ) {
              if ( $lineItem[$count] != 0.00 ) {
                $contributionParams['price_'.$fieldId] = $lineItem[$count];
              }
              $count++;
            } 
            $relParams['contact_id_a'] = $createContactA['id'];
            $relParams['version']      = 3;
            $relParams['relationship_type_id']      = 13;
            $relContact = civicrm_api( 'relationship','get', $relParams );
            if ( !empty($relContact['values'] ) ) {
              $orgID = $relContact['values'][$relContact['id']]['contact_id_b'];
              CRM_Core_PseudoConstant::populate( &$contributionType, 'CRM_Contribute_DAO_ContributionType', true, 'id', false, " contact_id = {$orgID} AND parent_id IS NULL", 'id' );
            } 
            $contributionParams['contribution_type_id'] = current($contributionType);
            CRM_Price_BAO_Set::processAmount( $fields, $contributionParams, $lineitem );
            $result = civicrm_api( 'contribution', 'create', $contributionParams );
            if ( array_key_exists( 'id', $result ) ) {
              require_once 'CRM/Contribute/Form/AdditionalInfo.php';
              $lineSet[ 2 ] = $lineitem;
              CRM_Contribute_Form_AdditionalInfo::processPriceSet( $result['id'], $lineSet );
            }
          }
        } else {
          $params['external_identifier'] = 'D-'.$getRecords->par_donor_id.'-'.$nameKey;
          $params['version']             = 3;
          require_once 'api/api.php';
          $getOtherContacts = civicrm_api( 'contact','get',$params );
          $params['first_name']   = $nameValue[key($nameValue)];
          $params['last_name']    = key($nameValue);
          $params['sort_name']    = key($nameValue).', '.$nameValue[key($nameValue)];
          $params['display_name'] = key($nameValue).', '.$nameValue[key($nameValue)];
          $params['contact_type'] = 'Individual';
          if ( !empty($getOtherContacts['values']) ) {
            $params['id']       = $$getOtherContacts['id'];
          }
          $createOtherContacts = civicrm_api( 'contact','create',$params );
        }  
      }
    }
  }
  }
$importObj = new CRM_import();
$importObj->generateSQL( );
$importObj->importRecords( );
