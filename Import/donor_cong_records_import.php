<?php

$read  = fopen( '/home/mayur/Desktop/donorcongimport.csv', 'r' );
$newRecordsToInsert  = fopen('/home/mayur/Desktop/donorcongimport.sql', 'w' );
$generateCSV =  fopen('/home/mayur/Desktop/notimport.csv', 'w' );

ini_set('memory_limit', '2048M');

$rows = fgetcsv( $read );
fputcsv( $generateCSV, $rows );
$count = $others = 0;
static $id_no ='';
while ( $rows = fgetcsv( $read ) ) { 
    if ( !empty( $rows[0] ) ) {
        $ext_id =  'D-'.$rows[0];
    }
    //print_r($ext_id); 
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
    }else {
        $branch_id  = "NULL";
    }
    if ( !empty( $rows[4] ) ) {
        $branch_name = addslashes( $rows[4] );
    } else {
        $branch_name = "NULL";
    }
    //print_r($branch_name);

    $account_no = $rows[5];
    if ( !empty( $rows[5] ) ) {
        $account_no = $rows[5];
    } else {
        $account_no = "NULL";
    }
    $first_name1 = $last_name1 = $first_name2 = $last_name2 = $first = $second = $names = $othernames = null;
    $flag = 0;
    if ( !empty( $rows[6] ) ) {
        $donor_name  = addslashes( $rows[6] );
        if ( strstr( $donor_name, '&') ) {
            $name = explode( '&' , $donor_name );
            if ( strstr( $name[1], ',' ) ) {
                $first  = explode (  ',' , $name[0] );
                $second = explode (  ',' , $name[1] );
            } else {
                $names = explode ( ',', $name[0] );
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
            $others ++;
        }
        if ( !empty ( $first ) && !empty ( $second ) ) {
            $first_name1 = $first[1];
            $last_name1 = $first[0];
            $first_name2 = $second[1];
            $last_name2 = $second[0];
        } else if ( !empty ( $names ) ) {
            $first_name1 = $names[1];
            $last_name1 = $names[0];
            $first_name2 = $name[1];
            $last_name2 = $names[0];
        } else if ( !empty ( $othernames ) ) {
            $first_name1 = $othernames[0];
            $last_name1 = $name[0];
            $first_name2 = $othernames[1];
            $last_name2 = $name[0];
        } else if ( $flag == 1 ) {
            $first_name1 = $name[1];
            $last_name1 = $name[0];
            $first_name2 = null;
            $last_name2 = null;
        } else {
            $first_name1 = null;
            $last_name1 = null;
            $first_name2 = null;
            $last_name2 = null;
        }
        // print_r($others); echo"\n";
        // print_r($first_name1);echo"\n";
        // print_r($last_name1);echo"\n";
        // print_r($first_name2);echo"\n";
        // print_r($last_name2);echo"\n";
        // print_r($count);
        // if ( $count == 10 ) {

        //     exit;
        // }

    } 
    // if ($count == 10 ) {
    //     exit;
    // }
    
    $donor_type = $rows[7];
    //print_r($donor_type);
    if ( !empty( $rows[8] ) ) {
        $donor_owner_id = 'O-'.$rows[8];
    } else {
        $donor_owner_id = null;
    }
    //print_r($donor_owner_id);
    if ( !empty( $rows[9] ) ) {
        $donor_envelope = $rows[9];
    } else {
        $donor_envelope = null;
    }
    // $donor_uc_direct = $rows[10];

    if ( !empty( $rows[11] ) ) {
        $donor_nsf = $rows[11];
    } else {
        $donor_nsf = 'NULL';
    }

    if ( !empty( $rows[12] ) ) {
        $donor_removed = $rows[12];
    } else {
        $donor_removed = 'NULL';
    }

    if ( !empty( $rows[13] ) ) {
        $donor_ms_amount = $rows[13];
    } else {
        $donor_ms_amount = null;
    }
    
    if ( !empty( $rows[14] ) ) {
        $donor_cong_amount = $rows[14];
    } else {
        $donor_cong_amount = null;
    }
    
    if ( !empty( $rows[15] ) ) {
        $donor_other_amount = $rows[15];
    } else {
        $donor_other_amount = null;
    }
    
    if ( !empty( $rows[17] ) ) {
        $donor_ms_no = $rows[17];
    } else {
        $donor_ms_no = null;
    }
    if ( !empty( $last_name1 ) && !empty( $first_name1 ) ) {
        $display_name =  $last_name1.', '.$first_name1;
    } else if ( !empty( $last_name1 ) && empty( $first_name1 ) ) {
        $display_name =  $last_name1;
    } else if ( empty( $last_name1 ) && !empty( $first_name1 ) ) {
        $display_name =  $first_name1;
    } else {
        $display_name = null;
    }
    
    if ( !empty( $last_name2 ) && !empty( $first_name2 ) ) {
        $display_name2 =  $last_name2.', '.$first_name2;
    } else if ( !empty( $last_name2 ) && empty( $first_name2 ) ) {
        $display_name2 =  $last_name2;
    } else if ( empty( $last_name2 ) && !empty( $first_name2 ) ) {
        $display_name2 =  $first_name2;
    } else {
        $display_name2 = null;
    }
    
    if(!empty($ext_id) && !empty($first_name1) && !empty($last_name1) )
        { 
            $insert_all_rows='';
            $insert_donor =  null;
            if ( !empty( $ext_id )  ) {
                $insert_donor = "INSERT INTO civicrm_contact ( external_identifier, contact_type, sort_name, first_name, last_name, display_name  ) values ('{$ext_id}','Individual', '{$display_name}', '{$first_name1}', '{$last_name1}', '{$display_name}');\n";
            }
            $contribution_ms_rec = $contribution_ms = $insert_ms_account_details = $insert_ms_nsf_details = null;

            if ( !empty($donor_ms_amount ) || $donor_ms_amount != 0 ) {
              
                $fee_amount = $donor_ms_amount * 0.025;
                $net_amount = $donor_ms_amount - $fee_amount;
                
                $contribution_ms_rec = " INSERT INTO civicrm_contribution_recur ( contact_id, amount, currency, installments, start_date, create_date, contribution_status_id, is_test, payment_processor_id  ) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), {$donor_ms_amount}, 'CAD', 999999999,'2011-10-20 00:00:00', '2011-10-20 00:00:00', 5 , 0, (SELECT id FROM civicrm_payment_processor WHERE payment_processor_type = 'DirectDebit' AND is_test = 0) );\n ";

                if ( ! empty( $contribution_ms_rec )) {
                    $contribution_ms = " INSERT INTO civicrm_contribution ( contact_id, contribution_type_id, payment_instrument_id, receive_date, total_amount, fee_amount, net_amount, currency, contribution_recur_id, contribution_status_id, is_test ) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), (SELECT MAX(id) FROM civicrm_contribution_type where name = 'M&S' AND contact_id = 8537  ), 6, '2011-10-20 00:00:00', {$donor_ms_amount}, {$fee_amount}, {$net_amount}, 'CAD', ( SELECT MAX(id) FROM civicrm_contribution_recur ), 5, 0 );\n ";
                }
                if ( ! empty( $contribution_ms )) {
                    $insert_ms_account_details = "INSERT INTO  civicrm_value_account_details_2 ( entity_id, bank_name_2, bank__name_38, account_number_4, branch_5, branch_name_39) values ((SELECT MAX(id) FROM civicrm_contribution ), {$bank_id}, '{$bank_name}', {$account_no},{$branch_id}, '{$branch_name}' );\n";
                 
                    $insert_ms_nsf_details = " INSERT INTO civicrm_value_nsf_12 ( entity_id, nsf_32, removal_33) values ((SELECT MAX(id) FROM civicrm_contribution ),{$donor_nsf}, {$donor_removed} );\n ";
                }
            }
            $contribution_cong_rec = $contribution_cong = $insert_cong_account_details = $insert_cong_nsf_details = null;
            if ( !empty( $donor_cong_amount ) || $donor_cong_amount != 0 ) {
               
                $fee_amount = $donor_cong_amount * 0.025;
                $net_amount = $donor_cong_amount - $fee_amount;

                $contribution_cong_rec = " INSERT INTO civicrm_contribution_recur ( contact_id, amount, currency, installments, start_date, create_date, contribution_status_id , is_test, payment_processor_id ) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), {$donor_cong_amount}, 'CAD', 999999999, '2011-10-20 00:00:00', '2011-10-20 00:00:00', 5, 0, (SELECT id FROM civicrm_payment_processor WHERE payment_processor_type = 'DirectDebit' AND is_test = 0) );\n ";

                  
                if ( ! empty( $contribution_cong_rec )) {
                    $contribution_cong = " INSERT INTO civicrm_contribution ( contact_id, contribution_type_id, payment_instrument_id, receive_date, total_amount, fee_amount, net_amount, currency, contribution_recur_id, contribution_status_id , is_test) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), (SELECT MAX(id) FROM civicrm_contribution_type where name = 'General' AND contact_id = 8537  ), 6, '2011-10-20 00:00:00', {$donor_cong_amount}, {$fee_amount}, {$net_amount}, 'CAD', ( SELECT MAX(id) FROM civicrm_contribution_recur ), 5, 0 );\n ";

                }
                if ( ! empty( $contribution_cong )) {
               
                    $insert_cong_account_details = "INSERT INTO  civicrm_value_account_details_2 ( entity_id, bank_name_2, bank__name_38, account_number_4, branch_5, branch_name_39) values ((SELECT MAX(id) FROM civicrm_contribution ), {$bank_id}, '{$bank_name}', {$account_no}, {$branch_id}, '{$branch_name}' );\n";
                
                    $insert_cong_nsf_details = " INSERT INTO civicrm_value_nsf_12 ( entity_id, nsf_32, removal_33) values ((SELECT MAX(id) FROM civicrm_contribution ),{$donor_nsf}, {$donor_removed} );\n ";
                }
            }
            $contribution_other_rec = $contribution_other = $insert_other_account_details = $insert_other_nsf_details = null;
            if ( !empty($donor_other_amount ) || $donor_other_amount != 0 ) {

                $fee_amount = $donor_other_amount * 0.025;
                $net_amount = $donor_other_amount - $fee_amount;

                $contribution_other_rec = "INSERT INTO civicrm_contribution_recur ( contact_id, amount, currency, installments, start_date, create_date, contribution_status_id , is_test, payment_processor_id ) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), {$donor_other_amount}, 'CAD', 999999999,'2011-10-20 00:00:00', '2011-10-20 00:00:00', 5, 0, (SELECT id FROM civicrm_payment_processor WHERE payment_processor_type = 'DirectDebit' AND is_test = 0) );\n ";

                if ( ! empty( $contribution_other_rec )) {
                    $contribution_other = "INSERT INTO civicrm_contribution ( contact_id, contribution_type_id, payment_instrument_id, receive_date, total_amount, fee_amount, net_amount, currency, contribution_recur_id , contribution_status_id, is_test ) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), (SELECT MAX(id) FROM civicrm_contribution_type where name = 'Other' AND contact_id = 8537  ), 6, '2011-10-20 00:00:00', {$donor_other_amount}, {$fee_amount}, {$net_amount}, 'CAD', ( SELECT MAX(id) FROM civicrm_contribution_recur ), 5, 0 );\n ";
                }

                if ( ! empty( $contribution_other ) ) {
                    $insert_other_account_details = "INSERT INTO  civicrm_value_account_details_2 ( entity_id, bank_name_2, bank__name_38, account_number_4, branch_5, branch_name_39) values ((SELECT MAX(id) FROM civicrm_contribution ), {$bank_id}, '{$bank_name}', {$account_no},{$branch_id}, '{$branch_name}' );\n";
            
                    $insert_other_nsf_details = " INSERT INTO civicrm_value_nsf_12 ( entity_id, nsf_32, removal_33) values ((SELECT MAX(id) FROM civicrm_contribution ),{$donor_nsf}, {$donor_removed} );\n ";
                }
            }
            $insert_ms_number = null;
            if (!empty($donor_ms_no)) {      
                $insert_ms_number = "INSERT INTO civicrm_value_other_details_7 ( entity_id , ms_number_16) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), {$donor_ms_no} );\n";
            }
            $insert_envelope = null;
            if (!empty($donor_envelope)) {      
                $insert_envelope = "INSERT INTO civicrm_value_envelope_13 ( entity_id , envelope_number_40) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}' ), {$donor_envelope} );\n";
            }
            $insert_donor_houshold = null;
            if ( !empty( $first_name2 ) && !empty( $last_name2 ) ) {
                $insert_donor_houshold = "INSERT INTO civicrm_contact ( external_identifier, contact_type, sort_name, first_name, last_name, display_name ) values ('{$ext_id}-1','Individual', '{$display_name2}',  '{$first_name2}', '{$last_name2}','{$display_name2}');\n";
            }

            $insert_all_rows = $insert_donor.$contribution_ms_rec.$contribution_ms.$insert_ms_account_details.$insert_ms_nsf_details.$contribution_cong_rec.$contribution_cong.$insert_cong_account_details.$insert_cong_nsf_details.$contribution_other_rec.$contribution_other.$insert_other_account_details.$insert_other_nsf_details.$insert_ms_number.$insert_envelope.$insert_donor_houshold;
             
            //print_r($insert_all_rows);  
            fwrite($newRecordsToInsert,$insert_all_rows); 
            $count = $count + 1;
        }
    else {
        fputcsv( $generateCSV, $rows );

    }
 }
