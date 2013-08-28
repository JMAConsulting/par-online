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

require_once 'CRM/Core/DAO/Mapping.php';
require_once 'CRM/Core/DAO/MappingField.php';

require_once 'CRM/Contribute/RBCImport/Parser/Contribution.php';

require_once 'CRM/Contribute/BAO/ContributionRecur.php';
/**
 * This class gets the name of the file to upload
 */
class CRM_Contribute_RBCImport_Form_Preview extends CRM_Core_Form 
{

    /**
     * cache of preview data values
     *
     * @var array
     * @access protected
     */
    protected $_dataValues;

    /**
     * mapper fields
     *
     * @var array
     * @access protected
     */
    protected $_mapperFields;

    /**
     * loaded mapping ID
     *
     * @var int
     * @access protected
     */
    protected $_loadedMappingId;

    /**
     * number of columns in import file
     *
     * @var int
     * @access protected
     */
    protected $_columnCount;


    /**
     * column headers, if we have them
     *
     * @var array
     * @access protected
     */
    protected $_columnHeaders;

    /**
     * an array of booleans to keep track of whether a field has been used in
     * form building already.
     *
     * @var array
     * @access protected
     */
    protected $_fieldUsed;

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess()
    {   
        $errors = $this->get( 'error' );
        $this->assign('errors', $errors );
        $errors = null;
        return true;
    }

    /**
     * Function to actually build the form
     *
     * @return void
     * @access public
     */
    public function buildQuickForm()
    {
        require_once "CRM/Core/BAO/Mapping.php";
        require_once "CRM/Core/OptionGroup.php";
        $rbcParams = $this->get( 'rbcDetails' );
        $totalRecords = $this->get( 'totalRecords' );
        $recordErrors = $this->get( 'recordErrors' );
        $errorCount = $this->get( 'errorCount' );
        $errorMessageRecords = $this->get( 'errorMessageRecords' );
        $recordsWithErrorMessages = $this->get( 'recordsWithErrorMessages' );
        $debitMessageRecords = $this->get( 'debitMessageRecords' );
        $creditMessageRecords = $this->get( 'creditMessageRecords' );
        $totalErrors = $errorCount;
        $this->assign( 'errorRecords', count( $recordsWithErrorMessages ) );
        $this->assign( 'errorMessageRecords', $errorMessageRecords );
        $this->assign( 'debitMessageRecords', $debitMessageRecords );
        $this->assign( 'creditMessageRecords', $creditMessageRecords );
        $this->assign( 'totalRecords', $totalRecords );
        $this->assign( 'totalErrors', $totalErrors );
        $validRecords = $totalRecords - $totalErrors;
        if ( $validRecords < 0 ) {
            $validRecords = 0;
        }
        $this->assign('validRecords', $validRecords );
         
        $errors = $this->get( 'error' );
        
        if ( empty( $errors ) ) {
            $this->addButtons( array(
                                     array ( 'type'      => 'back',
                                             'name'      => ts('<< Previous') ),
                                     array ( 'type'      => 'next',
                                             'name'      => ts('Continue >>'),
                                             'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
                                             'isDefault' => true   ),
                                     array ( 'type'      => 'cancel',
                                             'name'      => ts('Cancel') ),
                                     )
                               );
        } else {
            $this->addButtons( array(
                                     array ( 'type'      => 'back',
                                             'name'      => ts('<< Previous') ),
                                     array ( 'type'      => 'cancel',
                                             'name'      => ts('Cancel') ),
                                     )
                               );
        }
    }

    /**
     * global validation rules for the form
     *
     * @param array $fields posted values of the form
     *
     * @return array list of errors to be posted back to the form
     * @static
     * @access public
     */
    static function formRule( $fields, $files, $self ) {
 
    }

