<?php

$read  = fopen('/home/mayur/Desktop/ImportFiles/organizationImport.csv', 'r' );
$newRecordsToInsert  = fopen('/home/mayur/Desktop/ImportFiles/organizationImport.sql', 'w' );

ini_set('memory_limit', '2048M');

$rows = fgetcsv( $read );
$count =0;
static $id_no ='';
while ( $rows = fgetcsv( $read ) ) { 
    $ms_number = null;
    $ext_id = $rows[0];
    $organization_name = addslashes( $rows[2] );
    $city = addslashes( $rows[9] );
    $contact_subtype = $rows[5];
    if( $contact_subtype == 'CH' ) {
        $contact_subtype = 'Congregation';
        $ms_number = $rows[14];
    } else if( $contact_subtype == 'CO' ) {
        $contact_subtype = 'Conference';
    } else if( $contact_subtype == 'PR' ) {
        $contact_subtype = 'Presbytery';
    }else if( $contact_subtype == 'PC' ) {
        $contact_subtype = addslashes( 'Pastoral_Charge' );
        $ms_number = $rows[8];
    }
    if(!empty($ext_id))
        { 
            $insert_all_rows='';
            $insert_org = "INSERT INTO civicrm_contact ( external_identifier, contact_type, contact_sub_type, sort_name, display_name, organization_name) values ('{$ext_id}','Organization', '{$contact_subtype}','{$organization_name}','{$organization_name}','{$organization_name}');\n";
          
            if(!empty($city)) {     
               $insert_city  = "INSERT INTO civicrm_address (contact_id, city,is_primary) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}'),'{$city}', 1 );\n" ;
            }    
            $insert_ms_number = null;
            if(!empty($ms_number)) {      
                $insert_ms_number = "INSERT INTO civicrm_value_other_details_7 ( entity_id , ms_number_16) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), {$ms_number} );\n";
            }
            $insert_all_rows = $insert_org.$insert_city.$insert_ms_number;   
            print_r($insert_all_rows);  
            fwrite($newRecordsToInsert,$insert_all_rows); 
            $count = $count +1;
        }
 }
echo "no of records = ".$count;
fclose($read);
fclose($newRecordsToInsert);

?>