echo "no of records = ".$count;
echo "no of invalid data records =".$others;
fclose($read);
fclose($newRecordsToInsert);



$read  = fopen( '/home/mayur/Desktop/donorcongimport.csv', 'r' );
$newRecordsToInsert  = fopen('/home/mayur/Desktop/donorcongrelimport.sql', 'w' );

ini_set('memory_limit', '2048M');

$rows = fgetcsv( $read );
fputcsv( $generateCSV, $rows );
$count = $others = 0;
static $id_no ='';
while ( $rows = fgetcsv( $read ) ) { 
    if ( !empty( $rows[0] ) ) {
        $ext_id =  'D-'.$rows[0];
    }
    if ( !empty( $rows[8] ) ) {
        $donor_owner_id = 'O-'.$rows[8];
    } else {
        $donor_owner_id = null;
    }
    $insert_donor = null;
    if ( !empty( $ext_id ) && !empty( $donor_owner_id ) ) {
        $insert_donor = "INSERT INTO civicrm_relationship ( contact_id_a, contact_id_b, relationship_type_id, is_active) values ((SELECT MAX(id) FROM civicrm_contact where external_identifier ='{$ext_id}'),8537,13,1);\n";
    }
    fwrite($newRecordsToInsert,$insert_donor); 
    $count = $count + 1;
 }