<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';
require_once 'CRM/Contribute/RBCImport/Parser/Contribution.php';

/**
 * This class gets the name of the file to upload
 */
class CRM_Contribute_RBCImport_Form_Import extends CRM_Core_Form 
{

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess()
    { 
        $session = CRM_Core_Session::singleton( );
        $session->pushUserContext( CRM_Utils_System::url('civicrm/contribute/rbc/import', 'reset=1') );
    }
   
    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) {

        //Setting Upload File Size
        $config = CRM_Core_Config::singleton( );
        if ($config->maxImportFileSize >= 8388608 ) {
            $uploadFileSize = 8388608;
        } else {
            $uploadFileSize = $config->maxImportFileSize;
        }
        $uploadSize = round(($uploadFileSize / (1024*1024)), 2);
                
        $this->assign('uploadSize', $uploadSize );
        $this->addFormRule( array( 'CRM_Contribute_RBCImport_Form_Import', 'formRule' ) );
        $this->add( 'file', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=255', true );
        $this->addRule( 'uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile' );
        $this->addRule( 'uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(1 => $uploadSize, 2 => $uploadFileSize)), 'maxfilesize', $uploadFileSize );
        $this->setMaxFileSize( $uploadFileSize );
        
        $this->addButtons( array(
                                 array ( 'type'      => 'upload',
                                         'name'      => ts('Continue >>'),
                                         'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                                         'isDefault' => true ),
                                 array ( 'type'      => 'cancel',
                                         'name'      => ts('Cancel') ),
                                 )
                           );
    }

    public function formRule( $params, $files ) {
        $errors = array();
        if ($files['uploadFile']['type'] != 'text/plain') {
            $errors['uploadFile'] = '.text file required'; //array('uploadFile' => '.text file required');
        }
        return $errors;
    }
    /**
     * Process the uploaded file
     *
     * @return void
     * @access public
     */
    public function postProcess( ) {

        global $_FILES;
        $this->set( 'fileName', $_FILES['uploadFile']['name'] );
        $fileName = $this->controller->exportValue( $this->_name, 'uploadFile' );
        direct_debit_admin_require_once_rbc(TRUE);
        $rbc_file = new RBC( $type = null );
        $fileDetails = $rbc_file->readRBCFile( $fileName );

        $recordType = array( '0', '1', '2', '3', '4' );
        foreach ( $recordType as $type ) {
            if ( $type == 0 ) {
                $headerRecord = $fileDetails[$type];
            } else if ( $type == 1 ) {
                if ( !empty( $fileDetails[$type] ) ) {
                    $processData = $fileDetails[$type];
                    $recordType = $type;
                }
            } else if ( $type == 2 ) {
                if ( !empty(  $fileDetails[$type]) ) {
                    $processData = $fileDetails[$type];
                    $recordType = $type;
                }
            } else if ( $type == 3 ) {
                $accountRecord = $fileDetails[$type];
            } else if ( $type == 4 ) {
                $clientRecord = $fileDetails[$type];
            }
        }
        $this->set( 'processData', $processData );
        $errorCount = $total_amount = $errorMessageRecord = $debitMessageRecords = $creditMessageRecords = 0;
        $error = null;
      
        foreach ( $processData as $key => $value ) {
            if ( !empty( $value['payment_status'] ) ) {
                if ( $value['payment_status'] == 'U' ) {
                    $returnedPaymentRecords[$key] = $processData[$key];
                    if ( !empty( $value['payment_amount'] ) ) {
                        $total_amount = $total_amount + $value['payment_amount'];
                    }
                }  else if ( $value['payment_status'] == 'E' ) {
                    $reversedPaymentRecords[$key] = $processData[$key];
                } else if ( $value['payment_status'] == 'R' ) {
                    $rejectedPaymentRecords[$key] = $processData[$key];
                } else if ( $value['payment_status'] == 'W' ) {
                    $warningPaymentRecords[$key] = $processData[$key];
                } else if ( $value['payment_status'] == 'I' ) {
                    $InExcessPaymentRecords[$key] = $processData[$key];
                } 
            } else if ( empty( $value['payment_status'] ) ) {
                $ValidPaymentRecords[$key] = $processData[$key];
            } 
            if ( !empty( $value['is_error'] ) ) {
                foreach ( $value['is_error'] as $k => $val ) {
                    $errorCount++;
                    $error[$k] = $val;
                }
            }
        }
      
        static $setKey = null;
        foreach ( $processData as $key => $value ) {
            if ( !empty( $value['error_message_record'] ) ) {
                $errorMessageRecord ++;
                if ( !isset( $setKey ) ) {
                    $setKey = $key-1;
                }
                $processData[$setKey]['error'][$key] = $value;
                $recordsWithErrorMessages[$setKey] = $processData[$setKey];
                //unset( $processData[$key] );
            } else {
                $setKey = null;
            }
        }
        if ( !empty( $returnedPaymentRecords ) ) {
            if ( $recordType == 1 ) {
                $debitMessageRecords =  count( $processData ) - $errorMessageRecord;
        
                if ( count( $returnedPaymentRecords ) != $accountRecord['total_number_of_debit_records'] ) {
                    $recordErrors['total_number_of_debit_records'] = 'Uploaded file has a critical error: The total number of debit records in the file does not match the number indicated in the trailer record.';
                }
                if ( $total_amount != $accountRecord['total_amount_of_debit_records'] ) {
                    $recordErrors['total_amount_of_debit_records'] = 'Uploaded file has a critical error: The total amount in the debit records does not match the total amount indicated in the trailer record.';
                }
            } else if ( $recordType == 2 ) {
                $creditMessageRecords =  count( $processData ) - $errorMessageRecord;
                if ( count( $returnedPaymentRecords ) != $accountRecord['total_number_of_credit_records'] ) {
                    $recordErrors['total_number_of_credit_records'] = 'Uploaded file has a critical error: The total number of credit records in the file does not match the number indicated in the trailer record.';
                }
                if ( $total_amount != $accountRecord['total_amount_of_credit_records'] ) {
                    $recordErrors['total_amount_of_credit_records'] = 'Uploaded file has a critical error: The total number of credit records in the file does not match the number indicated in the trailer record.';
                }

            }
        }
      
        $this->set( 'errorMessageRecords', $errorMessageRecord );
        $this->set( 'debitMessageRecords', $debitMessageRecords );
        $this->set( 'creditMessageRecords', $creditMessageRecords );
        $this->set( 'totalRecords', count( $processData ) );

        $this->set( 'errorCount', $errorCount );
        if ( !empty( $recordErrors ) ) {
            $this->set( 'recordErrors', $recordErrors );
        }
        if ( !empty( $recordErrors ) && !empty( $error ) ) {
            $error = array_merge( $error, $recordErrors );
        } else if( !empty( $recordErrors ) && empty( $error ) ) {
            $error = $recordErrors;
        }
        if ( !empty( $error ) )
            $this->set( 'error', implode( "<br>", $error ) );
        else
            $this->set( 'error', 0 );
        
        $this->set( 'rbcDetails', $fileDetails );
        $this->set( 'recordsWithErrorMessages', $recordsWithErrorMessages );
        
    }

    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle( ) {
        return ts('Upload Data');
    }

}

