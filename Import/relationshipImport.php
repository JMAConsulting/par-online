<?php
$read  = fopen('/home/mayur/Desktop/ImportFiles/organizationImport.csv', 'r' );
$newRecordsToInsert  = fopen('/home/mayur/Desktop/ImportFiles/relationshipImport.sql', 'w' );

ini_set('memory_limit', '2048M');

$rows = fgetcsv( $read );
$count =0;
static $id_no ='';
while ( $rows = fgetcsv( $read ) ) { 
    $ext_id = $rows[0];
    $parent_id =  $rows[3];
    if(!empty($ext_id))
        { 
            $insert_all_rows='';
            $insert_rel = "INSERT INTO civicrm_relationship ( contact_id_a, contact_id_b, relationship_type_id, is_active) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}'),(SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$parent_id}'),10,1);\n";
          
            $insert_all_rows = $insert_rel;
            //print_r($insert_all_rows);
            
            fwrite($newRecordsToInsert,$insert_all_rows); 
            $count = $count +1;
        }
 }
echo "no of records = ".$count;
fclose($read);
fclose($newRecordsToInsert);


?>
