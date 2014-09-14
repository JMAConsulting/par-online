<?php
require_once 'importExport.php';
Class CRM_par_import extends CRM_par_ImportExport {

  function __construct( ) {  
    // you can run this program either from an apache command, or from the cli
    parent::__construct();
  }
  
  function importDonorNsfData() {
    require_once 'CRM/Utils/Money.php';
    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->transactionNSFFile, 'r' );
    $newRecordsToInsert  = fopen($this->par2parOnlinePath.$this->newDirectory.'/importDonorNsfData.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $header = array_merge($this->error,$rows);
    $count = $others = 0;
    static $id_no ='';
    while ( $rows = fgetcsv( $read ) ) {
      $extrnal_id =  null;
      if ( !empty( $rows[4] ) ) {
        $ext_id =  'D-'.$rows[4];
        $extrnal_id = $rows[4];
      }
      if ( !empty( $rows[9] ) ) {
        $donor_nsf = 1;
      } else {
        $donor_nsf = 0;
      }
      
      $contributionId = $query = $result = $updateContribution = $contribution_id = $setNsfNULL = $nsfId = $insertNsfCustomData = $insert_all_records = null;

      $query = "Select MAX(id) as id FROM civicrm_contribution WHERE contact_id = (SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}') AND  contribution_status_id = 1";
      
      $con = mysql_connect( $this->localhost, "{$this->userName}", "{$this->pass}" );
      if (!$con) {
        die('Could not connect: ' . mysql_error());
      }
      mysql_select_db( "$this->dbName", $con);
      
      $result = mysql_query($query);
      while ( $info = mysql_fetch_assoc($result) ) {
        $contribution_id = $info['id'];
      }
      if ( ( $donor_nsf == 1 && $contribution_id ) ) {  
        $count++;
        $updateContribution = "UPDATE  civicrm_contribution SET contribution_status_id = 4 WHERE id = {$contribution_id};\n";
        $dao = CRM_Core_DAO::executeQuery("SELECT primary_contact_id, par_donor_bank_id, par_donor_branch_id, par_donor_account, `m&s_amount` msamount, general_amount, other_amount, total_amount,
payment_instrument_id
FROM civicrm_log_par_donor  clpd
INNER JOIN civicrm_contribution cc ON cc.contact_id = clpd.primary_contact_id AND cc.id = {$contribution_id}
WHERE external_identifier = '{$extrnal_id}';");
        if ($dao->fetch()) {
          
          $updateContribution .= "INSERT INTO civicrm_value_change_log_18 (entity_id, file_number_52, modified_by_49, modified_date_50, change_log_data_51) 
VALUES ({$dao->primary_contact_id}, NULL, 1, now(), '" . serialize(
array(
  'Status' => 'Failed',
  'Payment Instrument' => ($dao->payment_instrument_id == 6) ? 'Direct Debit' : 'Credit Card',
  'Bank #' => $dao->par_donor_bank_id,
  'Branch #' => $dao->par_donor_branch_id,
  'Account #' => $dao->par_donor_account,
  'General' => CRM_Utils_Money::format($dao->general_amount, NULL),
  'M&S' => CRM_Utils_Money::format($dao->msamount, NULL),
  'Other' => CRM_Utils_Money::format($dao->other_amount, NULL),
  'Total' => CRM_Utils_Money::format($dao->total_amount, NULL),
  'NSF' => 1,
)) . "');\n";
        }
        $insert_all_records = $updateContribution;
        fwrite($newRecordsToInsert, $insert_all_records);
      } else {
        $others++;
        if(is_array($header)) {
          $generateCSV =  fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedNSF, 'w' );
          fputcsv( $generateCSV, $header );
          $header = null;
        } else {
          $error = array();
          $error[] = 'Previous contribution is not present to update nsf contributions';
          $rows = array_merge($error, $rows);
          fputcsv( $generateCSV, $rows );
        }
      }
    }
    fclose($read);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importDonorNsfData.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Donar NsF Data failed in function : importDonorNsfData()');
    }
  }
 
  function importOrganisation() {

    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->organizationFile, 'r' );
    $newRecordsToInsert  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/importOrganization.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $header = array_merge($this->error,$rows);
    $count = $others = 0;
    static $id_no ='';
    while ( $rows = fgetcsv( $read) ) {
      if ( !empty($rows[0]) ) {
        $ext_id = 'O-'.$rows[0];
      } else {
        $ext_id =  null;
      }
      if ( !empty($rows[1]) ) {
        $organization_name = addslashes( $rows[1] );
      } else {
        $organization_name = null;
      }
      if  ( !empty($rows[4]) ) {
        $email = addslashes( $rows[4] );
      } else {
        $email = null;
      }
      if  ( !empty($rows[5]) ) {
        $street_address = addslashes( $rows[5] ); 
      } else {
        $street_address = null;
      }
      if  ( !empty($rows[6]) ) {
        $city = addslashes( $rows[6] );
      } else {
        $city = null;
      }
      if  ( !empty($rows[7]) ) {
        $province = addslashes( $rows[7] );
      } else {
        $province = null;
      }
      if  ( !empty($rows[8]) ) {
        $postal_code = addslashes( $rows[8] );
      } else {
        $postal_code = null;
      }
      if  ( !empty($rows[9]) ) {
        $phone = $rows[9];
      } else {
        $phone = null;
      }
      if  ( !empty($rows[10]) ) {
        $fax = $rows[10];
      } else {
        $fax = null;
      }
   
      $contact_subtype = $rows[2];

      if ( !empty($rows[3]) ) {
        $ms_number = $rows[3];
      } else {
        $ms_number = null;
      }
      if( $contact_subtype == 'CH' ) {
        $contact_subtype = 'Congregation';
      } else if( $contact_subtype == 'CO' ) {
        $contact_subtype = 'Conference';
      } else if( $contact_subtype == 'PR' ) {
        $contact_subtype = 'Presbytery';
      }else if( $contact_subtype == 'DN' ) {
        $contact_subtype = 'Denomination';
      }else if( $contact_subtype == 'PC' ) {
        $contact_subtype = addslashes( 'Pastoral_Charge' );
      }
      if(!empty($ext_id))
        { 
          $insert_all_rows='';
          $insert_org = "INSERT INTO civicrm_contact ( external_identifier, contact_type, contact_sub_type, sort_name, display_name, organization_name) values ('{$ext_id}','Organization', '{$contact_subtype}','{$organization_name}','{$organization_name}','{$organization_name}') ON DUPLICATE KEY UPDATE external_identifier = '{$ext_id}', contact_sub_type = '{$contact_subtype}', sort_name = '{$organization_name}', display_name = '{$organization_name}', organization_name = '{$organization_name}', modified_date = now(), is_deleted = 0;\n";
  
          $contact_id = $setContactNULL = $setParentNULL = $setGenNULL = $setSetNULL = $setGPFNULL = $setGPFVNULL = $setMSNULL = $setMSPFNULL = $setMSPFVNULL = $setONULL = $setOPFINULL = $setOPFVNULL = $parent_id = $general_id = $price_setId = $general_price_fieldId = $ms_pfvId = $ms_id = $other_pfvId = $general_pfvId = $ms_price_fieldId = $other_id = $other_price_fieldId = $parent_contribution_type = $general_contribution_type =  $insert_price_set = $insert_general_price_field = $insert_general_price_field_value = $ms_contribution_type = $insert_ms_price_field = $insert_ms_price_field_value = $other_contribution_type = $insert_other_price_field = $insert_other_price_field_value = null;
          
          $setContactNULL = "SET @contactId := '';\n";
          $contact_id = "SELECT @contactId := id FROM civicrm_contact where external_identifier ='{$ext_id}';\n";
          
          $setParentNULL = "SET @parentId := '';\n";
          $parent_id = "SELECT @parentId := id FROM civicrm_contribution_type WHERE contact_id = @contactId AND parent_id IS NULL;\n";
          
          $parent_contribution_type = "INSERT INTO civicrm_contribution_type ( id, name, accounting_code, description, is_deductible, is_active, contact_id ) values ( @parentId, '{$organization_name}',55, '{$organization_name}', 1, 1, @contactId ) ON DUPLICATE KEY UPDATE id = @parentId, name = '{$organization_name}', description = '{$organization_name}', contact_id = @contactId;\n";
          
          $parent_id =  "SELECT @parentId := id FROM civicrm_contribution_type WHERE contact_id = @contactId AND parent_id IS NULL;\n";
          $setGenNULL = "SET @general_id := '';\n";
          $general_id = "SELECT @general_id := id FROM civicrm_contribution_type WHERE name = 'General' AND contact_id = @contactId AND parent_id = @parentId;\n";
          $general_contribution_type = "INSERT INTO civicrm_contribution_type ( id, name, accounting_code, description, is_deductible, is_active, contact_id, parent_id ) values ( @general_id, 'General',55, '{$organization_name}', 1, 1, @contactId, @parentId ) ON DUPLICATE KEY UPDATE id = @general_id, description = '{$organization_name}', contact_id = @contactId , parent_id = @parentId ;\n";
          
          //$general_id = "SELECT @general_id := id FROM civicrm_contribution_type WHERE name = 'General' AND contact_id = @contactId AND parent_id = @parentId;\n";
          $setSetNULL = "SET @setId := '';\n";
          $price_setId = "SELECT @setId := id FROM civicrm_price_set WHERE contribution_type_id = @parentId;\n";

          $insert_price_set = "INSERT INTO civicrm_price_set ( id, name, title, is_active, contribution_type_id ) VALUES ( @setId, '{$organization_name}', '{$organization_name}', 1, @parentId ) ON DUPLICATE KEY UPDATE id = @setId, name = '{$organization_name}', title = '{$organization_name}';\n";
          
          //$price_setId = "SELECT @setId := id FROM civicrm_price_set WHERE name = '{$organization_name}' AND title = '{$organization_name}';\n";
          $setGPFNULL = "SET @gpfID := '';\n";
          $general_price_fieldId = "SELECT @gpfID := id FROM civicrm_price_field WHERE contribution_type_id = @general_id;\n";

          $insert_general_price_field = "INSERT INTO civicrm_price_field ( id, price_set_id, name, label, html_type, is_enter_qty, weight, is_display_amounts, options_per_line, is_active, is_required, visibility_id, contribution_type_id ) VALUES ( @gpfID, @setId, CONCAT('General_', (SELECT description FROM civicrm_contribution_type WHERE contact_id = @contactId AND name = 'General') ), 'General', 'Text', 1, 1, 0, 1, 1, 0, 1, @general_id ) ON DUPLICATE KEY UPDATE id = @gpfID,contribution_type_id = @general_id, price_set_id = @setId, name = CONCAT('General_', (SELECT description FROM civicrm_contribution_type WHERE contact_id = @contactId AND name = 'General') );\n";
          
          //$general_price_fieldId = "SELECT @gpfID := id FROM civicrm_price_field WHERE contribution_type_id = @general_id;\n";
          $setGPFVNULL = "SET @gpfvID := '';\n";
          $general_pfvId = "SELECT @gpfvID := id FROM civicrm_price_field_value WHERE price_field_id = @gpfID;\n";

          $insert_general_price_field_value = "INSERT INTO civicrm_price_field_value ( id, price_field_id, name, label, amount, weight, is_active ) VALUES ( @gpfvID, @gpfID, 'General', 'General', 1, 1, 1 ) ON DUPLICATE KEY UPDATE id = @gpfvID, price_field_id = @gpfID;\n";
          
          $setMSNULL = "SET @ms_id := '';\n";
          $ms_id = "SELECT @ms_id := id FROM civicrm_contribution_type WHERE name = 'M&S' AND contact_id = @contactId AND parent_id = @parentId;\n";
          
          $ms_contribution_type = "INSERT INTO civicrm_contribution_type ( id, name, accounting_code, description, is_deductible, is_active, contact_id, parent_id ) values ( @ms_id, 'M&S',55, '{$organization_name}', 1, 1,@contactId, @parentId ) ON DUPLICATE KEY UPDATE id = @ms_id, description = '{$organization_name}', contact_id = @contactId , parent_id = @parentId ;\n";

          //$ms_id = "SELECT @ms_id := id FROM civicrm_contribution_type WHERE name = 'M&S' AND contact_id = @contactId AND parent_id = @parentId;\n";
          
          $setMSPFNULL = "SET @mpfID := '';\n";
          $ms_price_fieldId = "SELECT @mpfID := id FROM civicrm_price_field WHERE contribution_type_id = @ms_id;\n";
          
          $insert_ms_price_field = "INSERT INTO civicrm_price_field ( id, price_set_id, name, label, html_type, is_enter_qty, weight, is_display_amounts, options_per_line, is_active, is_required, visibility_id, contribution_type_id ) VALUES ( @mpfID, @setId, CONCAT('M&S_', (SELECT description FROM civicrm_contribution_type WHERE contact_id = @contactId AND name = 'M&S') ), 'M&S', 'Text', 1, 2, 0, 1, 1, 0, 1, @ms_id ) ON DUPLICATE KEY UPDATE id = @mpfID, contribution_type_id = @ms_id, price_set_id = @setId, name =  CONCAT('M&S_', (SELECT description FROM civicrm_contribution_type WHERE contact_id = @contactId AND name = 'M&S') );\n";
                
          //$ms_price_fieldId = "SELECT @mpfID := id FROM civicrm_price_field WHERE contribution_type_id = @ms_id;\n";
          $setMSPFVNULL = "SET @mpfvID := '';\n";
          $ms_pfvId = "SELECT @mpfvID := id FROM civicrm_price_field_value WHERE price_field_id = @mpfID;\n";
          
          $insert_ms_price_field_value = "INSERT INTO civicrm_price_field_value ( id, price_field_id, name, label, amount, weight, is_active ) VALUES ( @mpfvID, @mpfID, 'M&S', 'M&S', 1, 2, 1 ) ON DUPLICATE KEY UPDATE id = @mpfvID, price_field_id = @mpfID;\n";
          
          $setONULL = "SET @other_id := '';\n";
          $other_id = "SELECT @other_id := id FROM civicrm_contribution_type WHERE name = 'Other' AND contact_id = @contactId AND parent_id = @parentId;\n";
          
          $other_contribution_type = "INSERT INTO civicrm_contribution_type ( id, name, accounting_code, description, is_deductible, is_active, contact_id, parent_id ) values ( @other_id, 'Other',55, '{$organization_name}', 1, 1, @contactId, @parentId ) ON DUPLICATE KEY UPDATE id = @other_id, description = '{$organization_name}', contact_id = @contactId , parent_id = @parentId;\n";

          //$other_id = "SELECT @other_id := id FROM civicrm_contribution_type WHERE name = 'Other' AND contact_id = @contactId AND parent_id = @parentId;\n";
          $setOPFINULL = "SET @opfID := '';\n";
          $other_price_fieldId = "SELECT @opfID := id FROM civicrm_price_field WHERE contribution_type_id = @other_id;\n";

          $insert_other_price_field = "INSERT INTO civicrm_price_field ( id, price_set_id, name, label, html_type, is_enter_qty, weight, is_display_amounts, options_per_line, is_active, is_required, visibility_id, contribution_type_id ) VALUES( @opfID, @setId, CONCAT('Other_', (SELECT description FROM civicrm_contribution_type WHERE contact_id = @contactId AND name = 'Other') ), 'Other', 'Text', 1, 3, 0, 1, 1, 0, 1, @other_id ) ON DUPLICATE KEY UPDATE id = @opfID, contribution_type_id =@other_id, price_set_id = @setId, name = CONCAT('Other_', (SELECT description FROM civicrm_contribution_type WHERE contact_id = @contactId AND name = 'Other') ) ;\n";
          
          //$other_price_fieldId = "SELECT @opfID := id FROM civicrm_price_field WHERE contribution_type_id = @other_id;\n";
          
          $setOPFVNULL = "SET @opfvID := '';\n";
          $other_pfvId = "SELECT @opfvID := id FROM civicrm_price_field_value WHERE price_field_id = @opfID;\n";
          
          $insert_other_price_field_value = "INSERT INTO civicrm_price_field_value ( id, price_field_id, name, label, amount, weight, is_active ) VALUES( @opfvID, @opfID, 'Other', 'Other', 1, 3, 1 ) ON DUPLICATE KEY UPDATE id = @opfvID, price_field_id = @opfID;\n";
           
            
          $insert_city = null;
          if(!empty($street_address) || !empty($city) || !empty($province) || !empty($postal_code) ) {     
            $insert_city  = "SET @addressId := '';\n
SELECT @addressId := id FROM civicrm_address WHERE contact_id = @contactId AND location_type_id = 2 AND is_primary = 1;\n
INSERT INTO civicrm_address (id, contact_id, location_type_id, is_primary, street_address, city, postal_code, state_province_id, country_id  ) values (@addressId, @contactId, 2, 1, '{$street_address}','{$city}','{$postal_code}',(SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$province}' AND country_id = 1039 ), 1039 ) ON DUPLICATE KEY UPDATE contact_id = @contactId, location_type_id = 2, street_address = '{$street_address}', city = '{$city}', postal_code = '{$postal_code}', state_province_id = (SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$province}' AND country_id = 1039 );\n" ;
          } $street_address = $city = $province = $postal_code = null;

          $insert_email = null;
          if(!empty($email)) {     
            $insert_email = "SET @emailId := '';\n
SELECT @emailId := id FROM civicrm_email WHERE contact_id = @contactId AND is_primary = 1;\n
INSERT INTO civicrm_email (id, contact_id, location_type_id, email, is_primary ) values (@emailId, @contactId, 2, '{$email}',1 ) ON DUPLICATE KEY UPDATE contact_id = @contactId, email = '{$email}', location_type_id = 2;\n" ;
          } $email = null;
            
          $insert_phone = null;
          if(!empty($phone) ) {     
            $insert_phone  = "SET @phoneId := '';\n
SELECT @phoneId := id FROM civicrm_phone WHERE contact_id = @contactId AND is_primary = 1;\n
INSERT INTO civicrm_phone (id, contact_id, location_type_id, is_primary, phone, phone_type_id) values (@phoneId, @contactId, 1, 1, '{$phone}', 1) ON DUPLICATE KEY UPDATE contact_id = @contactId, phone = '{$phone}', location_type_id = 1;\n" ;
          } $phone = null;
            
          $insert_fax = null;
          if(!empty($fax) ) {     
            $insert_fax  = "SET @faxId := '';\n
SELECT @faxId := id FROM civicrm_phone WHERE contact_id = @contactId AND location_type_id = 1 AND phone_type_id = 3;\n
INSERT INTO civicrm_phone (id, contact_id, location_type_id, is_primary, phone, phone_type_id) values (@faxId, @contactId, 1, 0, '{$fax}', 3) ON DUPLICATE KEY UPDATE contact_id = @contactId, phone = '{$fax}';\n" ;
          } $fax = null;
            
          $insert_ms_number = null;
          if(!empty($ms_number)) {      
            $insert_ms_number = "INSERT INTO civicrm_value_other_details_7 ( entity_id , ms_number_16) values (@contactId, '{$ms_number}' ) ON DUPLICATE KEY UPDATE entity_id = @contactId, ms_number_16 = '{$ms_number}';\n";
          }

          $insert_all_rows = $insert_org.$setContactNULL.$contact_id.$setParentNULL.$parent_id.$parent_contribution_type.$parent_id.$setGenNULL.$general_id.$general_contribution_type.$general_id.$setSetNULL.$price_setId.$insert_price_set.$price_setId.$setGPFNULL.$general_price_fieldId.$insert_general_price_field.$general_price_fieldId.$setGPFVNULL.$general_pfvId.$insert_general_price_field_value.$setMSNULL.$ms_id.$ms_contribution_type.$ms_id.$setMSPFNULL.$ms_price_fieldId.$insert_ms_price_field.$ms_price_fieldId.$setMSPFVNULL.$ms_pfvId.$insert_ms_price_field_value.$setONULL.$other_id.$other_contribution_type.$other_id.$setOPFINULL.$other_price_fieldId.$insert_other_price_field.$other_price_fieldId.$setOPFVNULL.$other_pfvId.$insert_other_price_field_value.$insert_city.$insert_email.$insert_phone.$insert_fax.$insert_ms_number; 
          fwrite($newRecordsToInsert,$insert_all_rows);
          $count++;
        } else {
        if(is_array($header)) {
          $generateCSV =  fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedOrg, 'w' );
          fputcsv( $generateCSV, $header );
          $header = null;
        } else {
          $error = array();
          $error[] = 'Invalid value for field(s) : unit_id';
          $rows = array_merge($error, $rows);
          fputcsv( $generateCSV, $rows );
        }
      }
    }
    if ($count) {
      $sql = "UPDATE civicrm_contact cc
 LEFT JOIN civicrm_relationship cr ON cr.contact_id_a = cc.id
LEFT JOIN civicrm_relationship cr1 ON cr1.contact_id_b = cc.id
SET 
cc.is_deleted = 1,
cc.modified_date = now(),
cr.is_active = 0,
cr1.is_active = 0,
cr.end_date = now(),
cr1.end_date = now()
WHERE cc.contact_type = 'Organization' 
AND modified_date < CURDATE();\n";
      fwrite($newRecordsToInsert, $sql);
    }
    self::logs("no of records = ".$count);
    self::logs("no of invalid records = ".$others);
    
    fclose($read);
    fclose($newRecordsToInsert);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importOrganization.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Organisation failed in function : importOrganisation()');
    }
  }

  function importOrgRelationship() {
    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->organizationFile, 'r' );
    $newRecordsToInsert  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/importOrgRelationship.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $count = $others = 0;
    static $id_no ='';
    
    while ( $rows = fgetcsv( $read) ) {
      if ( !empty($rows[0]) ) {
        $ext_id = 'O-'.$rows[0];
      } else {
        $ext_id =  null;
      }
      if ( !empty($rows[11]) ) {
        $parent_id = 'O-'.$rows[11];
      } else {
        $parent_id =  null;
      }
      
      $con = mysql_connect( $this->localhost, "{$this->userName}", "{$this->pass}" );
      if (!$con) {
        die('Could not connect: ' . mysql_error());
      }
      mysql_select_db( "$this->dbName", $con);
      $id = $contact_id = null;
      $query = "SELECT MAX(id) as cid FROM civicrm_contact where external_identifier ='{$parent_id}'";
      $result = mysql_query($query);
      while ( $info = mysql_fetch_assoc($result) ) {
        $id = $info['cid'];
      }
      $insert_donor_rel = $setContNULL = $setRelNULL = null;
      
      if ( !empty($id) ) {
        $setContNULL = "SET @contactId := '';\n"; 
        $contact_id = "SELECT @contactId := id FROM civicrm_contact where external_identifier ='{$ext_id}';\n";
        $setRelNULL = "SET @relID := '';\n"; 
        $relID = "SELECT @relID := id FROM civicrm_relationship WHERE contact_id_a = @contactId AND contact_id_b = '{$id}' AND relationship_type_id = ".IS_PART_OF_RELATION_TYPE_ID." AND is_active = 1;\n";
              
        $insert_org_rel = "INSERT INTO civicrm_relationship ( id, contact_id_a, contact_id_b, relationship_type_id, is_active, start_date) values ( @relID,@contactId, '{$id}', ".IS_PART_OF_RELATION_TYPE_ID.", 1, now()) ON DUPLICATE KEY UPDATE id = @relID, contact_id_a = @contactId, contact_id_b = '{$id}' ;\n";
      }
      $insert_all_rows = $setContNULL.$contact_id.$setRelNULL.$relID.$insert_org_rel;
      fwrite($newRecordsToInsert,$insert_all_rows);
    } 
    
    fclose($read);
    fclose($newRecordsToInsert);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importOrgRelationship.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Organization Relationship failed in function : importOrgRelationship()');
    }
  }
  

  function  dedupeAdmin() {
    $read1      = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->localAdminFile, 'r' );
    $write     = fopen($this->par2parOnlinePath.$this->newDirectory.'/adminimport.csv', 'w' );
    $notwrite  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedAdmin, 'w' );

    ini_set('memory_limit', '2048M');
    $rowshead = fgetcsv( $read1,'300000');
    //$head = array('par_admin_id','par_admin_name','par_admin_login', 'par_admin_email','par_admin_street', 'par_admin_city', 'par_admin_province', 'par_admin_postal_code', 'par_admin_tel_num', 'par_admin_fax_num','unit_id');
    fputcsv($write, $rowshead, ',');  
    fputcsv($notwrite, $rowshead, ','); 
    while ( $rows = fgetcsv( $read1,'1500000') ) {
      $read2 = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->localAdminFile, 'r' );
      $rows1 = fgetcsv( $read2); 
      $flag =0;
   
      while ( $rows1 = fgetcsv( $read2 ,'1500000') ) {
        if( $rows['0'] == $rows1['0']) {
          $flag++;    
        }
      }
       
      if( $flag>1){
        fputcsv($notwrite, $rows, ','); 
      }
      if($flag == 1 ) { 
        fputcsv($write, $rows, ','); 
      }
    }    
    
    fclose($read1);
    fclose($write);
    fclose($notwrite);
  }

  function importAdmin() {

    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->localAdminFile, 'r' );
    $newRecordsToInsert  = fopen($this->par2parOnlinePath.$this->newDirectory.'/importAdmin.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $header = array_merge($this->error,$rows);
    $count = $others = 0;
    static $id_no ='';
    while ( $rows = fgetcsv( $read) ) { 
     
      $ext_id = $first_name = $middle_name= $last_name = null;
      if ( !empty( $rows[0] ) ) {
        $ext_id =  'A-'.$rows[0];
      }else {
        $ext_id = null;
      }
      if ( !empty( $rows[1] ) ) {
        $admin_name  = addslashes( $rows[1] );
        $name = explode( ' ' , $admin_name );
      } else {
        $admin_name = "NULL";
      } 
      if(!empty($name[2] )){
        $first_name = $name[0];   
        $middle_name = $name[1];
        $last_name = $name[2];
      }elseif(!empty($name[1] )){
        $first_name = $name[0]; 
        $last_name = $name[1]; 
      } 
      if  ( !empty($rows[3]) ) {
        $email = addslashes( $rows[3] );
      } else {
        $email = null;
      }  
      if  ( !empty($rows[4]) ) {
        $street_address = addslashes( $rows[4] ); 
      } else {
        $street_address = null;
      }  
      if  ( !empty($rows[5]) ) {
        $city = addslashes( $rows[5] );
      } else {
        $city = null;
      } 
      if  ( !empty($rows[6]) ) {
        $province = addslashes( $rows[6] );
      } else {
        $province = null;
      }
      if  ( !empty($rows[7]) ) {
        $postal_code = addslashes( $rows[7] );
      } else {
        $postal_code = null;
      }
      if  ( !empty($rows[8]) ) {
        $phone = $rows[8];
      } else {
        $phone = null;
      }  
      if  ( !empty($rows[9]) ) {
        $fax = $rows[9];
      } else {
        $fax = null;
      }    
      if  ( !empty($rows[10]) ) {
        $orgext = 'O-'.$rows[10];
      } else {
        $orgext = null;
      }
      if ( !empty( $last_name ) && !empty( $first_name ) ) {
        $display_name =  $first_name.' '.$last_name;
        $sort_name = $last_name.', '.$first_name;
      } else if ( !empty( $last_name ) && empty( $first_name ) ) {
        $display_name =  $last_name;
        $sort_name =   $last_name;
      } else if ( empty( $last_name ) && !empty( $first_name ) ) {
        $display_name =  $first_name;
        $sort_name =  $first_name;
      } else {
        $display_name = null;
        $sort_name = null;
      }
      if(!empty( $ext_id ) && !empty($first_name) && !empty($last_name) )
        { 
          $insert_all_rows =''; 
          $insert_admin = $setContNULL = $setGrpNULL = $setAddNULL = $setEmailNULL = $setPhoneNULL = $setFaxNULL = $contact_id = $groupId = $relID = $addressId = $emailId = $phoneId = $faxId = $delete = $individual_contact_grp = $insert_donor_rel = null;
          if ( !empty( $ext_id )  ) {
            $insert_admin = "INSERT INTO civicrm_contact ( external_identifier, contact_type, sort_name, first_name, last_name, display_name  ) values ('{$ext_id}','Individual', '{$sort_name}', '{$first_name}', '{$last_name}', '{$display_name}') ON DUPLICATE KEY UPDATE external_identifier = '{$ext_id}', contact_type = 'Individual', sort_name = '{$sort_name}', first_name = '{$first_name}', last_name = '{$last_name}', display_name = '{$display_name}', modified_date = now(), is_deleted = 0;\n";
            $individual_contact_grp = null;
            $setContNULL = "SET @contactId := '';\n"; 
            $contact_id = "SELECT @contactId := id FROM civicrm_contact where external_identifier ='{$ext_id}';\n";

            $insert_city = null;
            if(!empty($street_address) || !empty($city) || !empty($province) || !empty($postal_code) ) { 
              $setAddNULL = "SET @addressId := '';\n";
              $addressId = "SELECT @addressId := id FROM civicrm_address WHERE contact_id = @contactId AND location_type_id = 1 AND is_primary = 1;\n";
              
              $insert_city  = "INSERT INTO civicrm_address (id , contact_id, location_type_id, is_primary, is_billing, street_address, city, postal_code, state_province_id, country_id  ) values (@addressId, @contactId, 1, 1, 1, '{$street_address}','{$city}','{$postal_code}',(SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$province}' AND country_id = 1039 ), 1039 ) ON DUPLICATE KEY UPDATE id = @addressId, contact_id = @contactId, street_address = '{$street_address}', city = '{$city}', postal_code = '{$postal_code}', state_province_id = (SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$province}' AND country_id = 1039 );\n" ;
                
            } $street_address = $city = $province = $postal_code = null;

            $insert_email = null;
            if(!empty($email)) {
              $setEmailNULL = "SET @emailId := '';\n";
              $emailId = "SELECT @emailId := id FROM civicrm_email WHERE contact_id = @contactId AND email = '{$email}' AND location_type_id = 1 AND is_primary = 1;\n";
              
              $insert_email = "INSERT INTO civicrm_email ( id, contact_id, location_type_id, email, is_primary, is_billing ) values (@emailId, @contactId, 1, '{$email}', 1, 1 ) ON DUPLICATE KEY UPDATE id = @emailId, contact_id = @contactId, email = '{$email}';\n" ;
                  
            } $email = null;
                
            $insert_phone = null;
            if(!empty($phone) ) {
              $setPhoneNULL = "SET @phoneId := '';\n";
              $phoneId = "SELECT @phoneId := id FROM civicrm_phone WHERE contact_id = @contactId AND phone = '{$phone}' AND is_primary = 1 AND location_type_id = 1 AND phone_type_id = 1;\n";
              
              $insert_phone  = "INSERT INTO civicrm_phone (id, contact_id, location_type_id, is_primary, is_billing, phone, phone_type_id) values (@phoneId, @contactId, 1, 1, 1, '{$phone}', 1) ON DUPLICATE KEY UPDATE id = @phoneId, contact_id = @contactId, phone = '{$phone}';\n" ;
                
            } $phone = null;
            $insert_fax = null;
            if(!empty($fax) ) {
              $setFaxNULL = "SET @faxId := '';\n";
              $faxId = "SELECT @faxId := id FROM civicrm_phone WHERE contact_id = @contactId AND phone = '{$fax}' AND location_type_id = 1 AND phone_type_id = 3;\n";
              
              $insert_fax  = "INSERT INTO civicrm_phone (id, contact_id, location_type_id, is_primary, is_billing, phone, phone_type_id) values (@faxId, @contactId, 1, 0, 0, '{$fax}', 3) ON DUPLICATE KEY UPDATE id = @faxId, contact_id = @contactId, phone = '{$fax}';\n" ;
                 
            } $fax = null;

            $insert_all_rows = $insert_admin.$setContNULL.$contact_id.$setAddNULL.$addressId.$insert_city.$setEmailNULL.$emailId.$insert_email.$setPhoneNULL.$phoneId.$insert_phone.$setFaxNULL.$faxId.$insert_fax;

            fwrite($newRecordsToInsert,$insert_all_rows); 
            $count++;
          }
        } else {
        $others++;
        if(is_array($header)) {
          $generateCSV =  fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedAdmin, 'w' );
          fputcsv( $generateCSV, $header );
          $header = null;
        } else {
	      $error = array();
          $error[] = "Invalid value for field(s): par_admin_id or par_admin_name (contact not found)";
          $rows = array_merge($error, $rows);
          fputcsv( $generateCSV, $rows );
        }
      }
    }
    
    if ($count) {
      $config = CRM_Core_Config::singleton();
      $drupalDb = DB::parseDSN($config->userFrameworkDSN);
      $query = "UPDATE civicrm_contact cc
LEFT JOIN civicrm_relationship cr ON cr.contact_id_a = cc.id AND cr.relationship_type_id  IN (11, 12) AND cr.is_active = 1
LEFT JOIN civicrm_uf_match cum ON cum.contact_id = cc.id
LEFT JOIN {$drupalDb['database']}.users u ON u.uid = cum.uf_id
SET cr.is_active = 0,
cc.is_deleted = 1,
modified_date = now(),
cr.end_date = now(),
u.status = 0
WHERE cc.contact_type = 'Individual' AND cc.external_identifier LIKE 'A-%'
AND modified_date < CURDATE();\n";
      fwrite($newRecordsToInsert, $query); 
    }
    
    self::logs("no of records = ".$count);
    self::logs("no of invalid records = ".$others);
    fclose($read);
    fclose($newRecordsToInsert);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importAdmin.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Admin failed in function : importAdmin()');
    }
  }

  function importAdminRelationship() {
    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->localAdminFile, 'r' );
    $newRecordsToInsert  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/importAdminRelationship.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $count = $others = 0;
    static $id_no ='';
    
    while ( $rows = fgetcsv( $read) ) {
      if ( !empty($rows[0]) ) {
        $parent_id = 'A-'.$rows[0];
      } else {
        $parent_id =  null;
      }
      if ( !empty($rows[10]) ) {
        $ext_id = 'O-'.$rows[10];
      } else {
        $ext_id =  null;
      }
      
      $con = mysql_connect( $this->localhost, "{$this->userName}", "{$this->pass}" );
      if (!$con) {
        die('Could not connect: ' . mysql_error());
      }
      mysql_select_db( "$this->dbName", $con);
      $id = $contact_id = null;
      $query = "SELECT MAX(id) as id, contact_sub_type FROM civicrm_contact where external_identifier ='{$ext_id}'";
      $result = mysql_query($query);
      
      while ( $info = mysql_fetch_assoc($result) ) {
        $id = $info['id'];
        $contactType = $info['contact_sub_type'];
      }
      $relationshipType = PAR_ADMIN_RELATION_TYPE_ID;
      $groupID = PAR_ADMIN_GROUP_ID;
      if ( $contactType == 'Denomination' ) {
        $relationshipType = DENOMINATION_ADMIN_RELATION_TYPE_ID;
        $groupID = DENOMINATION_ADMIN_GROUP_ID;
      }
      
      $insert_donor_rel = $setContNULL = $setRelNULL = $setGrpNULL = $groupId = $individual_contact_grp = null;
      
      if ( !empty($id) && !empty($parent_id)) {
        $setContNULL = "SET @contactId := '';\n";
        $contact_id = "SELECT @contactId := id FROM civicrm_contact where external_identifier ='{$parent_id}';\n";
        $setRelNULL = "SET @relID := '';\n";
        $relID = "SELECT @relID := id FROM civicrm_relationship WHERE contact_id_a = @contactId AND contact_id_b = '{$id}' AND relationship_type_id = {$relationshipType} AND is_active = 1;\n";
              
        $insert_admin_rel = "INSERT INTO civicrm_relationship ( id, contact_id_a, contact_id_b, relationship_type_id, is_active, start_date) values ( @relID,@contactId, '{$id}',{$relationshipType} , 1, now()) ON DUPLICATE KEY UPDATE id = @relID, contact_id_a = @contactId, contact_id_b = '{$id}' ;\n";
        $setGrpNULL = "SET @grpID := '';\n";
        $groupId = "SELECT @grpID := id FROM civicrm_group_contact WHERE group_id = {$groupID} AND contact_id = @contactId;\n";
        
        $individual_contact_grp = "INSERT INTO civicrm_group_contact ( id, group_id, contact_id, status ) values ( @grpID, {$groupID}, @contactId, 'Added' ) ON DUPLICATE KEY UPDATE id = @grpID, contact_id = @contactId;\n";
      }
      $insert_all_rows = $setContNULL.$contact_id.$setRelNULL.$relID.$insert_admin_rel.$setGrpNULL.$groupId.$individual_contact_grp;
      fwrite($newRecordsToInsert,$insert_all_rows);
    } 
    
    fclose($read);
    fclose($newRecordsToInsert);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importAdminRelationship.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Admin Relationship failed in function importAdminRelationship()');
    }
  }

  function importDonor() {
    require_once('CRM/Contribute/PseudoConstant.php');
    $allInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->donorFile, 'r' );
    $newRecordsToInsert  = fopen($this->par2parOnlinePath.$this->newDirectory.'/importDonor.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $header = array_merge( $this->error,$rows );
    $count = $others = 0;
    static $id_no ='';
    while ( $rows = fgetcsv( $read ) ) {
      $street_address = $city = $province = $postal_code = $email = $phone = $pardonorName = null;
      $extrnal_id = $and = $idact = $ext_id = null;
      if ( !empty( $rows[0] ) ) {
        $ext_id =  'D-'.$rows[0];
        $extrnal_id = $rows[0];
      }
      if (!$this->isMonthlySync) {
        $idact = CRM_Core_DAO::singleValueQuery("SELECT c.id FROM civicrm_contact c INNER JOIN civicrm_value_is_online_17 o ON o.entity_id = c.id WHERE o.activated__48 = 1 AND c.external_identifier = '{$ext_id}'");
        if ($idact) {
          continue;
        }
      }

      if ( !empty( $rows[1] ) ) {
        $bank_id = $rows[1];
      }else {
        $bank_id = "NULL";
      }
      if ( !empty( $rows[2] ) ) {
        $bank_name = addslashes( $rows[2] );
      } else {
        $bank_name = "NULL";
      }
  
      if ( !empty( $rows[3] ) ) {
        $branch_id = $rows[3];
      }else {
        $branch_id  = "NULL";
      }
      if ( !empty( $rows[4] ) ) {
        $branch_name = addslashes( $rows[4] );
      } else {
        $branch_name = "NULL";
      }

      if ( !empty( $rows[5] ) ) {
        $account_no = $rows[5];
      } else {
        $account_no = "NULL";
      }
      $first_name1 = $last_name1 = $first_name2 = $last_name2 = $first = $second = $names = $othernames = $slashnames = $slashname = $slashfirstName = $firstNames = $firstName = $lastName = $diffNames = $differName = $prefixNames = null;
      $flag = 0;
      $ignore = true;
      if ( !empty( $rows[6] ) ) {
        if( $rows[23] == 1 ) {
          $donor_name  = $rows[6];
          $first_name1 = $sort_name = $display_name = $donor_name;
          $last_name1 = null;
        }  else if(strstr($rows[6],'(') == false ) {
          $donor_name  = $rows[6];
          if(strstr( $donor_name, 'DR & MRS.')) {
            $name = explode( 'DR & MRS.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              } else {
                $lastName = $names[0];
              }
            }
            $prefixNames["DR ".trim($firstName)] = $lastName;
            $prefixNames["MRS. ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'Dr. & Mrs.')) { 
            $name = explode( 'Dr. & Mrs.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["Dr. ".trim($firstName)] = $lastName;
            $prefixNames["Mrs ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'Mr & Mrs')) { 
            $name = explode( 'Mr & Mrs' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["Mr ".trim($firstName)] = $lastName;
            $prefixNames["Mrs ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'Mr. & Mrs.')) { 
            $name = explode( 'Mr. & Mrs.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["Mr. ".trim($firstName)] = $lastName;
            $prefixNames["Mrs. ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'MR & MRS.')) { 
            $name = explode( 'MR & MRS.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["MR ".trim($firstName)] = $lastName;
            $prefixNames["MRS. ".trim($firstName)] = $lastName;
          }  else if(strstr( $donor_name, 'MR & MRS')) { 
            $name = explode( 'MR & MRS' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["MR ".trim($firstName)] = $lastName;
            $prefixNames["MRS ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'MR & DR.')) { 
            $name = explode( 'MR & DR.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["MR ".trim($firstName)] = $lastName;
            $prefixNames["DR. ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'DR. & MRS.')) { 
            $name = explode( 'DR. & MRS.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["DR. ".trim($firstName)] = $lastName;
            $prefixNames["MRS. ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, 'MRS.')) { 
            $name = explode( 'MRS.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["MRS. ".trim($firstName)] = $lastName;
          }  else if(strstr( $donor_name, ' MRS')) { 
            $name = explode( ' MRS' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["MRS ".trim($firstName)] = $lastName;
          }  else if(strstr( $donor_name, ' MR')) { 
            $name = explode( ' MR' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            $fname = $fnames = array();
            if (strstr($donor_name, '/')) {
              $fnames = explode('/', $donor_name);
              foreach($fnames as $k => $val) {
                if (strstr($val, ',')) {
                  $holder = explode(',' , $val);
                  if (strstr($holder[1], 'MR')) {
                    $fname[] = 'MR '.trim(str_replace('MR', '', trim($holder[1])));
                  }
                  else {
                    $fname[] = $holder[1];
                  }
                }
                else {
                   if (strstr($val, 'MR')) {
                     $fname[] = 'MR '.str_replace('MR', '', trim($val));
                  }
                  else {
                    $fname[] = $val;
                  }
                }
              }
            }
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                if( strstr( $names[1], '/') ) {
                  $diffNames = explode (  '/' , $names[1] );
                } else {
                  $firstName = $names[1];
                }
              } if(!empty($names[1]) && !empty($name[1])) {
                $diffNames[] = $names[1];
                $differName = ltrim(trim($name[1]), '&');
              }
              $lastName = $names[0];
            } else {
              $lastName = $name[0];
            }
            if ($diffNames && empty($fname)) {
              foreach( $diffNames as $diffKey => $diffVal ) {
                $prefixNames["MR ".trim($diffVal)] = $lastName;
              }
            } 
            elseif (empty($fname)) {
              $prefixNames["MR ".trim($firstName)] = $lastName;
            }
            if($differName && empty($fname)) {
              $prefixNames[trim($differName)] = $lastName;
            }
            elseif (!empty($fname)) {
              foreach ($fname as $k => $fnam) {
                $prefixNames[trim($fnam)] = $lastName;
              }
            }
          } else if(strstr( $donor_name, 'Mrs.')) { 
            $name = explode( ' Mrs.' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                if(strstr( $names[1], '&') ) {
                  $diffNames = explode (  '&' , $names[1] );
                  $differName = $firstName = $diffNames[0];
                } else {
                  $firstName = $names[1];
                }
              } 
              
              if(!empty($names[1]) && !empty($name[1])) {
                if(strstr( $names[1], '&') ) {
                  $diffNames = explode (  '&' , $names[1] );
                  $differName = $diffNames[0];
                  $firstName = $name[1];
                } else {
                  $firstName = $names[1];
                }
              }
              $lastName = $names[0];
            }
            $prefixNames["Mrs. ".trim($firstName)] = $lastName;
            if($differName) {
              $prefixNames[trim($differName)] = $lastName;
            }
          } else if(strstr( $donor_name, 'Mr')) { 
            $name = explode( ' Mr' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              $lastName = $names[0];
              if(!empty($names[1]) && trim($names[1]) == "D.A.") {
                $lastName .= ' '.$names[1];
              }
              elseif (trim($names[1]) != "D.A.") {
                $firstName = $names[1];
              }
            }
            $prefixNames["Mr ".trim($firstName)] = $lastName;
          } else if(strstr( $donor_name, ' Dr.')) { 
            $name = explode( ' Dr.' , $donor_name );
            if(!empty($name[1])) {
              if(strstr( $name[1], '/')) {
                $firstNames = explode( ',' , $name[1]);
              } 
              elseif(strstr( $name[1], '&') ) {
                $diffNames = explode (  '&' , $name[1] );
                $differName = $diffNames[1];
                $firstName = $diffNames[0];
              }
              else {
                $firstName = $name[1];
              }
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            if($firstNames) {
              foreach( $firstNames as $firstKey => $firstVal ) {
                $prefixNames["Dr. ".trim($firstVal)] = $lastName;
              }
            } else {
              $prefixNames["Dr. ".trim($firstName)] = $lastName;
              if($differName) {
                $prefixNames[trim($differName)] = $lastName;
              }
            }
          } else if(strstr( $donor_name, ' DR.')) { 
            $name = explode( ' DR.' , $donor_name );
            if(!empty($name[1])) {
              if(strstr( $name[1], '/')) {
                $firstNames = explode('/', $name[1]);
              } else {
                $firstName = $name[1];
              }
            } 
            if (strstr($donor_name, '/')) {
              $sname = explode( '/' , $donor_name );
              foreach ($sname as $key => $val) {
                if (strstr($val , ',')) {
                  $holder = explode(',' , $val);
                  $fnames[] = $holder[1];
                }
                else {
                  $fnames[] = $val;
                }
              }
            }
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            if($firstNames && empty($fnames)) {
              foreach( $firstNames as $firstKey => $firstVal ) {
                $prefixNames["DR. ".trim($firstVal)] = $lastName;
              }
            } 
            elseif (empty($fnames))  {
              $prefixNames["DR. ".trim($firstName)] = $lastName;
            }
            elseif (!empty($fnames)) {
              foreach ($fnames as $k => $fnam) {
                $prefixNames[trim($fnam)] = $lastName;
              }
            }
          }else if(strstr( $donor_name, ' DR')) { 
            $name = explode( ' DR' , $donor_name );
            if(!empty($name[1])) {
              $firstName = $name[1];
            } 
            if (strstr( $name[0], ',')) {
              $names = explode( ',' , $name[0]);
              if(!empty($names[1]) && empty($name[1])) {
                $firstName = $names[1];
              }
              $lastName = $names[0];
            }
            $prefixNames["DR ".trim($firstName)] = $lastName;
          } else if ( strstr( $donor_name, '&') ) {
            $name = explode( '&' , $donor_name );
            if ( strstr( $name[1], ',' ) ) {
              $first  = explode (  ',' , $name[0] );
              $second = explode (  ',' , $name[1] );
            } else {
              if ( strstr( $name[0], ',' ) ) {
                $names = explode ( ',', $name[0] );
              } else {
                $names = $name;
                $name = null;
              }
            }
          } else if ( strstr( $donor_name, ' and ') ) {
            $name = explode( ' and ' , $donor_name );
            $and = 'and';
            if ( strstr( $name[1], ',' ) ) {
              $first  = explode (  ',' , $name[0] );
              $second = explode (  ',' , $name[1] );
            } else {
              if ( strstr( $name[0], ',' ) ) {
                $names = explode ( ',', $name[0] );
              } else {
                $names = $name;
              }
            }
          } else if ( strstr( $donor_name, '/') ) {
            $slashname = explode( '/' , $donor_name );
            $slashfirstName = explode( ',' , $slashname[0] );
            if ( strstr( $slashname[1], ',' ) ) {
              $slashnames = explode( ',' , $slashname[1] );
            } else if ( strstr($slashname[1], ' ') ) {
              $slashnames = explode( ' ' , trim($slashname[1]) ); 
            } 
          } else if ( strstr( $donor_name, ',') ) {
            $name = explode( ',' , $donor_name );
            if ( strstr( $name[1], ',' ) ) {
              $othernames = explode( ',' , $name[1] );
            } else if ( strstr($name[1], '/') ) {
              $othernames = explode( '/' , $name[1] );
            } else {
              $flag = 1;
            }
          } else {
            $ignore = false;
            $donor_name  = addslashes( $rows[6] );
            $first_name1 = $sort_name = $display_name = $donor_name;
            $last_name1 = null;
          }
          if ( !empty ( $prefixNames ) ) {
            $c = 1;
            foreach( $prefixNames as $preFName => $preLName ) {
              if ( $c == 1 ) {
                $first_name1 = $preFName;
                $last_name1= $preLName;
              } else {
                $first_name2 = $preFName;
                $last_name2 = $preLName;
              }
              $c++;
            }
          } else if ( !empty ( $first ) && !empty ( $second ) ) {
            if ( empty($first[1]) ) {
              $first = explode(' ',$first[0]);
              $first_name1 = $first[1];
              $last_name1 = $first[0];
              $first_name2 = $second[1];
              $last_name2 = $second[0];
            } else{
              $first_name1 = $first[1];
              $last_name1 = $first[0];
              $first_name2 = $second[1];
              $last_name2 = $second[0];
            }
         
          } else if ( !empty ( $names ) ) {
            $first_name1 = $names[1];
            $last_name1 = $names[0];
            if($name) {
              $first_name2 = $name[1];
              $last_name2 = $names[0];
            }
          } else if ( !empty ( $slashname ) ) {
            if ( !empty ( $slashnames ) ) {
              $first_name1 = $slashfirstName[1];
              $last_name1 = $slashfirstName[0];
              $first_name2 = $slashnames[1];
              $last_name2 = $slashnames[0];
            } else {
              $first_name1 = $slashfirstName[1];
              $last_name1 = $slashfirstName[0];
              $first_name2 = $slashname[1];
              $last_name2 = $slashfirstName[0];
            }
          } else if ( !empty ( $othernames ) ) {
            $first_name1 = $othernames[0];
            $last_name1 = $name[0];
            $first_name2 = $othernames[1];
            $last_name2 = $name[0];
          } else if ( $flag == 1 ) {
            if(!empty($name[2])) {
              $first_name1 = $name[1].$name[2];
            } else {
              $first_name1 = $name[1];
            }
            $last_name1 = $name[0];
            $first_name2 = null;
            $last_name2 = null;
          } else if( $rows[23] != 1 ) {
            if( $ignore ) {
              $first_name1 = null;
              $last_name1 = null;
              $first_name2 = null;
              $last_name2 = null;
            }
          }
        } else {
          $donor_name  = $rows[6];
          $first_name1 = $sort_name = $display_name = $donor_name;
          $last_name1 = null;
        }
      } else {
        $others++;
      }
      if ( !empty( $rows[8] ) ) {
        $donor_owner_id = 'O-'.$rows[8];
      } else {
        $donor_owner_id = null;
      }
    
      if ( !empty( $rows[9] ) ) {
        $donor_envelope = $rows[9];
      } else {
        $donor_envelope = null;
      }
  
      if ( !empty( $rows[11] ) ) {
        $donor_nsf = $rows[11];
      } else {
        $donor_nsf = 0;
      }

      if ( !empty( $rows[12] ) ) {
        $donor_removed = $rows[12];
      } else {
        $donor_removed = 0;
      }

      if ( !empty( $rows[13] ) ) {
        $donor_ms_amount = $rows[13];
      } else {
        $donor_ms_amount = 0;
      }
    
      if ( !empty( $rows[14] ) ) {
        $donor_cong_amount = $rows[14];
      } else {
        $donor_cong_amount = 0;
      }
    
      if ( !empty( $rows[15] ) ) {
        $donor_other_amount = $rows[15];
      } else {
        $donor_other_amount = 0;
      }
      
      $totalAmount = $donor_other_amount + $donor_cong_amount + $donor_ms_amount;
      if (in_array($bank_id, array(VISA, MASTER_CARD))) {
        $paymentInstrument = 1;
      }
      else {
        $paymentInstrument = 6;
      }
      
      $onHoldStatus = 5;
      if ($donor_removed) {
        $onHoldStatus = 7;
      }
      
      $updateRecurTable = "\n SET @recurId := '';\n SET @recurStatus := '';\n SELECT @recurId := id, @recurStatus := contribution_status_id FROM civicrm_contribution_recur WHERE contact_id = @contactId AND contribution_status_id IN(5, 7); Insert into civicrm_contribution_recur (id, contact_id, amount, currency, frequency_unit, frequency_interval, start_date, create_date, contribution_status_id, payment_instrument_id) values (@recurId, @contactId, '{$totalAmount}', 'CAD','month', '1', now(), now(), {$onHoldStatus}, {$paymentInstrument}) ON DUPLICATE KEY UPDATE amount = '{$totalAmount}', contribution_status_id = '{$onHoldStatus}'; SELECT @recurId := id FROM civicrm_contribution_recur WHERE contact_id = @contactId AND contribution_status_id = 5;\n";
      
      $updateRecurTable .= " Insert into civicrm_line_item (entity_table, entity_id, price_field_id, label, qty, unit_price, line_total, price_field_value_id)
Select  'civicrm_contribution_recur', @recurId, cpf.id, cct.name,  CAST(
CASE WHEN cct.name LIKE 'General' THEN '{$donor_cong_amount}'
WHEN cct.name LIKE 'M&S' THEN '{$donor_ms_amount}'
WHEN cct.name LIKE 'Other' THEN '{$donor_other_amount}'
END as unsigned) as qty, '1.00', 
CASE WHEN cct.name LIKE 'General' THEN '{$donor_cong_amount}'
WHEN cct.name LIKE 'M&S' THEN '{$donor_ms_amount}'
WHEN cct.name LIKE 'Other' THEN '{$donor_other_amount}'
END as amount,
 cpfv.id FROM civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id LEFT JOIN civicrm_price_field_value as cpfv ON cpf.id = cpfv.price_field_id  LEFT JOIN civicrm_contact cc ON cc.id = cct.contact_id
WHERE cc.external_identifier = '{$donor_owner_id}' AND cct.parent_id IS NOT NULL
AND 
CASE WHEN @recurStatus = 7
   THEN 0
 ELSE 1
END
ON DUPLICATE KEY UPDATE line_total = CASE WHEN cct.name LIKE 'General' THEN '{$donor_cong_amount}'
WHEN cct.name LIKE 'M&S' THEN '{$donor_ms_amount}'
WHEN cct.name LIKE 'Other' THEN '{$donor_other_amount}'
END;\n";

      $dao = CRM_Core_DAO::executeQuery("SELECT primary_contact_id, par_donor_bank_id, par_donor_branch_id, par_donor_account, `m&s_amount` msamount, general_amount, other_amount, amount,
payment_instrument_id, contribution_status_id status
FROM civicrm_log_par_donor clpd
INNER JOIN civicrm_contribution_recur cc ON cc.contact_id = clpd.primary_contact_id
WHERE clpd.primary_contact_id = @contactId AND ( 
payment_instrument_id <> '{$paymentInstrument}'
|| par_donor_bank_id <> '{$bank_id}'
|| par_donor_branch_id <> '{$branch_id}'
|| par_donor_account <> '{$account_no}'
|| `m&s_amount` <> '{$donor_ms_amount}'
|| general_amount <> '{$donor_cong_amount}'
|| other_amount <> '{$donor_other_amount}'
|| contribution_status_id NOT IN (5, 7)
|| nsf <> '{$donor_nsf}'
)
ORDER BY cc.id DESC LIMIT 1;");
      if ($dao->fetch()) {
        $updateRecurTable .= "INSERT INTO civicrm_value_change_log_18 (entity_id, file_number_52, modified_by_49, modified_date_50, change_log_data_51) 
VALUES (@contactId, NULL, 1, now(), '" . serialize(
array(
  'Status' => 'In Progress',
  'Payment Instrument' => $allInstruments[$dao->payment_instrument_id],
  'Bank #' => $dao->par_donor_bank_id,
  'Branch #' => $dao->par_donor_branch_id,
  'Account #' => $dao->par_donor_account,
  'General' => CRM_Utils_Money::format($dao->general_amount, NULL),
  'M&S' => CRM_Utils_Money::format($dao->msamount, NULL),
  'Other' => CRM_Utils_Money::format($dao->other_amount, NULL),
  'Total' => CRM_Utils_Money::format($dao->total_amount, NULL),
  'NSF' => $dao->nsf,
)) . "');\n";
      }
      if ( !empty( $rows[18] ) ) {
        $donor_ms_no = $rows[18];
      } else {
        $donor_ms_no = null;
      }
      if  ( !empty($rows[23]) ) {
        $street_address = addslashes( $rows[23] );
        $houshold_street_address = addslashes( $rows[23] );
      } else {
        $street_address = null;
        $houshold_street_address = null;
      }
      if  ( !empty($rows[24]) ) {
        $city = addslashes( $rows[24] );
        $houshold_city = addslashes( $rows[24] );
      } else {
        $city = null;
        $houshold_city = null;
      }
      if  ( !empty($rows[25]) ) {
        $province = addslashes( $rows[25] );
        $houshold_province = addslashes( $rows[25] );
      } else {
        $province = null;
        $houshold_province = null;
      }
      if  ( !empty($rows[26]) ) {
        $postal_code = addslashes( $rows[26] );
        $houshold_postal_code = addslashes( $rows[26] );
      } else {
        $postal_code = null;
        $houshold_postal_code = null;
      }
      if  ( !empty($rows[27]) ) {
        $email = addslashes( $rows[27] );
        $houshold_email = addslashes( $rows[27] );
      } else {
        $email = null;
        $houshold_email = null;
      }
      if  ( !empty($rows[28]) ) {
        $phone = $rows[28];
        $houshold_phone = $rows[28];
      } else {
        $phone = null;
        $houshold_phone = null;
      }

      $email = null;
      if ( !empty( $last_name1 ) && !empty( $first_name1 ) ) {
        $display_name =  $first_name1.' '.$last_name1;
        $sort_name = $last_name1.', '.$first_name1;
      } else if ( !empty( $last_name1 ) && empty( $first_name1 ) ) {
        $display_name =  $last_name1;
        $sort_name =   $last_name1;
      } else if ( empty( $last_name1 ) && !empty( $first_name1 ) ) {
        $display_name =  $first_name1;
        $sort_name =  $first_name1;
      } else {
        $display_name = null;
        $sort_name = null;
      }
    
      if ( !empty( $last_name2 ) && !empty( $first_name2 ) ) {
        $display_name2 =  $first_name2.' '.$last_name2;
        $sort_name2 =  $last_name2.', '.$first_name2;
      } else if ( !empty( $last_name2 ) && empty( $first_name2 ) ) {
        $display_name2 =  $last_name2;
        $sort_name2 =   $last_name2;
      } else if ( empty( $last_name2 ) && !empty( $first_name2 ) ) {
        $display_name2 =  $first_name2;
        $sort_name2 =   $first_name2;
      } else {
        $display_name2 = null;
        $sort_name2 =  null;
      }
      $orgID = NULL;
      if ($donor_owner_id) {
        $orgID = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE external_identifier = '{$donor_owner_id}' AND is_deleted = 0");
      }
      
      if(!empty($ext_id) && (!empty($first_name1) || !empty($last_name1)) && $orgID)
        {
          $insert_all_rows ='';
          
          $pardonorName = $rows[6];
          if ( !empty( $last_name2 ) ) {
            if ( trim($last_name1) == trim($last_name2) ) {
              /* if($and) { */
              /*   $pardonorName = trim($last_name1).", ".trim($first_name1)." and ".trim($first_name2); */
              /* } else { */
                $pardonorName = trim($last_name1).", ".trim($first_name1)." & ".trim($first_name2);
                //}
            } else {
              $pardonorName = trim($last_name1).", ".trim($first_name1)." & ".trim($last_name2).", ".trim($first_name2);
            }
          } else if ( empty( $last_name1 ) ) {
            $pardonorName = trim($first_name1);
          } else {
            $pardonorName = trim($last_name1).", ".trim($first_name1);
          }
          $pardonorName = addslashes($pardonorName);  
                
          $insert_donor = $setContNULL = $setGrpNULL = $setRelNULL = $setParNULL = $setAddNULL = $setEmailNULL = $setPhoneNULL = $setMSNULL = $setENNULL = $setHCNULL = $setHGNULL = $setHRNULL = $setHAddNULL = $setHEmailNULL = $setHPhoneNULL = $contact_id = $groupId = $relID = $par_accountID = $emailId = $phoneId = $msId = $envelopeId = $addressId = $delete = $individual_contact_grp = $insert_donor_rel = $insert_houshold_city = $insert_houshold_email = $insert_houshold_phone = $insertParLog = $setLOGNULL = $logId = $householdCreate = null;
          if ( !empty( $ext_id )  ) {
            $insert_all_rows ='';
            $first_name1 =  addslashes($first_name1);
            $last_name1 =  addslashes($last_name1);
            $display_name =  addslashes($display_name);
            $sort_name =  addslashes($sort_name);
            $first_name2 =  addslashes($first_name2);
            $last_name2 =  addslashes($last_name2);
            $display_name2 =  addslashes($display_name2);
            $sort_name2 =  addslashes($sort_name2);
           
            $insert_donor = "INSERT INTO civicrm_contact ( external_identifier, contact_type, sort_name, first_name, last_name, display_name ) values ('{$ext_id}','Individual', '{$sort_name}', '{$first_name1}', '{$last_name1}', '{$display_name}') ON DUPLICATE KEY UPDATE external_identifier = '{$ext_id}', contact_type = 'Individual', sort_name = '{$sort_name}', first_name = '{$first_name1}', last_name = '{$last_name1}', display_name = '{$display_name}', modified_date = now(), is_deleted = 0;\n";
            $individual_contact_grp = null;
            $setContNULL = "SET @contactId := '';\n";
            $contact_id = "SELECT @contactId := id FROM civicrm_contact where external_identifier ='{$ext_id}';\n";
            
            $setGrpNULL = "SET @grpID := '';\n";
            $groupId = "SELECT @grpID := id FROM civicrm_group_contact WHERE group_id = 3 AND contact_id = @contactId;\n";
            
            $individual_contact_grp = "INSERT INTO civicrm_group_contact ( id, group_id, contact_id, status ) values ( @grpID, 3,(SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), 'Added' ) ON DUPLICATE KEY UPDATE id = @grpID, contact_id = @contactId;\n";
                       

            $con = mysql_connect( $this->localhost, "{$this->userName}", "{$this->pass}" );
            if (!$con) {
              die('Could not connect: ' . mysql_error());
            }
            mysql_select_db( "$this->dbName", $con);
            $idb = $organization_name = null;
            $queryb = "SELECT MAX(id) as cid , organization_name FROM civicrm_contact where external_identifier ='{$donor_owner_id}'";
            $cb = mysql_query($queryb);
            while ( $infob = mysql_fetch_assoc($cb) ) {
              $idb = $infob['cid'];
              $organization_name = addslashes( $infob['organization_name'] );
            }
            $insert_donor_rel = null;
            if ( !empty($idb) ) {
              $setRelNULL = "SET @relID := '';\n";
              $relID = "SELECT @relID := id FROM civicrm_relationship WHERE contact_id_a = @contactId AND contact_id_b = '{$idb}' AND relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID." AND is_active = 1;\n";
              
              $insert_donor_rel = "INSERT INTO civicrm_relationship ( id, contact_id_a, contact_id_b, relationship_type_id, is_active, start_date) values ( @relID, @contactId, '{$idb}', ".SUPPORTER_RELATION_TYPE_ID.", 1, now()) ON DUPLICATE KEY UPDATE id = @relID, contact_id_a = @contactId, contact_id_b = '{$idb}' ;\n
UPDATE civicrm_relationship 
SET `is_active` = 0, end_date = now()
WHERE contact_id_a = @contactId AND relationship_type_id = " . SUPPORTER_RELATION_TYPE_ID . " AND contact_id_b != '{$idb}';\n";
            }
            $insertCustom = '';
            if(!empty($bank_id) || !empty($branch_id) || !empty($account_no) || !empty($bank_name)|| !empty($branch_name) ){
              $setParNULL = "SET @paID := '';\n";
              $par_accountID = "SELECT @paID := id FROM civicrm_value_par_account_details_6 WHERE entity_id = @contactId;\n";
              
              $insertCustom = "Insert into civicrm_value_par_account_details_6 (id, entity_id, bank_number_11, branch_number_12, par_account_number_13, name_of_bank_36, 	name_of_branch_37) values ( @paID, @contactId, '{$bank_id}', '{$branch_id}', '{$account_no}', '{$bank_name}', '{$branch_name}') ON DUPLICATE KEY UPDATE id = @paID, entity_id = @contactId, bank_number_11 = '{$bank_id}', branch_number_12 = '{$branch_id}', par_account_number_13 = '{$account_no}', name_of_bank_36 = '{$bank_name}', 	name_of_branch_37 = '{$branch_name}';\n";
              // $insert_all_rows = $insertCustom;
              // fwrite($newRecordsToInsert,$insert_all_rows);
              // $count = $count + 1;
            }
           
            $insert_city = null;
            
            if(!empty($street_address) || !empty($city) || !empty($province) || !empty($postal_code) ) {
              $setAddNULL = "SET @addressId := '';\n";
              $addressId = "SELECT @addressId := id FROM civicrm_address WHERE contact_id = @contactId AND location_type_id = 5 AND is_primary = 1;\n";
              
              $insert_city  = "INSERT INTO civicrm_address (id, contact_id, location_type_id, is_primary, is_billing, street_address, city, postal_code, state_province_id, country_id  ) values (@addressId, @contactId, 5, 1, 1, '{$street_address}','{$city}','{$postal_code}',(SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$province}' AND country_id = 1039 ), 1039 ) ON DUPLICATE KEY UPDATE id = @addressId, contact_id = @contactId, street_address = '{$street_address}', city = '{$city}', postal_code = '{$postal_code}', state_province_id = (SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$province}' AND country_id = 1039 );\n" ;
            } 

            $insert_email = null;
            if(!empty($email)) {
              $setEmailNULL = "SET @emailId := '';\n";
              $emailId = "SELECT @emailId := id FROM civicrm_email WHERE contact_id = @contactId AND email = '{$email}' AND location_type_id = 5 AND is_primary = 1;\n";
              
              $insert_email = "INSERT INTO civicrm_email ( id, contact_id, location_type_id, email, is_primary, is_billing ) values (@emailId,  @contactId, 5, '{$email}', 1, 1 ) ON DUPLICATE KEY UPDATE id = @emailId, contact_id = @contactId, email = '{$email}';\n" ;
            }
                
            $insert_phone = null;
            if(!empty($phone) ) {
              $setPhoneNULL = "SET @phoneId := '';\n";
              $phoneId = "SELECT @phoneId := id FROM civicrm_phone WHERE contact_id = @contactId AND phone = '{$phone}' AND is_primary = 1 AND location_type_id = 5 AND phone_type_id = 1;\n";
              
              $insert_phone  = "INSERT INTO civicrm_phone (id, contact_id, location_type_id, is_primary, is_billing, phone, phone_type_id) values (@phoneId, @contactId, 5, 1, 1, '{$phone}', 1) ON DUPLICATE KEY UPDATE id = @phoneId, contact_id = @contactId, phone = '{$phone}';\n" ;
            } 
            $insert_ms_number = null;
            if (!empty($donor_ms_no)) {
              $setMSNULL = "SET @msId := '';\n";
              $msId = "SELECT @msId := id FROM civicrm_value_other_details_7 WHERE entity_id = @contactId;\n";
            
              $insert_ms_number = "INSERT INTO civicrm_value_other_details_7 ( id, entity_id , ms_number_16) values (@msId, @contactId, {$donor_ms_no} ) ON DUPLICATE KEY UPDATE id = @msId, entity_id = @contactId, ms_number_16 = '{$donor_ms_no}';\n";
            }
            $insert_envelope = null;
            if (!empty($donor_envelope)) {
              $setENNULL = "SET @envelopeId := '';\n";
              $envelopeId = "SELECT @envelopeId := id FROM civicrm_value_envelope_13 WHERE entity_id = @contactId;\n";
            
              $insert_envelope = "INSERT INTO civicrm_value_envelope_13 ( id, entity_id , envelope_number_40) values (@envelopeId, @contactId, {$donor_envelope} ) ON DUPLICATE KEY UPDATE id = @envelopeId, entity_id = @contactId, envelope_number_40 = '{$donor_envelope}';\n";
            }
            $insertNsfCustomData = NULL;
            //$insertNsfCustomData = " INSERT INTO civicrm_value_nsf_12 (entity_id, nsf_32, removal_33) VALUES ({$contribution_id}, {$NSF}, {$removed} ) ON DUPLICATE KEY UPDATE entity_id = {$contribution_id};\n";

            $setLOGNULL = "SET @logId := '';\n";
            
            // ADD change logs
            
            $logId = "SELECT @logId := log_id FROM civicrm_log_par_donor WHERE primary_contact_id = @contactId AND external_identifier = '{$extrnal_id}';\n";
            $data = array(
              'Status' => 'In Progress',
              'Payment Instrument' => $allInstruments[$paymentInstrument],
              'Bank #' => $bank_id,
              'Branch #' => $branch_id,
              'Account #' => $account_no,
              'General' => $donor_cong_amount,
              'M&S' => $donor_ms_amount,
              'Other' => $donor_other_amount,
              'Total' => $donor_cong_amount + $donor_ms_amount + $donor_other_amount,
            );
            
            $insertParLog = "INSERT INTO civicrm_value_change_log_18 (entity_id, modified_by_49, modified_date_50, change_log_data_51)
SELECT @contactId, 1, now(), '" . serialize($data) . "' FROM civicrm_log_par_donor
WHERE external_identifier = {$extrnal_id} AND removed = {$donor_removed};";
            
            $insertParLog .= "INSERT INTO civicrm_log_par_donor ( log_time, log_id, log_contact, log_action, primary_contact_id, external_identifier, ms_number, par_donor_name, organization_name, street_address, city, postal_code, country, email, par_donor_envelope, parent_id, par_donor_bank_id, par_donor_branch_id, par_donor_account, `m&s_amount`, general_amount, other_amount, nsf, removed ) VALUES ( now(), @logId, 1, 'Update', @contactId, '{$extrnal_id}', {$donor_ms_no}, '{$pardonorName}', '{$organization_name}', '{$street_address}', '{$city}','{$postal_code}', 'CAN', '{$email}', '{$donor_envelope}', '{$idb}', {$bank_id}, {$branch_id}, '{$account_no}', {$donor_ms_amount}, {$donor_cong_amount}, {$donor_other_amount}, {$donor_nsf}, $donor_removed ) ON DUPLICATE KEY UPDATE log_id = @logId, primary_contact_id = @contactId, external_identifier = '{$extrnal_id}', ms_number = {$donor_ms_no}, par_donor_name = '{$pardonorName}', organization_name = '{$organization_name}', street_address = '{$street_address}', city = '{$city}', postal_code = '{$postal_code}', email = '{$email}', par_donor_envelope = '{$donor_envelope}', parent_id = '{$idb}', log_time = now(), par_donor_bank_id = {$bank_id}, par_donor_branch_id = {$branch_id}, par_donor_account = '{$account_no}', `m&s_amount` = {$donor_ms_amount}, general_amount = {$donor_cong_amount}, other_amount = {$donor_other_amount}, nsf = {$donor_nsf}, removed = {$donor_removed};\n";
            
          }
          
          $insert_donor_houshold = $household_id = $houseGroupId = $houseRelID = $houseAddressId = $delete_other = $household_contact_grp = $insert_donor1_rel = null;
          if ( !empty( $first_name2 ) && !empty( $last_name2 ) ) {
            $insert_donor_houshold = "INSERT INTO civicrm_contact ( external_identifier, contact_type, sort_name, first_name, last_name, display_name ) values ('{$ext_id}-1','Individual', '{$sort_name2}',  '{$first_name2}', '{$last_name2}','{$display_name2}') ON DUPLICATE KEY UPDATE external_identifier = '{$ext_id}-1', contact_type = 'Individual', sort_name = '{$sort_name2}', first_name = '{$first_name2}', last_name = '{$last_name2}', display_name = '{$display_name2}', modified_date = now(), is_deleted = 0;\n";
            $setHCNULL = "SET @householdId := '';\n";
            $household_id = "SELECT @householdId := id FROM civicrm_contact where external_identifier ='{$ext_id}-1';\n";
            $setHGNULL = "SET @houseGrpID := '';\n";
            $houseGroupId = "SELECT @houseGrpID := id FROM civicrm_group_contact WHERE group_id = 3 AND contact_id = @householdId;\n";
            if ($pardonorName) {
              $householdCreate = " INSERT INTO civicrm_contact(contact_type, sort_name, household_name, display_name, external_identifier)
VALUES ('Household', '{$pardonorName}', '{$pardonorName}', '{$pardonorName}', 'H-" . $rows[0] ."') ON DUPLICATE KEY UPDATE sort_name = '{$pardonorName}', display_name = '{$pardonorName}', household_name = '{$pardonorName}', modified_date = now(), is_deleted = 0;\n
SELECT @householdcId := id FROM civicrm_contact WHERE external_identifier = 'H-" . $rows[0] . "';\n
SELECT @hcid1 := id FROM civicrm_relationship WHERE contact_id_a = @contactId AND is_active = 1 AND relationship_type_id IN (" . HEAD_OF_HOUSEHOLD . "," . MEMBER_OF_HOUSEHOLD. ");\n
SELECT @hcid2 := id FROM civicrm_relationship WHERE contact_id_a = @householdId AND is_active = 1 AND relationship_type_id IN (" . HEAD_OF_HOUSEHOLD . "," . MEMBER_OF_HOUSEHOLD. "); 
INSERT IGNORE INTO civicrm_relationship (id, contact_id_a, contact_id_b, relationship_type_id, start_date) VALUES
(@hcid1, @contactId, @householdcId, " . HEAD_OF_HOUSEHOLD . ", now()),
(@hcid2, @householdId, @householdcId, " . MEMBER_OF_HOUSEHOLD . ", now());";
            }
            $household_contact_grp = null;
            $household_contact_grp = "INSERT INTO civicrm_group_contact ( id, group_id, contact_id, status ) values ( @houseGrpID, 3,@householdId, 'Added' ) ON DUPLICATE KEY UPDATE id = @houseGrpID, contact_id = @householdId;\n";
                
            $insert_donor1_rel = null;
            //$delete_other = "DELETE FROM civicrm_contact where external_identifier = '{$ext_id}-1';\n";
            if ( !empty($idb) ) {
              $setHRNULL = "SET @houseRelID := '';\n";
              $houseRelID = "SELECT @houseRelID := id FROM civicrm_relationship WHERE contact_id_a = @householdId AND contact_id_b = '{$idb}' AND relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID." AND is_active = 1;\n";
              
              $insert_donor1_rel = "INSERT INTO civicrm_relationship ( id, contact_id_a, contact_id_b, relationship_type_id, is_active, start_date) values (@houseRelID, @householdId,{$idb},".SUPPORTER_RELATION_TYPE_ID.",1, now()) ON DUPLICATE KEY UPDATE id = @houseRelID, contact_id_a = @householdId, contact_id_b = '{$idb}';\n
UPDATE civicrm_relationship 
SET `is_active` = 0, end_date = now()
WHERE contact_id_a = @householdId AND relationship_type_id = " . SUPPORTER_RELATION_TYPE_ID . " AND contact_id_b != '{$idb}';\n";
            }
            $insert_houshold_city = null;
            if(!empty($houshold_street_address) || !empty($houshold_city) || !empty($houshold_province) || !empty($houshold_postal_code) ) {
              $setHAddNULL = "SET @houseAddressId := '';\n";
              $houseAddressId = "SELECT @houseAddressId := id FROM civicrm_address WHERE contact_id = @householdId AND location_type_id = 5 AND is_primary = 1;\n";
            
              $insert_houshold_city  = "INSERT INTO civicrm_address (id, contact_id, location_type_id, is_primary, is_billing, street_address, city, postal_code, state_province_id, country_id  ) values (@houseAddressId, @householdId, 5, 1, 1, '{$houshold_street_address}','{$houshold_city}','{$houshold_postal_code}',(SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$houshold_province}' AND country_id = 1039 ), 1039 ) ON DUPLICATE KEY UPDATE id = @houseAddressId, contact_id = @householdId, street_address = '{$houshold_street_address}', city = '{$houshold_city}', postal_code = '{$houshold_postal_code}', state_province_id = (SELECT MAX(id) FROM civicrm_state_province where abbreviation = '{$houshold_province}' AND country_id = 1039 );\n" ;
            } $street_address = $city = $province = $postal_code = null;
                
            $insert_houshold_email = $houseEmailId = null;
            if(!empty($houshold_email)) {
              $setHEmailNULL = "SET @houseEmailId := '';\n";
              $houseEmailId = "SELECT @houseEmailId := id FROM civicrm_email WHERE contact_id = @householdId AND email = '{$email}' AND location_type_id = 5 AND is_primary = 1;\n";
              
              $insert_houshold_email = "INSERT INTO civicrm_email ( id, contact_id, location_type_id, email, is_primary, is_billing ) values (@houseEmailId, @householdId, 5, '{$houshold_email}',1, 1 )  ON DUPLICATE KEY UPDATE id = @houseEmailId, contact_id = @householdId, email = '{$houshold_email}';\n" ;
            } $houshold_email = null;
                
            $insert_houshold_phone = $housePhoneId = null;
            if(!empty($houshold_phone) ) {
              $setHPhoneNULL = "SET @housePhoneId := '';\n";
              $housePhoneId = "SELECT @housePhoneId := id FROM civicrm_phone WHERE contact_id = @householdId AND phone = '{$phone}' AND is_primary = 1 AND location_type_id = 5 AND phone_type_id = 1;\n";
              
              $insert_houshold_phone  = "INSERT INTO civicrm_phone (id, contact_id, location_type_id, is_primary, is_billing, phone, phone_type_id) values (@housePhoneId, @householdId, 5, 1, 1, '{$houshold_phone}', 1)  ON DUPLICATE KEY UPDATE id = @housePhoneId, contact_id = @householdId, phone = '{$houshold_phone}';\n" ;
            } $houshold_phone = null;
                
          }
          else {
            $householdCreate = "UPDATE civicrm_contact cc
LEFT JOIN civicrm_relationship cr ON cc.id = cr.contact_id_b
SET  cc.external_identifier = NULL,
cc.is_deleted =1,
cr.is_active = 0,
cr.end_date = NOW()
WHERE cr.is_active = 1 AND cc.contact_type LIKE 'Household' AND cr.relationship_type_id IN (" . HEAD_OF_HOUSEHOLD . "," . MEMBER_OF_HOUSEHOLD . ")
AND cc.external_identifier LIKE 'H-" . $rows[0] . "';\n";
          }
          $insert_all_rows = $insert_donor . $setContNULL . $contact_id . $setGrpNULL . $groupId . $individual_contact_grp . $setRelNULL . $relID . $insert_donor_rel . $setAddNULL . $addressId . $insert_city . $setEmailNULL . $emailId . $insert_email . $setPhoneNULL . $phoneId . $insert_phone . $setMSNULL . $msId . $insert_ms_number . $setENNULL . $envelopeId . $insert_envelope . $insert_donor_houshold . $setHCNULL . $household_id . $setHGNULL . $houseGroupId . $householdCreate . $household_contact_grp . $setHRNULL . $houseRelID . $insert_donor1_rel . $setHAddNULL . $houseAddressId . $insert_houshold_city . $setHEmailNULL . $houseEmailId . $insert_houshold_email . $setHPhoneNULL . $housePhoneId . $insert_houshold_phone . $setParNULL . $par_accountID . $insertCustom . $setLOGNULL . $logId . $insertParLog . $updateRecurTable . $insertNsfCustomData;
          
          fwrite($newRecordsToInsert,$insert_all_rows);
          $count++;
        }
      else {
        $others++;
        if(is_array($header)) {
          $generateCSV =  fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedDonor, 'w' ); 
          fputcsv( $generateCSV, $header );
          $header = null;
        } else {
          $error = array();
          $error[] = "Invalid value for field(s) : par_donor_id or par_donor_name (contact not found) or Supporter Relationship for Organization";
          $rows = array_merge($error, $rows);
          fputcsv( $generateCSV, $rows );
        }
      }
    }
    if (!empty($count)) {
      $extraClause = '';
      if (!$this->isMonthlySync) {
        $extraClause = ' AND 
          CASE 
            WHEN cvc.entity_id IS NOT NULL AND activated__48 = 1
            THEN 0
            ELSE 1   
          END ';
      }
      $deleteQuery = "\n UPDATE civicrm_log_par_donor clpd
LEFT JOIN civicrm_contact cc ON cc.id = clpd.primary_contact_id
LEFT JOIN civicrm_contribution_recur ccr ON ccr.contact_id = cc.id AND ccr.contribution_status_id IN (5, 7)
LEFT JOIN civicrm_value_is_online_17 cvc ON clpd.primary_contact_id = cvc.entity_id
SET `log_action` = 'Delete',
`log_time` = now(),
cc.is_deleted = 1,
ccr.contribution_status_id = 1,
clpd.`m&s_amount` = '0.00',
clpd.general_amount = '0.00',
clpd.other_amount = '0.00'
WHERE `log_time` < CURDATE() {$extraClause};
\n";
      $deleteQuery .= "\n UPDATE civicrm_relationship cr
INNER JOIN civicrm_contact cc ON cc.id = cr.contact_id_a AND cc.is_deleted = 1 and cr.relationship_type_id = " . HEAD_OF_HOUSEHOLD . "
LEFT JOIN civicrm_contact cc1 ON cc1.id = cr.contact_id_b
LEFT JOIN civicrm_relationship cr1 on cr1.contact_id_b = cc1.id AND cr1.relationship_type_id = " . MEMBER_OF_HOUSEHOLD . "
LEFT JOIN civicrm_contact cc2 on cc2.id = cr1.contact_id_a AND cc2.is_deleted =0
SET
  cc1.is_deleted = 1,
  cc2.is_deleted = 1,
  cr.is_active = 0,
  cr.end_date = now(),
  cr1.end_date = now(),
  cr1.is_active = 0;\n";

      fwrite($newRecordsToInsert, $deleteQuery);
    }
    self::logs("no of records = ".$count);
    self::logs("no of invalid records = ".$others);
    fclose($read);
    fclose($newRecordsToInsert);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importDonor.sql";
    $test = exec($cmd, $output, $return);

    if ($return) {
      throw new Exception('Import Donar Failed in function : importDonor()');
    }
  }

  function importCharge() {
    $read  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->accountFile, 'r' );
    $newRecordsToInsert  = fopen($this->par2parOnlinePath.$this->newDirectory.'/importCharge.sql', 'w' );
    ini_set('memory_limit', '2048M');
    $rows = fgetcsv( $read );
    $header = array_merge( $this->error,$rows );
    static $id_no ='';
    $ContTypeID = array();
    while ( $rows = fgetcsv( $read ) ) {
      if ( !empty( $rows[8] ) ) {
        $ext_id =  'O-'.$rows[8];
      }
      
      if ( !empty( $rows[1] ) ) {
        $bank_id = $rows[1];
      }else {
        $bank_id = "NULL";
      }
      //print_r($bank_id);
      if ( !empty( $rows[2] ) ) {
        $bank_name = addslashes( $rows[2] );
      } else {
        $bank_name = "NULL";
      }
   
      if ( !empty( $rows[3] ) ) {
        $branch_id = $rows[3];
      } else {
        $branch_id  = "NULL";
      }
      if ( !empty( $rows[4] ) ) {
        $branch_name = addslashes( $rows[4] );
      } else {
        $branch_name = "NULL";
      }
      if ( !empty( $rows[5] ) ) {
        $account_no = $rows[5];
      } else {
        $account_no = "NULL";
      }
      if ( !empty( $rows[6] ) ) {
        $fund_id = $rows[6];
      } else {
        $fund_id = "NULL";
      }
      $flag = 0;
      $insertCustom = "Select id from civicrm_contact where external_identifier = '{$ext_id}'; \n";
     
      $result = mysql_query($insertCustom);
      $ContTypeID[$rows[8]][$fund_id] = "NULL";$query = 'NULL';
      while( $rowdetail = mysql_fetch_array($result) ) {
        if($rowdetail[0]) {
          if( $fund_id == 1 ){
            $query = "UPDATE civicrm_contribution_type SET `bank_id` = '{$bank_id}', `branch_number` = '{$branch_id}',`account_number` = '{$account_no}',`bank_name` = '{$bank_name}',`branch_name` = '{$branch_name}' WHERE `contact_id` = {$rowdetail[0]} AND name = 'General';\n";
          } elseif ( $fund_id == 2 ){
            $query = "UPDATE civicrm_contribution_type SET `bank_id` = '{$bank_id}',`branch_number` = '{$branch_id}',`account_number` = '{$account_no}',`bank_name` = '{$bank_name}',`branch_name` = '{$branch_name}' WHERE `contact_id` = {$rowdetail[0]} AND name = 'M&S';\n";
          } elseif ( $fund_id == 3 ){
            $query = "UPDATE civicrm_contribution_type SET `bank_id` = '{$bank_id}', `branch_number` = '{$branch_id}',`account_number` = '{$account_no}',`bank_name` = '{$bank_name}',`branch_name` = '{$branch_name}' WHERE `contact_id` = {$rowdetail[0]} AND name = 'Other';\n";
          }
          fwrite($newRecordsToInsert,$query);
          $flag = 1;
        }
      } 
      if($flag == 0) {
        if(is_array($header)) {
          $notimportCSV  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedCharge, 'w' );
          fputcsv( $notimportCSV, $header );
          $header = null;
        } else {
          $error = array();
          $error[] = "Invalid value for field(s) : fund_id or par_charge_owner_id (unable to find the organization's fund)";
          $rows = array_merge($error, $rows);
          fputcsv($notimportCSV, $rows); 
        }
      }
    }
    
    fclose($read);
    fclose($newRecordsToInsert);
    fclose($notimportCSV);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importCharge.sql";
    $test = exec($cmd ,$output, $return);
    if ($return) {
      throw new Exception('Import Charge failed in function : importCharge()');
    }
  }

  function importContribution() {
    $read  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->transactionFile, 'r' );
    $write  = fopen($this->par2parOnlinePath.$this->newDirectory.'/importTransaction.sql', 'w' );

    ini_set('memory_limit', '10000M');
    $rows = fgetcsv( $read );
    $header = array_merge( $this->error,$rows );
    $count = 0; $others = 0;
    static $id_no ='';
    $todaysDay = date('d',time());
    $endDate   = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 19, date( 'Y', time() ) ) );
    $startDate = date( 'Y-m-d',strtotime("$endDate +1 day -1 month"));
    while ( $rows = fgetcsv( $read,15000) ) {
      $row = $ext_id = $ext_identifier = $ms_amount = $ContTypeID = $general_amount = $other_amount  = $amount_level = null;
  
      if(!empty($rows[4]) ) {
        $ext_id         = 'D-'.$rows[4];
        $ext_identifier = $rows[4];
        $con = mysql_connect($this->localhost,"{$this->userName}","{$this->pass}");
        if (!$con)
          {
            die('Could not connect: ' . mysql_error());
          }
        mysql_select_db("{$this->dbName}", $con);
        $query= "Select id from civicrm_contribution_type where parent_id IS NULL and contact_id = (Select MAX(contact_id_b) from civicrm_relationship where contact_id_a = (Select id from civicrm_contact where external_identifier = '{$ext_id}') and relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID." )";
       
        $result = mysql_query($query);
        while( $row = mysql_fetch_array($result) ) {   
          $ContTypeID = $row[0];
        }
        if ( $rows[5] != 0 ) {
          $amount_level[] = 'General -'.number_format($rows[5], 2);
          $general_amount = $rows[5];
        } 
        if($rows[6] != 0 ) {
          $amount_level[] = 'M&S -'.number_format($rows[6], 2);
          $ms_amount = $rows[6];
        } 
        if($rows[7] != 0 ) {
          $amount_level[] = 'Other -'.number_format($rows[7], 2);
          $other_amount = $rows[7];
        } 
        if ( !empty($amount_level ) ) {
          $amount_level = ''.implode('', $amount_level).'';
        }
     
        $total_amount   = $general_amount+ $ms_amount+ $other_amount;
        $fee_amount = $traDate = $contact_id = $contribType = $recurId = $otherPFID = $generalLI = $generalPFID = $generalPFValue = $msLI = $msPFValue = $msPFID = $otherPFValue = $contribRecurInsert = $contrId = $contrib = $setLOGNULL = $logId = $insertParLog = null;
        $lineItemGeneral = $lineItemMS = $otherLI = $lineItemOther = $generalPriceFieldID = $generalPriceFieldValue = $msPriceFieldID = $msPriceFieldValue = $otherPriceFieldID = $otherPriceFieldValue = null;
        
        if( $rows[9] !='Contributors') {
          $fee_amount   =  $total_amount * FEE_AMOUNT;
        }
        $fee_amount = number_format($fee_amount, 2);

        //$test = DateTime::createFromFormat('m/d/Y H:i:s:u',$rows[14]);
        $date = explode(':',$rows[14]);
        $dateTime = explode(' ',$date[0]);
        $dateSplit = explode('/',$dateTime[0]);
        $traDate = $dateSplit[2].'-'.$dateSplit[0].'-'.$dateSplit[1]; 
        $month = date('m', strtotime($dateTime[0]));
        $currentMonth = date('m');
        $previousMonth = date('m',strtotime('last month'));
        unset($date[3]);
        
        // for par_donor_transaction.csv the contribution status should be always completed.
        $contributionStatus = 1;
        $query = "SELECT ccr.id FROM civicrm_contribution_recur ccr 
INNER JOIN civicrm_contact cc ON cc.id = ccr.contact_id WHERE contribution_status_id = 5 and external_identifier = '{$ext_id}'";
        $recurID = CRM_Core_DAO::singleValueQuery($query);
        $date = implode(':',$date);
        $tstr           = strtotime($date);
        $start_date     = date('Y-m-d h:i:s', mktime(date('h',$tstr), date('i',$tstr), date('s',$tstr), date('m',$tstr),date('d',$tstr), date('Y',$tstr) ));
        $end_date       = date('Y-m-d h:i:s', mktime(date('h',$tstr), date('i',$tstr), date('s',$tstr), date('m',$tstr),date('d',$tstr), date('Y',$tstr) ));
     
        //$contactId   = "(Select id from civicrm_contact where external_identifier = '{$ext_id}' )";
        $contact_id = "SELECT @contactId := id FROM civicrm_contact where external_identifier ='{$ext_id}';\n";
        
        $setContrNULL = "SET @contrId := '';\n";
        $contrId = "SELECT @contrId := id FROM civicrm_contribution WHERE contact_id = @contactId AND contribution_status_id = {$contributionStatus} AND total_amount = '{$total_amount}' AND receive_date = '{$start_date}'";
        if ($recurID) {
          $contrId .= " AND contribution_recur_id = {$recurID}";          
        }
        if (in_array($bank_id, array(VISA, MASTER_CARD))) {
          $paymentInstrument = 1;
        }
        else {
          $paymentInstrument = 6;
        }
        
        $contrId .= ';\n';
        if( $rows[9] !='Contributors') {
          $contrib ="Insert into civicrm_contribution (id, contact_id, contribution_type_id, receive_date, payment_instrument_id, total_amount, fee_amount, net_amount, amount_level, contribution_recur_id, contribution_status_id) values(@contrId, @contactId, {$ContTypeID}, '{$start_date}', {$paymentInstrument}, '{$total_amount}', '{$fee_amount}', '{$total_amount}', '{$amount_level}', {$recurID}, {$contributionStatus} ) ON DUPLICATE KEY UPDATE id = @contrId;\n"; 
        } else {
          $contrib ="Insert into civicrm_contribution (id,contact_id, contribution_type_id, receive_date, payment_instrument_id, total_amount, net_amount, amount_level, contribution_recur_id, contribution_status_id) values(@contrId, @contactId, {$ContTypeID}, '{$start_date}', {$paymentInstrument}, '{$total_amount}',  '{$total_amount}', '{$amount_level}', {$recurID}, {$contributionStatus} ) ON DUPLICATE KEY UPDATE id = @contrId;\n"; 

        }
        //$setContrNULL = "SET @contrId := '';\n";
        //$contrId .= "SELECT @contrId := max(id) FROM civicrm_contribution WHERE contact_id = @contactId AND contribution_status_id = {$contributionStatus} AND total_amount = '{$total_amount}';\n";
        
        $orgId = "Select @orgId := contact_id_b from civicrm_relationship where contact_id_a = @contactId and relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID.";\n";
         
        if ( !empty($general_amount) ) {
          $generalPFID = "Select @gpfID := cpf.id from civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id where cct.name = 'General' AND cct.contact_id = @orgId;\n";
         
          $generalPFValue = "Select @gpfvID := cpfv.id from civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id LEFT JOIN civicrm_price_field_value as cpfv ON cpf.id = cpfv.price_field_id where cct.name = 'General' AND cct.contact_id = @orgId;\n";
          
          $setGeneraNULL = "SET @gliId := '';\n";
          
          $generalLI = "Select @gliId := id from civicrm_line_item where entity_id = @contrId AND price_field_id = @gpfID AND price_field_value_id = @gpfvID AND line_total = '{$general_amount}';\n";
          
          $lineItemGeneral ="INSERT into civicrm_line_item (id, entity_table, entity_id, price_field_id, label, qty, unit_price, line_total, price_field_value_id) VALUES (@gliId,'civicrm_contribution', @contrId, @gpfID, 'General', '{$general_amount}', '1.00', '{$general_amount}', @gpfvID ) ON DUPLICATE KEY UPDATE id = @gliId;\n";
        }
     
     
        if ( !empty($ms_amount) ) {

          $msPFID = "Select @mpfID := cpf.id from civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id where cct.name = 'M&S' AND cct.contact_id = @orgId;\n";
         
          $msPFValue = "Select @mpfvID := cpfv.id from civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id LEFT JOIN civicrm_price_field_value as cpfv ON cpf.id = cpfv.price_field_id where cct.name = 'M&S' AND cct.contact_id = @orgId;\n";

          $setMsNULL = "SET @mliId := '';\n";
          
          $msLI = "Select @mliId := id from civicrm_line_item where entity_id = @contrId AND price_field_id = @mpfID AND price_field_value_id = @mpfvID AND line_total = '{$ms_amount}';\n";
          $lineItemMS ="INSERT into civicrm_line_item (id, entity_table, entity_id, price_field_id, label, qty, unit_price, line_total, price_field_value_id) VALUES (@mliId, 'civicrm_contribution', @contrId, @mpfID, 'M&S', '{$ms_amount}', '1.00', '{$ms_amount}', @mpfvID )  ON DUPLICATE KEY UPDATE id = @mliId;\n";
        }
     
        if ( !empty($other_amount) ) {
         
          $otherPFID = "Select @opfID := cpf.id from civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id where cct.name = 'Other' AND cct.contact_id = @orgId;\n";
         
          $otherPFValue = "Select @opfvID := cpfv.id from civicrm_contribution_type as cct LEFT JOIN civicrm_price_field as cpf ON cpf.contribution_type_id = cct.id LEFT JOIN civicrm_price_field_value as cpfv ON cpf.id = cpfv.price_field_id where cct.name = 'Other' AND cct.contact_id = @orgId;\n";
         
          $setOtherNULL = "SET @oliId := '';\n";
          
          $otherLI = "Select @oliId := id from civicrm_line_item where entity_id = @contrId AND price_field_id = @opfID AND price_field_value_id = @opfvID AND line_total = '{$other_amount}';\n";
          $lineItemOther ="INSERT into civicrm_line_item (id, entity_table, entity_id, price_field_id, label, qty, unit_price, line_total, price_field_value_id) VALUES (@oliId, 'civicrm_contribution', @contrId, @opfID, 'Other', '{$other_amount}', '1.00', '{$other_amount}', @opfvID ) ON DUPLICATE KEY UPDATE id = @oliId ;\n";
        }
        
        $setLOGNULL = "SET @logId := '';\n";
        $logId = "SELECT @logId := log_id FROM civicrm_log_par_donor WHERE primary_contact_id = @contactId AND external_identifier = '{$ext_identifier}';\n";
                
        $insertParLog = "INSERT INTO civicrm_log_par_donor (  log_time, log_id, primary_contact_id, external_identifier, `m&s_amount`, general_amount, other_amount ) VALUES ( now(), @logId, @contactId, '{$ext_identifier}', '{$ms_amount}', '{$general_amount}', '{$other_amount}' ) ON DUPLICATE KEY UPDATE log_id = @logId, primary_contact_id = @contactId, `m&s_amount` = '{$ms_amount}', general_amount = '{$general_amount}', other_amount = '{$other_amount}', log_time = now();\n

INSERT IGNORE INTO civicrm_contribution_soft(contact_id, contribution_id, amount, currency)
SELECT contact_id_b, @contrId, '{$total_amount}', 'USD' FROM civicrm_relationship 
WHERE is_active = 1 AND relationship_type_id = " . HEAD_OF_HOUSEHOLD . " AND contact_id_a = @contactId;
\n";
    
        $insert_all_rows  = $contact_id.$setContrNULL.$contrId.$contrib.$contrId.$orgId.$generalPFID.$generalPFValue.$setGeneraNULL.$generalLI.$lineItemGeneral.$msPFID.$msPFValue.$setMsNULL.$msLI.$lineItemMS.$otherPFID.$otherPFValue.$setOtherNULL.$otherLI.$lineItemOther.$setLOGNULL.$logId.$insertParLog;
       
        if(!empty($ContTypeID) && !empty($recurID)) {
          $count++; 
          fwrite($write,$insert_all_rows); 
        } else {
          $others++;
          if(is_array($header)) {
            $notwrite  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedTransactions, 'w' );
            fputcsv( $notwrite, $header );
            $header = null;
          } else {
            $error = array();
            $error[] = "Invalid value for field(s): par_donor_id (Supporter or a fund for them is not found) or Recurring contribution not found for contact";
            $rows = array_merge($error, $rows);
            fputcsv( $notwrite,$rows );   
          }  
        } 
      }
    }
    fclose($read);
    fclose($write);
    fclose($notwrite);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/importTransaction.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Contribution failed in function : importContribution()');
    }
  }

  function addContributionCustomData() {
    $read  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->transactionFile, 'r' );
    $write  = fopen($this->par2parOnlinePath.$this->newDirectory.'/updateTransaction.sql', 'w' );

    ini_set('memory_limit', '10000M');
    $rows = fgetcsv( $read );
    $header = array_merge( $this->error,$rows );    
    $count = $others = $flag = $test = 0;
    static $id_no ='';
    $contactarr = array();
    $todaysDay = date('d',time());
    $endDate   = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 19, date( 'Y', time() ) ) );
    $startDate = date( 'Y-m-d',strtotime("$endDate +1 day -1 month"));
    
    while ( $rows = fgetcsv( $read,15000) ) {
      $row = $ext_id = $ms_amount = $ContTypeID = $general_amount = $other_amount  = $amount_level = null;
      if(!empty($rows[4]) ) {
        $ext_id         = 'D-'.$rows[4];
        $con = mysql_connect($this->localhost,"{$this->userName}","{$this->pass}");
        if (!$con)
          {
            die('Could not connect: ' . mysql_error());
          }
       
        mysql_select_db("{$this->dbName}", $con);
       
        $contactId = "Select id from civicrm_contact where external_identifier = '$ext_id'";
        $query = "Select id from civicrm_contribution_type where parent_id IS NULL and contact_id = (Select MAX(contact_id_b) from civicrm_relationship where contact_id_a = ({$contactId}) and relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID." )";
       
        $bank_number = $bankNumber =  $bank_name = $account_number = $branch = $cc_type = $bank__name = $branch_name = $branch_number = $account_number = $name_of_bank = $name_of_branch = null;
       
        $result = mysql_query($query);
        if (!$result) { // add this check.
          die('Invalid query: ' . mysql_error());
        }
        else {
          while( $row = mysql_fetch_array($result) ) {
            $ContTypeID = $row[0];
          }
        }
       
        if(!empty($rows[14]) ) {
          $date = explode(':',$rows[14]);
          unset($date[3]);
          $dateTime = explode(' ',$date[0]);
          $date1 = implode(':',$date);
          $tstr           = strtotime($date1);
          $received_date     = date('Y-m-d h:i:s', mktime(date('h',$tstr), date('i',$tstr), date('s',$tstr), date('m',$tstr),date('d',$tstr), date('Y',$tstr) ));
        }
        if(!empty($rows[13]) ) {
          $bank_number = $rows[13];
        }
        $queryBankDetails = "SELECT cd.branch_number_12, cd.par_account_number_13, cd.name_of_bank_36, cd.name_of_branch_37
FROM civicrm_value_par_account_details_6 cd
LEFT JOIN civicrm_contact c ON c.id = cd.entity_id
WHERE c.external_identifier = '{$ext_id}'
AND cd.bank_number_11 = '{$bank_number}'";
        $details = mysql_query($queryBankDetails);
        while( $detailRow = mysql_fetch_array($details)){
          $branch_number = $detailRow['branch_number_12'];
          $account_number = $detailRow['par_account_number_13'];
          $name_of_bank = addslashes( $detailRow['name_of_bank_36'] );
          $name_of_branch = addslashes( $detailRow['name_of_branch_37'] );
        }
        
        $date = explode(':',$rows[14]);
        $dateTime = explode(' ',$date[0]);
        $dateSplit = explode('/',$dateTime[0]);
        $traDate = $dateSplit[2].'-'.$dateSplit[0].'-'.$dateSplit[1]; 
        
        if (strtotime($traDate) > strtotime($startDate)) {
          $contributionStatus = 5;
        } else {
          $contributionStatus = 1;
        }
        
        $contactId   = "(Select id from civicrm_contact where external_identifier = '{$ext_id}' )";
        $contributionId = "Select id as id FROM civicrm_contribution WHERE contact_id = {$contactId} AND receive_date LIKE '{$received_date}' AND contribution_status_id = {$contributionStatus}";
        $cb = mysql_query($contributionId);
        $count = mysql_num_rows($cb);
        $contribution_id = array();
        while( $data = mysql_fetch_array($cb) ) {
          // if (in_array($data[0],$contactarr)) {
          //   continue;
          // }
          $contribution_id = $data[0];
          // if($count > 1 ){ 
          //   //$contactarr[] = $data[0];
          //   break;
          // }
        }
        if ( !empty( $rows['13'] ) ) {
          $bank_name = $rows['13'];
        }
        $NSF = $removed = 0;
        if ( !empty( $rows['9'] ) ) {
          $cc_type = $rows['9'];
          if ($cc_type != 'Contributors') {
            if($cc_type == "VISA" ) {
              $cc_type = 'Visa';
            }elseif($cc_type == "MASTER") {
              $cc_type = 'MasterCard';
            } elseif($cc_type == "NSF") {
              $NSF = 1;
              $cc_type = 'NULL';
            } elseif($cc_type == "Removed") {
              $removed = 1;
              $cc_type = 'NULL';
            }
          }else{
            $cc_type = 'NULL';
          }
        }
        $insertCustomData = null;
        if( !empty( $contribution_id )) {
          $setAccountNULL = "SET @aId := '';\n";
          $accountId = "SELECT @aId := id FROM civicrm_value_account_details_2 WHERE entity_id = {$contribution_id};\n";
          
          $insertCustomData = "INSERT INTO civicrm_value_account_details_2 (id, entity_id, bank_name_2, account_number_4, branch_5, cc_type_31, bank__name_38, branch_name_39) VALUES (@aId, {$contribution_id}, '{$bank_name}', '{$account_number}', '{$branch_number}', '{$cc_type}', '{$name_of_bank}', '{$name_of_branch}') ON DUPLICATE KEY UPDATE id = @aId, cc_type_31 = '{$cc_type}';\n";
    
          $insert_all_rows  = $setAccountNULL.$accountId.$insertCustomData;
          if( !empty($ContTypeID) ) {
            $count++;
            fwrite($write,$insert_all_rows);
          } else {
            $others++;
            if(is_array($header)) {
              $notwrite  = fopen($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notUpdatedTransactions, 'w' );
              fputcsv( $notwrite, $header );
              $header = null;
            } else {
              $error = array();
              $error[] = "Invalid value for field(s): par_donor_id (Supporter or a fund for them is not found)";
              $rows = array_merge($error, $rows);
              fputcsv( $notwrite,$rows );
            }
          }
        }
      }
    }
    
    $sql = 'UPDATE civicrm_log_par_donor clpd
INNER JOIN civicrm_contribution_recur cr ON cr.contact_id =clpd.primary_contact_id
SET contribution_status_id = 7
WHERE contribution_status_id = 5 and removed = 1;\n';
    fwrite($write, $sql);
    fclose($read);
    fclose($write);
    fclose($notwrite);
    $cmd  = "mysql -u{$this->userName} -p{$this->pass} -h{$this->localhost} --default-character-set=utf8 {$this->dbName} < ".$this->par2parOnlinePath.$this->newDirectory."/updateTransaction.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Contribution Custom data filed in function : addContributionCustomData()');
    }
  }

  function drupalUser() {
    $config          = CRM_Core_Config::singleton();
    $getDBdetails = explode( '/',  $config->dsn);
    $dbName       = explode( '?',  $getDBdetails[3]);
    $dbName      = $dbName[0];
    $userName     = explode( '@', $getDBdetails[2] );
    $userName    = explode( ':', $userName[0] );
    $password        = $userName[1];
    $userName    = $userName[0];

    $getDBdetails    = explode( '/',  $config->userFrameworkDSN);
    $drupalDBName    = explode( '?',  $getDBdetails[3]);
    $drupalDBName    = $drupalDBName[0];
    $drupaluserName  = explode( '@', $getDBdetails[2] );
    $drupaluserName  = explode( ':', $drupaluserName[0] );
    $drupalpass      = $drupaluserName[1];
    $drupaluserName  = $drupaluserName[0];
    
    $read  = fopen( $this->par2parOnlinePath.$this->newDirectory.'/'.$this->localAdminFile, 'r' );
    ini_set('memory_limit', '2048M');

    $rows = fgetcsv( $read );
    $count = $others = 0;
    static $id_no ='';
    while ( $rows = fgetcsv( $read ) ) { 
      $ext_id = null;
      if ( !empty($rows[0]) ) {
        $ext_id = 'A-'.$rows[0];
      }
      $email = null;
      if ( !empty($rows[3]) ) {
        $email = addslashes($rows[3]);
      }
      $name = $nam = null;
      if (!empty($rows[1])) {
        $name = str_replace(' ', '.', strtolower( addslashes($rows[1])));
      }
      if(!empty($name)) {
      
        $contcatId = "(SELECT id FROM civicrm_contact where external_identifier ='{$ext_id}')";
        $ufMId = "SELECT uf_id FROM civicrm_uf_match WHERE contact_id = {$contcatId};\n"; 
      
        $uf_id['uf_id'] = CRM_Core_DAO::singleValueQuery($ufMId);
       
        //password = drpl@3252
        $password = addslashes('$S$DbfpWYNTukpzEU7GyqFg3yivAXiqQTssrpzbOc2UJdA1Ot3XJXMW');
        
        if (is_array($uf_id) && !empty($uf_id['uf_id'])) {
          $query = "UPDATE {$drupalDBName}.users SET mail = '{$email}' WHERE uid = {$uf_id['uf_id']}";
          CRM_Core_DAO::executeQuery($query);
        } else {
          $query1 = "SELECT max(uid) as id FROM {$drupalDBName}.users";
          $id = CRM_Core_DAO::singleValueQuery($query1) + 1;
          $query3 = "SELECT name FROM {$drupalDBName}.users where name LIKE '{$name}%'";
          $cb2 = CRM_Core_DAO::singleValueQuery($query3);
          if ($cb2) {
            $name = $name . '-' .CRM_Core_DAO::singleValueQuery("SELECT IFNULL(max(SUBSTRING_INDEX(name, '{$name}-', -1)), 0) + 1 FROM {$drupalDBName}.users WHERE `name` LIKE '{$name}-%'");
          } 
        
          $query = "INSERT INTO {$drupalDBName}.users (uid, name, pass, mail, signature_format, timezone, init, status ) VALUES ( {$id}, '{$name}', '{$password}', '{$email}', 'filtered_html', 'America/Toronto', '{$email}', 1 );";
          CRM_Core_DAO::executeQuery($query);
        
          $user_role = "Insert into {$drupalDBName}.users_roles (uid, rid) values( {$id}, '5') ON DUPLICATE KEY UPDATE uid = uid;";
          CRM_Core_DAO::executeQuery($user_role);
                 
          if ( empty($email) ) {
            $email = $name;
          }
    
          $query2 = "SELECT uf_name as name FROM civicrm_uf_match where uf_name = '{$email}'";
          $infob1 = CRM_Core_DAO::executeQuery($query2);
          if ($infob1->N) {
            $email = $name;
          }    
          $contcatId = "(SELECT id FROM civicrm_contact where external_identifier ='{$ext_id}')";
      
          $cvQQuery = "INSERT INTO civicrm_uf_match (domain_id, uf_id, uf_name, contact_id) VALUES ( 1, {$id}, '{$email}', {$contcatId}) ON DUPLICATE KEY UPDATE contact_id = {$contcatId};\n";
          
          CRM_Core_DAO::executeQuery($cvQQuery);
        }
      }
    }
    $cmd = "mysqldump -u{$drupaluserName} -p{$drupalpass} -h{$this->localhost} {$drupalDBName} > ".$this->par2parOnlinePath.$this->newDirectory."/".$this->dbBackup."/Drupal-Backup-After-UserImport.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Import Drupal User Failed in function : drupalUser()');
    }
  }
  function relatedContacts() {
   
    $con = mysql_connect( $this->localhost,"{$this->userName}" , "{$this->pass}" );
    if (!$con) {
      die('Could not connect: ' . mysql_error());
    }
    mysql_select_db( "{$this->dbName}", $con);
    $dao = mysql_query( "SELECT id FROM `civicrm_contact` WHERE `external_identifier` LIKE '%A-%'" );
    while(  $ids = mysql_fetch_assoc($dao) ){
      $flag = FALSE;
      $getRelationParam = array( 'version'    => 3,
                                 'contact_id' => $ids['id'] );
      $relationResult   = civicrm_api( 'relationship','get', $getRelationParam );
      foreach( $relationResult[ 'values' ] as $relKey => $relValue ) {
        if( $relValue[ 'relationship_type_id' ] == PAR_ADMIN_RELATION_TYPE_ID || $relValue[ 'relationship_type_id' ] == DENOMINATION_ADMIN_RELATION_TYPE_ID ){
          if (!$flag && $relValue[ 'contact_id_b']) { 
             self::clearRelatedContact($ids['id']); 
             $flag = TRUE;
           }
          self::putRelatedCache( array( $relValue[ 'contact_id_b' ] ), $ids['id'] );
        }
      }
      self::getRelatedDonors( $ids['id'] );
      self::getRelatedAdmins( $ids['id'] );
    }
  }
  function putRelatedCache( $cid, $currCID ) {
    if( $cid ){
      $flag = 0;
      $insertQuery = "INSERT IGNORE INTO custom_relatedContacts
                    ( contact_id, related_id ) VALUES";
      foreach( $cid as $cidKey => $cidValue ){
        if( $currCID != $cidValue ){
          $flag = 1;
          $insertQuery .= " ( {$currCID}, {$cidValue} )";
        }
      }
      $insertQuery .= ";";
      if( $flag ){
        $dao    =  CRM_Core_DAO::executeQuery( $insertQuery );
      }
    } else {
      $selectQuery = "SELECT contact_id_a FROM civicrm_relationship
                     LEFT JOIN custom_relatedContacts ON related_id = contact_id_b
                     WHERE relationship_type_id = " . IS_PART_OF_RELATION_TYPE_ID ." AND is_active = 1 AND processed = 0";
      $selectDao   =  CRM_Core_DAO::executeQuery( $selectQuery );
      $insertQuery = "INSERT IGNORE INTO custom_relatedContacts
                      ( contact_id, related_id ) VALUES ";
      $flag        = 0;
      $rowCount    = $selectDao->N;
      if( $selectDao->N ){
        while( $selectDao->fetch() ){
          if( $currCID != $selectDao->contact_id_a ){
            $rowCount--;
            $flag = 1;
            $insertQuery .= " ( {$currCID}, {$selectDao->contact_id_a} )";
            if( $rowCount != 0 ){
              $insertQuery .= ",";
            }
          }
        }
        $insertQuery = rtrim( $insertQuery, ',' );
        $insertQuery .= ";";
      }
      $updateQuery = "UPDATE custom_relatedContacts SET processed = 1 WHERE processed = 0 AND contact_id = {$currCID}";
      $dao    =  CRM_Core_DAO::executeQuery( $updateQuery );
      if( $selectDao->N && $flag ){
        $dao    =  CRM_Core_DAO::executeQuery( $insertQuery );
      }
    }
    //check if there are any record to be processed 
    $checkQuery = "SELECT count(id) AS count FROM custom_relatedContacts WHERE contact_id = {$currCID} AND processed = 0";
    $dao    =  CRM_Core_DAO::singleValueQuery( $checkQuery );
    if( $dao ){
      self::putRelatedCache( null, $currCID );
    }
  }
  function clearRelatedContact( $cid ) {
    $deleteQuery = "DELETE FROM custom_relatedContacts WHERE contact_id = {$cid}";   
    $dao    =  CRM_Core_DAO::executeQuery( $deleteQuery );
  }
  function getRelatedDonors( $cid ) {
    $insertQuery = "INSERT IGNORE INTO custom_relatedContacts
                    ( contact_id, related_id, processed ) 
                    SELECT {$cid}, contact_id_a, 1 FROM civicrm_relationship
                    LEFT JOIN custom_relatedContacts ON related_id = contact_id_b
                    WHERE contact_id = {$cid} AND relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID." AND is_active = 1 AND contact_id_a != related_id AND contact_id_a != {$cid}";
    $dao    =  CRM_Core_DAO::executeQuery( $insertQuery );
  }
  function getRelatedAdmins( $cid ) {
    $insertQuery = "INSERT IGNORE INTO custom_relatedContacts
                    ( contact_id, related_id, processed ) 
                    SELECT {$cid}, contact_id_a, 1 FROM civicrm_relationship
                    LEFT JOIN custom_relatedContacts ON related_id = contact_id_b
                    WHERE contact_id = {$cid} AND ( relationship_type_id = ".DENOMINATION_ADMIN_RELATION_TYPE_ID." OR relationship_type_id = ".PAR_ADMIN_RELATION_TYPE_ID." ) AND is_active = 1 AND contact_id_a != related_id";
    $dao    =  CRM_Core_DAO::executeQuery( $insertQuery );
  }

  function createBackup($after) {
    $cmd = "mysqldump -u{$this->userName} -p{$this->pass} -h{$this->localhost} {$this->dbName} > ".$this->par2parOnlinePath.$this->newDirectory."/".$this->dbBackup."/Civi-Backup-{$after}.sql";
    $test = exec($cmd, $output, $return);
    if ($return) {
      throw new Exception('Creating Backup failed');
    }
  }
  
  function startExport() {
    define('DRUPAL_ROOT', $this->root_path );
    
    include_once DRUPAL_ROOT.'includes/bootstrap.inc';
    drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    if (!isset($_GET['cron_key']) || variable_get('cron_key', 'drupal') != $_GET['cron_key']) {
      watchdog('cron', 'Cron could not run because an invalid key was used.', array(), WATCHDOG_NOTICE);
      drupal_access_denied();
    }
    elseif (variable_get('maintenance_mode', 0)) {
      watchdog('cron', 'Cron could not run because the site is in maintenance mode.', array(), WATCHDOG_NOTICE);
      drupal_access_denied();
    }
    else {
      drupal_cron_run();
    }
  }

  function setupImport() {
    if (file_exists($this->par2parOnlinePath.$this->importLog) ) {
      unlink($this->par2parOnlinePath.$this->importLog);
    }
    
    if ($this->monthlySync) {
      throw new Exception('Only one sync can run at a time.');
    }
    CRM_Core_DAO::executeQuery('UPDATE civicrm_option_value SET value = 1 WHERE id = 824');
    if (file_exists($this->par2parOnlinePath.$this->accountFile) && file_exists($this->par2parOnlinePath.$this->donorFile) && file_exists($this->par2parOnlinePath.$this->localAdminFile) && file_exists($this->par2parOnlinePath.$this->organizationFile) && file_exists($this->par2parOnlinePath.$this->transactionFile) && file_exists($this->par2parOnlinePath.$this->transactionNSFFile)) { 
      parent::startProcess();
      
      $oldmask = umask(0);
      mkdir($this->par2parOnlinePath.$this->newDirectory, 01777);
      umask($oldmask);
      $oldmask = umask(0);
      mkdir($this->par2parOnlinePath.$this->newDirectory.'/'.$this->dbBackup, 01777);
      umask($oldmask);
      $par_charge_accounts = copy( $this->par2parOnlinePath.$this->accountFile, $this->par2parOnlinePath.$this->newDirectory.'/'.$this->accountFile);
      $par_donor = copy( $this->par2parOnlinePath.$this->donorFile, $this->par2parOnlinePath.$this->newDirectory.'/'.$this->donorFile);
      $par_local_admin = copy( $this->par2parOnlinePath.$this->localAdminFile, $this->par2parOnlinePath.$this->newDirectory.'/'.$this->localAdminFile);
      $par_organization = copy( $this->par2parOnlinePath.$this->organizationFile,$this->par2parOnlinePath.$this->newDirectory.'/'.$this->organizationFile);
      $transaction = copy( $this->par2parOnlinePath.$this->transactionFile, $this->par2parOnlinePath.$this->newDirectory.'/'.$this->transactionFile);
      $transactionNSF = copy( $this->par2parOnlinePath.$this->transactionNSFFile, $this->par2parOnlinePath.$this->newDirectory.'/'.$this->transactionNSFFile);
      return TRUE;
    } 
    else {
      throw new Exception('No file(s) found to import');
    }
  }

  function endProcess() {
    define('DRUPAL_ROOT',$this->root_path);
    include_once DRUPAL_ROOT.'includes/bootstrap.inc';
    drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
    if (!$this->isMonthlySync) {
      $var = db_query("update variable set value = 'i:0;' where name = 'maintenance_mode'")->execute();
      cache_clear_all('variables', 'cache_bootstrap');
    }
    CRM_Core_DAO::executeQuery('UPDATE civicrm_option_value SET value = 0 WHERE id = 824');
  }
  function sendMail() {
    if (file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedNSF) || file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedOrg) || file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedAdmin) || file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedDonor) || file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedCharge) || file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notImportedTransactions) || file_exists($this->par2parOnlinePath.$this->newDirectory.'/'.$this->notUpdatedTransactions)) { 
      $to      = 'jding@united-church.ca';
      $subject = 'PAR2PAROnline data migration errors';
      $message = 'There were errors in the PAR2PAROnline import. Details can be found in files in the ' . "\r\n" .
        $this->par2parOnlinePath.$this->newDirectory. "\r\n" .
        'directory.';
      $headers = 'From: info@united-church.ca' . "\r\n" .
        'Reply-To: info@united-church.ca' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
      $mail = mail($to, $subject, $message, $headers);
    }
  }
  function deleteFiles() {
    unlink($this->par2parOnlinePath . $this->transactionNSFFile);
    unlink($this->par2parOnlinePath . $this->accountFile);
    unlink($this->par2parOnlinePath . $this->donorFile);
    unlink($this->par2parOnlinePath . $this->localAdminFile);
    unlink($this->par2parOnlinePath . $this->organizationFile);
    unlink($this->par2parOnlinePath . $this->transactionFile);
    $backupsToDelete =  $this->par2parOnlinePath . date('Ym', strtotime(date('Ymd') . ' -2 months')) . '*/';
    $cmd = "rm -rf {$backupsToDelete}dbBackup";
    shell_exec($cmd);
    $cmd = "rm -rf {$backupsToDelete}*.sql";
    shell_exec($cmd);
  }
  function logs($data) {
    $file = fopen($this->par2parOnlinePath . $this->newDirectory . '/' . $this->importLog, 'a');
    fwrite($file, $data . "\n");
    fclose($file);
  }
}