    /**
     * Process the mapped fields and map it into the uploaded file
     * preview the file and extract some summary statistics
     *
     * @return void
     * @access public
     */
    public function postProcess( )
    { 
        $processData = $this->get( 'processData' );
        $totalRecords = $this->get( 'totalRecords' );
        $recordsWithErrorMessages = $this->get( 'recordsWithErrorMessages' );
        $this->set( 'errorRecords', $recordsWithErrorMessages );
        $this->set( 'totalRecords', $totalRecords);
        require_once 'api/api.php';
        $importedRecords = 0;
        $session = CRM_Core_Session::singleton( );
        $contactID = $session->get( 'userID' );
        foreach( $processData as $key => $value ) {
            $sourceContactId = $targetContactId = $assignContactId = null;
            $rbcCustomDataDetails = getRBCColumns( );
            $accountCustomDataDetails = getAccountColumns( );
            if ( !empty( $value['customer_number'] ) ) {
              $doner_id = substr( $value['customer_number'], 8, 11 );
            }
            $ltrimed = ltrim( $doner_id, '0' ); 
            $contact_id = rtrim( $ltrimed, ' ' ); 
            if ( !empty( $value['payment_status'] ) ) {
                if ( $value['payment_status'] == 'U' ) {
                    $getParams = array( $rbcCustomDataDetails['fieldId']['rbc'] => ltrim( $value['file_number'], '0' ),
                                        'contact_id' => $contact_id,
                                        'contribution_status_id' => 1,
                                        $accountCustomDataDetails['fieldId']['branch'] => ltrim( $value['branch_number'], '0' ),
                                        $accountCustomDataDetails['fieldId']['account'] => ltrim( $value['account_number'], '0' ),
                                        'version'  => 3 );
                    $contributionParams = civicrm_api( 'contribution', 'get', $getParams );
                    $data = null;
                    foreach ( $contributionParams['values'] as $contrKey => $contrValue ) {

                        
                        $path = CIVICRM_UF_BASEURL.'civicrm/contact/view/contribution?action=view&reset=1&id='.$contrKey.'&cid='.$contact_id;
                        $name = "Contribution".$contrKey;
                        $data .= '<a href='.$path.'>'.$name.'</a> <br>';
                        
                        $getParams = array( 'contribution_recur_id' => $contrValue['contribution_recur_id'],
                                            'contact_id' => $contact_id,
                                            'contribution_type_id' => $contrValue['contribution_type_id'],
                                            'version'  => 3 );
                        $result = civicrm_api( 'contribution', 'get', $getParams );
                        
                        $pathRec = CIVICRM_UF_BASEURL.'civicrm/contact/view/contribution?action=update&reset=1&id='.$result['id'].'&cid='.$contact_id;
                        $nameRec = "Recuring Contribution". $contrValue['contribution_recur_id'];
                        $data .= '<a href='.$pathRec.'>'.$nameRec.'</a> <br>';
                        
                        if ( $contact_id == $contrValue['contact_id'] ) {
                            $relParams = array( 'contact_id' => 2,
                                                'version'  => 3 );
                            $result = civicrm_api( 'relationship', 'get', $relParams );
                            static $supporterFlag = null;
                            foreach ( $result['values'] as $key => $value ) {
                                if ( !isset( $supporterFlag ) ) {
                                    if ( $value['relationship_type_id'] == SUPPORTER_RELATION_TYPE_ID ) {
                                        $supporterFlag = 1;
                                        $newParams = array( 'contact_id' => $value['contact_id_b'],
                                                            'version'  => 3 );
                                        $newResult = civicrm_api( 'relationship', 'get', $newParams );
                                        static $flag = null;
                                        foreach ( $newResult['values'] as $newKey => $newValue ) {
                                            if ( $newValue['relationship_type_id'] == PAR_ADMIN_RELATION_TYPE_ID ) {
                                                if ( !isset( $flag ) ) {
                                                    $flag = 1;
                                                    $sourceContactId = $contactID;
                                                    $targetContactId = $contact_id;
                                                    $assignContactId = $newValue['contact_id_a'];
                                                }
                                            }
                                        } 
                                        $flag = null;
                                    }
                                }
                            }
                            $supporterFlag = null;
                            $nsfCustoData = getNSFColumns();
                            $updateParams = array( 'id' => $contrKey,
                                                   'contact_id' => $contact_id,
                                                   'contribution_status_id' => 4,
                                                   'contribution_type_id' => $contrValue['contribution_type_id'],
                                                   $nsfCustoData['fieldId']['nsf'] => 1,
                                                   'version'  => 3 );

                            $result = civicrm_api( 'contribution', 'create', $updateParams );

                            // // update recurring contribution table status ///
                            // $updateParam = array( 'id' => $contrRecId,  
                            //                       'contact_id' => $contact_id,  
                            //                       'contribution_type_id' => $contrValue['contribution_type_id'],
                            //                       'contribution_status_id' => 4 );
                            // $ids = array( 'contribution' => $contrRecId );
                            // $updateContribute = new CRM_Contribute_BAO_ContributionRecur();
                            // $recurResult = $updateContribute->add( $updateParam, $ids );
                          
                            $importedRecords ++;
                        }
                    }
            
                    if ( !empty ( $sourceContactId ) && !empty ( $targetContactId ) && !empty ( $assignContactId ) ) {
              
                        $returnedContributions = getReturnedContributionsColumns( );
                        $params = array( 
                                        'source_contact_id'    => $sourceContactId,
                                        'activity_type_id'     => RETURNED_RECORDS_ACTIVITY_TYPE_ID,
                                        'target_contact_id'    => $targetContactId,
                                        'assignee_contact_id'  => $assignContactId,
                                        'subject'              => "RBC returned records",
                                        'activity_date_time'   => date( 'Y-m-d H:i:s' ),
                                        'status_id'            => 2,
                                        'priority_id'          => 2,
                                        'version'              => 3,
                                        "{$returnedContributions['fieldId']['contributions']}" => $data,
                                         );
                        $activityResult = civicrm_api( 'activity','create',$params ); 
                    }
                } else if ( $value['payment_status'] == 'R' ) {
                    $getParams = array( $rbcCustomDataDetails['fieldId']['rbc'] => ltrim( $value['file_number'], '0' ),
                                        'contact_id' => $contact_id,
                                        $accountCustomDataDetails['fieldId']['branch'] => ltrim( $value['branch_number'], '0' ),
                                        $accountCustomDataDetails['fieldId']['account'] => ltrim( $value['account_number'], '0' ),
                                        'version'  => 3 );
                    $contributionParams = civicrm_api( 'contribution', 'get', $getParams );
                    foreach ( $contributionParams['values'] as $contrKey => $contrValue ) {
                        if ( $contact_id == $contrValue['contact_id'] ) {
                            $relParams = array( 'contact_id' => $contact_id,
                                                'version'  => 3 );
                            $result = civicrm_api( 'relationship', 'get', $relParams );
                            static $supporterFlag = null;
                            foreach ( $result['values'] as $key => $value ) {
                                if ( !isset( $supporterFlag ) ) {
                                    if ( $value['relationship_type_id'] == SUPPORTER_RELATION_TYPE_ID ) {
                                        $supporterFlag = 1;
                                        $newParams = array( 'contact_id' => $value['contact_id_b'],
                                                            'version'  => 3 );
                                        $newResult = civicrm_api( 'relationship', 'get', $newParams );
                                        static $flag = null;
                                        foreach ( $newResult['values'] as $newKey => $newValue ) {
                                            if ( $newValue['relationship_type_id'] == PAR_ADMIN_RELATION_TYPE_ID ) {
                                                if ( !isset( $flag ) ) {
                                                    $flag = 1;
                                                    $sourceContactId = $contactID;
                                                    $targetContactId = $contact_id;
                                                    $assignContactId = $newValue['contact_id_a'];
                                                }
                                            }
                                        } 
                                        $flag = null;
                                    }
                                }
                            }

                            $suppoerterFlag = null; 
                            $updateParams = array( 'id' => $contrKey,
                                                   'contact_id' => $contact_id,
                                                   'contribution_status_id' => 4,
                                                   'contribution_type_id' => $contrValue['contribution_type_id'],
                                                   'version'  => 3 );
                            $result = civicrm_api( 'contribution', 'create', $updateParams );
                            $importedRecords ++;
                        }
                    }
                    if ( !empty ( $sourceContactId ) && !empty ( $targetContactId ) && !empty ( $assignContactId ) ) {
                        $params = array( 
                                        'source_contact_id'    => $sourceContactId,
                                        'activity_type_id'     => RETURNED_RECORDS_ACTIVITY_TYPE_ID,
                                        'target_contact_id'    => $targetContactId,
                                        'assignee_contact_id'  => $assignContactId,
                                        'subject'              => "RBC returned records",
                                        'activity_date_time'   => date( 'Y-m-d H:i:s' ),
                                        'status_id'            => 2,
                                        'priority_id'          => 2,
                                        'version'              => 3,
                                         );
                        $activityResult = civicrm_api( 'activity','create',$params );
                    }
                }
            } 
        }
        $errorFlag = $ctrIDS = null;
        foreach( $recordsWithErrorMessages as $errorKey => $errorValue ) {
            $errorFlag = 1;
            $rbcCustomDataDetails = getRBCColumns( );
            $accountCustomDataDetails = getAccountColumns( );
            $doner_id = substr( $errorValue['customer_number'], 8, 11 );
            $ltrimed = ltrim( $doner_id, '0' );
            $contact_id = rtrim( $ltrimed, ' ' );
            if ( !empty( $errorValue['payment_status'] ) ) {
                $getParams = array( $rbcCustomDataDetails['fieldId']['rbc'] => ltrim( $errorValue['file_number'], '0' ),
                                    'contact_id' => $contact_id,
                                    $accountCustomDataDetails['fieldId']['branch'] => ltrim( $errorValue['branch_number'], '0' ),
                                    $accountCustomDataDetails['fieldId']['account'] => ltrim( $errorValue['account_number'], '0' ),
                                    'version'  => 3 );
                $contributionParams = civicrm_api( 'contribution', 'get', $getParams );
                $data =  null;
                foreach ( $contributionParams['values'] as $contrKey => $contrValue ) {

                    $getParams = array( 'contribution_recur_id' => $contrValue['contribution_recur_id'],
                                        'contact_id' => $contact_id,
                                        'contribution_status_id' => 5,
                                        'contribution_type_id' => $contrValue['contribution_type_id'],
                                        'version'  => 3 );
                    $result = civicrm_api( 'contribution', 'get', $getParams );
                    
                    $ctrIDS = $ctrIDS.''.$contrValue['contribution_recur_id'].',';
                    if ( $contact_id == $contrValue['contact_id'] ) {
                        $relParams = array( 'contact_id' => $contact_id,
                                            'version'  => 3 );
                        $result = civicrm_api( 'relationship', 'get', $relParams );
                        foreach ( $result['values'] as $key => $value ) {
                          if ( $value['relationship_type_id'] == SUPPORTER_RELATION_TYPE_ID ) {
                            $newParams = array( 'contact_id' => $value['contact_id_b'],
                                                'version'  => 3 );
                            $newResult = civicrm_api( 'relationship', 'get', $newParams );
                            static $flag = null;
                            foreach ( $newResult['values'] as $newKey => $newValue ) {
                              if ( $newValue['relationship_type_id'] == PAR_ADMIN_RELATION_TYPE_ID ) {
                                if ( !isset( $flag ) ) {
                                  $flag = 1;
                                  $sourceContactId = $contactID;
                                  $targetContactId = $contact_id;
                                  $assignContactId = $newValue['contact_id_a'];
                                }
                              }
                            } 
                            $flag = null;
                          }
                        }
                        $nsfCustoData = getNSFColumns();
                        $updateParams = array( 'id' => $contrKey,
                                               'contact_id' => $contact_id,
                                               'contribution_status_id' => 7,
                                               'contribution_type_id' => $contrValue['contribution_type_id'],
                                               'version'  => 3 );
                        $result = civicrm_api( 'contribution', 'create', $updateParams );

                        // //update rec table status
                        // $updateParam = array( 'id' => $contrRecId, 
                        //                       'contact_id' => $contact_id,  
                        //                       'contribution_type_id' => $contrValue['contribution_type_id'],
                        //                       'contribution_status_id' => 4 );
                        // $ids = array( 'contribution' => $contrRecId );
                        // $updateContribute = new CRM_Contribute_BAO_ContributionRecur();
                        // $recurResult = $updateContribute->add( $updateParam, $ids );
                                                               
                        $importedRecords ++;
                    }
                } 
                
            }
        } 
        if ( !empty ( $sourceContactId ) && !empty ( $targetContactId ) && !empty ( $assignContactId ) && !empty ( $ctrIDS ) && !empty ( $errorFlag ) ) {
            $ctrIDS = rtrim( $ctrIDS, ',' );
            require_once 'CRM/Core/DAO.php';
            $query = "SHOW TABLE STATUS LIKE 'civicrm_activity'";
            $activityResult = CRM_Core_DAO::executeQuery( $query );
            while ( $activityResult->fetch( ) ) {
                $activityId = $activityResult->Auto_increment;
            }
            $pathRec = CIVICRM_UF_BASEURL.'civicrm/contact/view?reset=1&cid='.$contact_id.'&selectedChild=donation&ctrid='.$ctrIDS.'&actid='.$activityId;
            $nameRec = "Donations";
            $data .= '<a href='.$pathRec.'>'.$nameRec.'</a> <br>';
            
            $errorContributions =  getErrorContributionsColumns( );
            $params = array ( 
                             'source_contact_id'    => $contactID,
                             'activity_type_id'     => ERROR_RECORDS_ACTIVITY_TYPE_ID,
                             'target_contact_id'    => $targetContactId,
                             'assignee_contact_id'  => $assignContactId,
                             'subject'              => "RBC Error records",
                             'activity_date_time'   => date( 'Y-m-d H:i:s' ),
                             'status_id'            => 1,
                             'priority_id'          => 2,
                             'version'              => 3,
                             "{$errorContributions['fieldId']['error_contributions']}" => $data,
                              );
            $activityResult = civicrm_api( 'activity','create',$params );
        } 
        $errorMessageRecords = $this->get( 'errorMessageRecords' );
        $debitMessageRecords = $this->get( 'debitMessageRecords' );
        $creditMessageRecords = $this->get( 'creditMessageRecords' );
        $notProcessed = $debitMessageRecords - $importedRecords;
        $this->set( 'notProcessed', $notProcessed );
        $this->set( 'importedRecords', $importedRecords );
        $this->assign( 'errorMessageRecords', $errorMessageRecords );
        $this->assign( 'debitMessageRecords', $debitMessageRecords );
        $this->assign( 'creditMessageRecords', $creditMessageRecords );
        $fileName = $this->get( 'fileName' );
        $returnedFileColumns = getReturnedFileColumns( );
        $params = array( 
                        'source_contact_id'    => $contactID,
                        'activity_type_id'     => IMPORT_RBC_ACTIVITY_TYPE_ID,
                        'assignee_contact_id'  => $contactID,
                        'subject'              => "RBC Import {$fileName}",
                        'activity_date_time'   => date( 'Y-m-d H:i:s' ),
                        'status_id'            => 2,
                        'priority_id'          => 2,
                        'version'              => 3,
                        "{$returnedFileColumns['fieldId']['total_error_message']}" => $errorMessageRecords,
                        "{$returnedFileColumns['fieldId']['total_debit_records']}" => $debitMessageRecords,
                        "{$returnedFileColumns['fieldId']['total_credit_records']}" => $creditMessageRecords,
                        "{$returnedFileColumns['fieldId']['total_transactions']}" => $totalRecords,
                        "{$returnedFileColumns['fieldId']['total_number_of_returned_records']}" => $importedRecords,
                        "{$returnedFileColumns['fieldId']['records_in_import_file']}" => $notProcessed,
                         );
        $activityResult = civicrm_api( 'activity','create',$params );
    }
    
    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle()
    {
        return ts('Preview');
    }

}