$importObj = new CRM_par_import();
$importObj->isMonthlySync = CRM_Utils_Array::value(1, $argv);
try {
  $flag = $importObj->setupImport();
  if ($flag) {
    $importObj->createBackup('preImport');
    $importObj->logs("Update previous months NSF contributions - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importDonorNsfData();
    //$importObj->createBackup('nsf');
    $importObj->logs("Update previous months NSF contributions - End @ " . date('Y-m-d H:i:s'));
  
    $importObj->logs("Organization Import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importOrganisation();
    //$importObj->createBackup('Org');
    $importObj->logs("Organization Import - End @ " . date('Y-m-d H:i:s'));
  
    $importObj->logs("Organization relationship Import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importOrgRelationship();
    //$importObj->createBackup('OrgRel');
    $importObj->logs("Organization relationship Import - End @ " . date('Y-m-d H:i:s'));
  
    $importObj->logs("Admin Import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importAdmin();
    //$importObj->createBackup('Admin');
    $importObj->logs("Admin Import - End @ " . date('Y-m-d H:i:s'));

    $importObj->logs("Admin relationship Import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importAdminRelationship();
    //$importObj->createBackup('AdminRel');
    $importObj->logs("Admin relationship Import - End @ " . date('Y-m-d H:i:s'));
  
    $importObj->logs("Donor Import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importDonor();
    //$importObj->createBackup('Donor');
    $importObj->logs("Donor Import - End @ " . date('Y-m-d H:i:s'));
  
    $importObj->logs("Contribution type account details import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importCharge();
    //$importObj->createBackup('Charge');
    $importObj->logs("Contribution type account details import - End @ " . date('Y-m-d H:i:s'));

    $importObj->logs("Contribution Import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->importContribution();
    //$importObj->createBackup('Contribution');
    $importObj->logs("Contribution Import - End @ " . date('Y-m-d H:i:s'));

    $importObj->logs("Contribution custom data import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->addContributionCustomData();
    //$importObj->createBackup('contriCustomData');
    $importObj->logs("Contribution custom data import - End. @ " . date('Y-m-d H:i:s'));

    $importObj->logs("Related Contacts Cache table import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->relatedContacts();
    //$importObj->createBackup('relatedContacts');
    $importObj->logs("Related Contacts Cache table import - End @ " . date('Y-m-d H:i:s'));
  
    $importObj->logs("Drupal user import - Start @ " . date('Y-m-d H:i:s'));
    $importObj->drupalUser();
    $importObj->logs("Drupal user import End @ " . date('Y-m-d H:i:s'));

    $importObj->deleteFiles();
    $importObj->createBackup('postImport');
  }
  //FIXME: add details
  $details = '';
}
catch(Exception $e) {
  $details = 'IMPORT FAILED SINCE : ' . $e->getMessage();
}
$importObj->createActivity(PAR2PAROnlineImport_ACTIVITY_TYPE_ID, 'PAR Legacy to PAR Online Import', $details);
$attachFile = FALSE;
if (!$importObj->monthlySync) {
  try {
    $importObj->logs("Export par log table - Start @ " . date('Y-m-d H:i:s'));
    $importObj->exportCSV();
    $importObj->logs("Export par log table - End @ " . date('Y-m-d H:i:s'));
    //FIXME: add details
    $details = '';
    $attachFile = TRUE;
  }
  catch(Exception $e) {
    $details = 'EXPORT FAILED SINCE : ' . $e->getMessage();
  }
  $importObj->createActivity(PAROnline2PAR_ACTIVITY_TYPE_ID, 'PAR Online to PAR Legacy Export', $details, $attachFile);
  $importObj->sendMail();
  $importObj->endProcess();
}
else {
  echo "A previous import is still running or failed. Please reset 'Sync currently running' flag to 0 in the Administer > System Settings > Option Groups > Synchronization with PAR Legacy -> Previous sync has not completed successfully.\n\n";
}
?>
