<?php
require_once 'CRM/Core/Form.php';

class CRM_Contact_Form_Donation extends CRM_Core_Form {
    function preProcess( ) {                    
    }
    
    public function buildQuickForm( ) {
        require_once 'CRM/Price/BAO/Set.php';
        require_once 'CRM/Price/DAO/Field.php';
        require_once 'CRM/Contribute/DAO/ContributionType.php';
        require_once 'CRM/Contribute/PseudoConstant.php';
        
        $tabIndex = CRM_Utils_Request::retrieve( 'tabIndex', 'Positive', $this, false );
        $cid      = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, false );
        $this->assign( 'tabIndex', $tabIndex );
        $this->assign( 'cid', $cid );
        $eid = CRM_Core_DAO::singleValueQuery("SELECT external_identifier FROM civicrm_contact WHERE id = ".$cid);
        if ($eid = strstr(ltrim($eid, "D-"), '-', TRUE)) {
          $pid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contact WHERE external_identifier = 'D-".$eid."'");
          if ($pid) {
            $this->assign( 'householdExt', $pid );
          }
        }
        if (!$pid) {
          $this->assign( 'household',  $cid);
        }
        $recurResult        = self::getRecurringContribution( $cid, true );
        $frequencyUnits     = CRM_Core_OptionGroup::values( 'recur_frequency_units' );
        $showFrequency      = SHOW_FREQUENCY_UNIT;
        $contributionStatus = $this->getAvailablePaymentStatus();
        if( $showFrequency ){
            $showFrequency = explode( ',', $showFrequency );
            foreach( $showFrequency as $freqKey => $freqValue ){
                $frequency[trim( $freqValue )] = $frequencyUnits[ trim( $freqValue ) ];
            }                                   
            $frequencyUnits = $frequency;
        }
        
        if( count( $frequencyUnits ) == 1 ) {
            $this->assign( 'singleFreqUnit', 1 );
        }
        $ccType    = CRM_Core_OptionGroup::values( 'accept_creditcard' );
        $paymentInstrument = array( 'Direct Debit', 'Credit Card' );
        $allInstruments = CRM_Contribute_PseudoConstant::paymentInstrument(  );
        $allInstruments = array_flip( $allInstruments );
        foreach( $paymentInstrument as $instrumentValue ){
            $instrument[ $allInstruments[$instrumentValue] ] = $instrumentValue;
        }
        $paymentInstrument = $instrument;
        rsort( $recurResult );
        $recurIds = array();
        if( !empty( $recurResult ) ){
            $this->set( 'contributionId', $recurResult[0][ 'installment' ][0]['contribution_id'] );
        } else {
            unset( $contributionStatus[7] );
            unset( $contributionStatus[1] );
            $contributionStatus[5] = '- not created -';
        }
        $this->_recurringDetails = $recurResult;
        $currentYear = date('Y');
        $totalAmount = 0;
        
        //Prepare payment details like Bank details or CC details
        //As all the contributions will be made through same payment
        //instrument we just need to check for first entry
        $contributionDetails = reset($recurResult);
     
        $priceSet     = $this->getRelatedPriceSetField( $cid );
       
        $fieldList    = current( $priceSet );
        $tplPriceList = array();
        $this->assign( 'fieldList', $fieldList );
        foreach( $fieldList as $fieldKey => $fieldValue ){
            $this->add( 'text', $fieldValue[ 'name' ]."_".$fieldKey, $fieldValue[ 'label' ], array( 'maxlength' => 3, 'class' => 'bank' ) );
        }
        //Get parent contribution type
        $priceSetField     = new CRM_Price_DAO_Field();
        $priceSetField->id = key($fieldList);
        $priceSetField->find( true );
        $typeDao = new CRM_Contribute_DAO_ContributionType();
        $typeDao->id = $priceSetField->contribution_type_id;
        $typeDao->find( true );
        $this->set( 'contributionType', $typeDao->parent_id );
        
        $buttons[] = array( 'type'      => 'save',
                            'name'      => ts('Save'),
                            'isDefault' => true);
        $this->addButtons(  $buttons );
        $this->set('priceSetId', $priceSet[ 'id' ]);
        //Build bank details block
        $daoObject = getLogDetails(array('nsf', 'removed'), array('primary_contact_id = ' . $cid));
        $extra = array();
        if (!in_array(CRM_Core_Session::singleton()->get('userID'), getSysAdmins())) {
          $extra['disabled'] = 'disabled';
        }
        if ($daoObject->nsf) {
          unset($contributionStatus[array_search('Stopped', $contributionStatus)]);
        }
        $this->add( 'select', "payment_status", null, $contributionStatus, null, array( 'class' => 'payment_status' ) );
        $choice[] = $this->createElement('radio', NULL, '1', ts('Yes'), 1, $extra);
        $choice[] = $this->createElement('radio', NULL, '0', ts('No'), 0, $extra);
        $this->addGroup($choice, 'nsf', 'NSF');
        $this->add( 'select', "frequency_unit", null, $frequencyUnits, null, array( 'class' => 'frequency_unit' ) );
        //$this->assign( 'contriStatus', $contributionStatus[$contributionDetails[ 'contribution_status_id' ]] );
        $this->add( 'select', "payment_instrument", null, $paymentInstrument, null, array( 'class' => 'payment_instrument' ) );
        $this->add( 'select', "cc_type", null, $ccType, null, array( 'class' => 'cc_type' ) );
        $this->add( 'text', "bank", null, array( 'maxlength' => 3, 'class' => 'bank' ) );
        $this->add( 'text', "branch", null, array( 'maxlength' => 5, 'class' => 'branch' ) );
        $this->add( 'text', "account", null, array( 'maxlength' => 12, 'class' => 'account' ) );
        $this->add( 'text', "cc_number", null, array( 'class' => 'cc_number' ) );
        $this->add( 'text', "contribution_id", null, array( 'class' => 'contribution_id' ) );
        $this->add('text', 'file_id', ts('Last File Number'), NULL, TRUE)->freeze();
        $this->add( 'text', "contribution_type", null, array( 'class' => 'contribution_type' ) );
        $this->add( 'hidden', "old_status", null, array( 'class' => 'old_status', 'id' => 'old_status' ) );
        $this->add( 'hidden', "old_instrument", null, array( 'class' => 'old_instrument', 'id' => 'old_instrument' ) );
        $this->add( 'date', "cc_expire", null, array( 'addEmptyOption'    => 1, 
                                                                  'emptyOptionText'   => '- select -',
                                                                  'emptyOptionValue'  => null,
                                                                  'format'            => 'M Y',
                                                                  'minYear'           => $currentYear,
                                                                  'maxYear'           => date( 'Y', strtotime( "{$currentYear} +10 year" ) ),
                                                                  ) );
        $this->add( 'text', "cavv", null, array( 'class' => 'cavv' ) );
        $this->add( 'hidden', "pricesetid", null, array( 'id' => "pricesetid" ) );
        CRM_Price_BAO_Set::buildPriceSet( $this );
        if ($daoObject->nsf) {
          foreach ($this->_elementIndex as $key => $keyID) {
            if (substr($key, 0, 6) == 'price_') {
              $this->_elements[$keyID]->_attributes['readonly'] = TRUE;
            }
          }          
        }
        $session =  CRM_Core_Session::singleton( );
        $status  = $session->getStatus( true );
        if( $status ){
            $this->assign( 'status', $status );
        }
    }
    
    function setDefaultValues( ) {
        require_once 'CRM/Price/BAO/Set.php';
        require_once 'CRM/Price/DAO/LineItem.php';
        $default = array();
        $cid = CRM_Utils_Request::retrieve( 'cid', 'Positive', $this, false );
        $ccType = CRM_Core_OptionGroup::values( 'accept_creditcard' );
        $paymentStatus         = $this->getAvailablePaymentStatus();
        $flipCCType            = array_flip( $ccType );
        $accountDetails        = getAccountColumns();
        $bankDetails           = $accountDetails['fieldId'];
        $default['pricesetid'] = $this->get('priceSetId');
        $default['contribution_type'] = $this->get('contributionType');     
        $default['contribution_id']   = $this->get('contributionId');
        $query = 'SELECT * FROM civicrm_value_par_account_details_6 WHERE entity_id = ' . $cid;
        $dao = CRM_Core_DAO::executeQuery($query);
        if ($dao->fetch() && !in_array($dao->bank_number_11, array(VISA, MASTER_CARD))) {
            $default['bank'] = $dao->bank_number_11;
            $default['branch'] = $dao->branch_number_12;
            $default['account'] = $dao->par_account_number_13;
        }
        foreach( $this->_recurringDetails as $recurKey => $recurValue ){
            $default['old_instrument'] = 
              $default['payment_instrument'] = $recurValue['payment_instrument_id'];
            $default['payment_status'] = $recurValue[ 'contribution_status_id' ];
            $default['old_status'] = $recurValue[ 'contribution_status_id' ];
            if (!in_array($recurValue['installment'][0][$bankDetails['type']], array(VISA, MASTER_CARD))) {
              $default['cc_type'] = $recurValue['installment'][0][$bankDetails['type']];
            }
            else {
              if ($recurValue['installment'][0][$bankDetails['type']] == VISA) {
                $default['cc_type'] = 1;
              }
              else {
                $default['cc_type'] = 2;
              }
            }
            break;
        }
        $contributions = $this->getRecurringContribution($cid, TRUE);
        if (!empty($contributions)) {
          rsort($contributions);
          $lineItem = new CRM_Price_DAO_LineItem();
          $lineItem->entity_table = 'civicrm_contribution_recur';
          $lineItem->entity_id = $contributions[0]['id'];
          $lineItem->find();
          while ($lineItem->fetch()) {
            $default[ 'price_'.$lineItem->price_field_id ] = $lineItem->line_total;
          }
        }
        $daoObject = getLogDetails(array('nsf', 'removed'), array('primary_contact_id = ' . $cid));
        $default['nsf'] = $daoObject->nsf;
        $default['file_id'] = CRM_Core_DAO::singleValueQuery('SELECT file_number_52 FROM civicrm_value_change_log_18 WHERE entity_id = ' . $cid . ' ORDER BY modified_date_50 DESC LIMIT 1;');
        return $default;
    }
    
    static function getRecurringContribution( $cid, $noCompleted = false ) {
        if ( $cid ) {
            require_once 'api/api.php';
            require_once 'CRM/Contribute/DAO/ContributionRecur.php';
            $contributionRecur = new CRM_Contribute_DAO_ContributionRecur();
            $contributionRecur->contact_id = $cid;
            $contributionRecur->orderBy('id desc');
            $contributionRecur->find(TRUE);
            $recurContributions = array();
            if ($contributionRecur->N) {
              if ($contributionRecur->contribution_status_id != 3 && ($contributionRecur->contribution_status_id != 1 || !$noCompleted ) ){
                $recurContributions[$contributionRecur->id] = array();
                CRM_Core_DAO::storeValues( $contributionRecur,  $recurContributions[$contributionRecur->id] );
                $contributionParams = array( 
                  'version' => 3,
                  'contact_id' => $cid,
                  'contribution_recur_id' => $contributionRecur->id,
                  'sort' => 'contribution_id DESC'
                );
                $contributions = getContributions($contributionParams);
                $recurContributions[$contributionRecur->id]['installment'][] = ($contributions['count']) ? reset($contributions['values']) : array();
              }
            }
            return $recurContributions;
        } else {
            return false;
        }
    }
    
    public function getAvailablePaymentStatus(){
        $contributionStatus = CRM_Core_OptionGroup::values( 'contribution_status' );
        unset( $contributionStatus[2] );
        unset( $contributionStatus[3] );
        unset( $contributionStatus[4] );
        unset( $contributionStatus[6] );
        return $contributionStatus;
    }
    
    public function getRelatedPriceSetField( $cid ){
        require_once 'CRM/Price/DAO/Set.php';
        require_once 'CRM/Price/DAO/Field.php';
        $contributionType = $this->getRelatedFundType( $cid );

        $sets             = array();
        foreach( $contributionType as $contriTypeId => $contriValue ){
            
            $priceSetField    = new CRM_Price_DAO_Field();
            $priceSetField->contribution_type_id = $contriTypeId;
            $priceSetField->find( );
            while( $priceSetField->fetch() ){
                //Only text priceset fields are supported
                if( $priceSetField->html_type == 'Text' ){
                    $sets[ 'fields' ][ $priceSetField->id ][ 'name' ] = $priceSetField->name;
                    $sets[ 'id' ] = $priceSetField->price_set_id;
                    $sets[ 'fields' ][ $priceSetField->id ][ 'label' ] = $priceSetField->label;
                    
                }
            }
        }
        return $sets;
    }    

    public function getRelatedFundType( $cid, $relationType = SUPPORTER_RELATION_TYPE_ID ){
        require_once 'api/api.php';
        $getRelationParam = array( 'version'  => 3,
                                   'contact_id'       => $cid );
        $result = civicrm_api( 'relationship', 'get', $getRelationParam );
        $contributionTypes = array();
        require_once 'CRM/Contribute/DAO/ContributionType.php';
        $typeDao = new CRM_Contribute_DAO_ContributionType();
        foreach( $result[ 'values' ] as $relationValue ){
            if( $relationValue[ 'relationship_type_id' ] == $relationType ){
                $typeDao->contact_id = $relationValue[ 'contact_id_b' ];
                $typeDao->find();
                while( $typeDao->fetch() ){
                    $contributionTypes[ $typeDao->id ] = $typeDao->name;
                }
            }
        }
        asort($contributionTypes);
        return $contributionTypes;
    }

    static function saveContribution($postParams = NULL, $hasPostValue = FALSE) {
      require_once 'CRM/Contribute/BAO/ContributionRecur.php';
      require_once 'CRM/Core/Payment/DirectDebit.php';
      require_once 'CRM/Core/Payment/Moneris.php';
      require_once 'CRM/Core/BAO/CustomValueTable.php';
      require_once 'CRM/Utils/Date.php';
      require_once 'CRM/Utils/Money.php';
      require_once 'api/api.php';
      require_once 'CRM/Core/DAO/PaymentProcessor.php';
      require_once 'CRM/Core/OptionGroup.php';
      require_once 'CRM/Core/BAO/PaymentProcessor.php';
      require_once 'CRM/Contribute/PseudoConstant.php';
      
      $mode = 'live';
      if (IS_TEST_PAYMENT) {
        $mode = 'test';
      }
      
      if (!$hasPostValue) {
        $postParams = $_POST;
      }
        
      if (!CRM_Utils_Array::value('cid', $_GET)) {
        $_GET['cid'] = $postParams['monthly_contact_id'];
      }
      $postParams['cid'] = $_GET['cid'];
      $paymentProcessorDetails = CRM_Core_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, $mode);
      $moneris =& CRM_Core_Payment_Moneris::singleton($mode, $paymentProcessorDetails);
      foreach ($postParams as $postKey => $postValue) {
        $fieldDetails[ $postKey ] = $postValue;
      }
        
      if (!empty($postParams['contribution_id'])) {
        $query ="
SELECT cc.total_amount, cc.payment_instrument_id, par_donor_bank_id, par_donor_branch_id, par_donor_account, other_amount, general_amount, `m&s_amount`AS msamount, nsf
FROM civicrm_contribution  cc LEFT JOIN civicrm_log_par_donor ON cc.contact_id = primary_contact_id 
WHERE cc.id = " . $postParams['contribution_id'];
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $fieldDetails['oldData']= array(
            'Status' => $fieldDetails['old_status'],
            'Payment Instrument' => $dao->payment_instrument_id,
            'Bank #' => $dao->par_donor_bank_id,
            'Branch #' => $dao->par_donor_branch_id,
            'Account #' => $dao->par_donor_account,
            'General' => CRM_Utils_Money::format($dao->general_amount, NULL),
            'M&S' => CRM_Utils_Money::format($dao->msamount, NULL),
            'Other' => CRM_Utils_Money::format($dao->other_amount, NULL),
            'Total' => CRM_Utils_Money::format($dao->total_amount, NULL),
            'NSF' => $dao->nsf,
          );
        } 
      } 
        
      $successfullDonation = 'Donations changed successfully.';
        
      if($fieldDetails['contribution_id'] && $fieldDetails['old_status'] == 5 && $fieldDetails['payment_status'] == 5) {
        $noChanges = TRUE;
        if ($fieldDetails['payment_instrument'] == 1 && $fieldDetails['old_instrument'] != 1) {
          $noChanges = FALSE;
        }
        self::editContribution($fieldDetails['contribution_id'], $fieldDetails['payment_instrument'], 1, $noChanges);
      }
      elseif ($fieldDetails['contribution_id']) {
        if ($fieldDetails['payment_instrument'] == 1) {
          //ADD code to change the status
          $recurId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $fieldDetails['contribution_id'], 'contribution_recur_id');
          $invoice = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'invoice_id');
          if ($invoice) {
            $amount = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'amount');
            $results = self::stopOnHoldPayment($invoice, $fieldDetails['payment_status'], $amount, $moneris);
            if (is_a($results, 'CRM_Core_Error')) {
              CRM_Core_Session::setStatus(ts('Credit card processor has declined to update this recurring payment with the following Error message: ' . $results->getMessages($results)));
              echo ts('Credit card processor has declined to update this recurring payment with the following Error message: ' . $results->getMessages($results));
              CRM_Utils_System::civiExit();
            }
          }
        }
        self::editContribution($fieldDetails['contribution_id'], $fieldDetails['payment_instrument'], $fieldDetails['payment_status']);
        $fieldDetails['amount'] = ($fieldDetails['payment_status']) == 1 ? '0.00' : CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $fieldDetails[ 'contribution_id' ], 'total_amount');
        $logParams = array(
          'primary_contact_id' => $_GET['cid'], 
          'nsf' => $postParams['nsf'],
        );
        make_entry_in_par_log('Update', $logParams);
        $fileId = self::save_log_changes($fieldDetails);
        if ($fileId) {
          $successfullDonation .= ' Documentation to support this change should be filed under File # ' . $fileId;
        }
        CRM_Core_Session::setStatus(ts($successfullDonation));
        echo ts($successfullDonation);
        CRM_Utils_System::civiExit();
      }
      elseif (!$fieldDetails['contribution_id']) {
        $rid = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_contribution_recur WHERE payment_instrument_id <> 1 AND contribution_status_id IN (5, 7) AND contact_id = {$_GET['cid']} ORDER BY id DESC LIMIT 1");
        if ($rid) {
          CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution_recur SET contribution_status_id = 1,
            modified_date = now() WHERE id = {$rid}");
        }
      }
      
      $accountDetails = getAccountColumns();
      $parAccountDetails = array(
        'bank' => 'custom_11',
        'branch' => 'custom_12',
        'account' => 'custom_13',
      );
      $bankDetails    = $accountDetails['fieldId'];
      $monerisParams = array();
      $contactParams = array( 
        'version' => 3,
        'id'      => $_GET['cid'] );
      $contactResult = civicrm_api('contact', 'get', $contactParams);
      $addressParams = array( 
        'version'           => 3,
        'contact_id'        => $_GET['cid'],
        'is_billing'        => 1
      );
      $addressResult = civicrm_api('address', 'get', $addressParams);
      $emailParams = array( 
        'version'           => 3,
        'contact_id'        => $_GET['cid'],
        'location_type_id'  => 5,
      );
      $emailResult  = civicrm_api('email', 'get', $emailParams);
      $ccType = CRM_Core_OptionGroup::values( 'accept_creditcard' );
      $monerisParams['contact_id']     = $_GET['cid'];
      if (CRM_Utils_Array::value('id',$contactResult)) {
        $monerisParams['first_name']     = CRM_Utils_Array::value('first_name', $contactResult['values'][$contactResult['id']]);
        $monerisParams['middle_name']    = CRM_Utils_Array::value('middle_name', $contactResult['values'][$contactResult['id']]);
        $monerisParams['last_name']      = CRM_Utils_Array::value('last_name', $contactResult['values'][$contactResult['id']]);
      }
      if (CRM_Utils_Array::value('id',$addressResult)) {
        $monerisParams['street_address'] = CRM_Utils_Array::value('street_address', $addressResult['values'][$addressResult['id']]);
        $monerisParams['city'] = CRM_Utils_Array::value('city', $addressResult['values'][$addressResult['id']]);
        $monerisParams['province'] = CRM_Utils_Array::value('state_province_id', $addressResult['values'][$addressResult['id']]);
        $monerisParams['country'] = CRM_Utils_Array::value('country_id', $addressResult['values'][$addressResult['id']]);  
        $monerisParams['postal_code'] = CRM_Utils_Array::value('postal_code', $addressResult['values'][$addressResult['id']]);
      }
      if (CRM_Utils_Array::value('id',$emailResult)) {
        $monerisParams['email'] = CRM_Utils_Array::value('email', $emailResult['values'][$emailResult['id']]);
      }
      $invoice = NULL;
      $previousInstrument = $fieldDetails['old_instrument'];
      if ($fieldDetails['payment_instrument'] == 1) {
        if (empty($fieldDetails['contribution_id']) || $previousInstrument != 1) {
          $invoice = md5(uniqid(rand(), true));
        }
        else {
          require_once 'CRM/Core/DAO.php';
          $recurId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $fieldDetails['contribution_id'], 'contribution_recur_id');
          $invoice = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $recurId, 'invoice_id');
        }
      }
      $timestamp = date("H:i:s");
      $currentDate = date("Y-m-d");
      $date = getdate();
      if ($date['mday'] > 20) {
        $date['mon'] += $recurInterval;
        while ($date['mon'] > 12) {
          $date['mon']  -= 12;
          $date['year'] += 1;
        }
      }
      $date['mday']  = 20;
      $next = mktime($date['hours'],$date['minutes'],$date['seconds'],$date['mon'],$date['mday'],$date['year']);
      $time = explode(':', $timestamp);
      $date = explode('-', $currentDate);     
      $trxn_date = CRM_Utils_Date::format(array('Y'=>$date[0], 'M'=>$date[1], 'd'=>$date[2], 'H'=>$time[0], 'i'=>$time[1], 's'=>$time[2]));
      require_once 'CRM/Price/BAO/Set.php';
      $priceSetDetails = CRM_Price_BAO_Set::getSetDetail($fieldDetails['pricesetid']);
      $fields = $priceSetDetails[$fieldDetails['pricesetid']]['fields'];
      $lineitem = array();
      $start_date = date("YmdHis", $next);
      CRM_Price_BAO_Set::processAmount($fields, $fieldDetails, $lineitem);
      //Prepare recurring contribution params
        
      if ($fieldDetails['payment_instrument'] == 1) {
        if( !empty( $lineitem ) ) {
          foreach ( $lineitem as $lineitemKey => $lineitemValue ) {
            $monerisParams[ 'price_'.$lineitemKey] =$lineitemValue['line_total'];
          }
        }
        $monerisParams['credit_card_number'] = $fieldDetails['cc_number'];
        $monerisParams['cvv2'] = $fieldDetails['cavv'];
        $monerisParams['credit_card_exp_date'] = $fieldDetails['cc_expire'];
        $monerisParams['credit_card_type'] = $ccType[$fieldDetails['cc_type']];
        $monerisParams['payment_action'] = 'Sale';
        $monerisParams['invoiceID'] = $invoice;
        $monerisParams['currencyID'] = 'CAD';
        $monerisParams['year'] = $fieldDetails['cc_expire']['Y'];
        $monerisParams['month'] = $fieldDetails['cc_expire']['M'];
        $monerisParams['amount'] = $fieldDetails['amount'];
        $monerisParams['is_recur'] = 1;
        $monerisParams['frequency_interval'] = 1;
        $monerisParams['frequency_unit'] = $fieldDetails['frequency_unit'];
        $monerisParams['installments'] = 90010;
        if (empty($fieldDetails['contribution_id']) || $previousInstrument != 1) {
          $monerisParams['type'] = 'purchase';
        }
        else {
          $monerisParams['type'] = 'recur_update';              
        }
      }
      $recurParams  = array( 
        'contact_id' => $_GET['cid'],
        'amount' => $fieldDetails['amount'],
        'start_date' => $start_date,
        'create_date' => $trxn_date,
        'modified_date' => $trxn_date,
        'frequency_unit' => $fieldDetails['frequency_unit'],
        'frequency_interval' => 1,
        'payment_instrument_id' => $fieldDetails['payment_instrument'],
        'contribution_status_id' => $fieldDetails['payment_status'],
        'payment_processor_id' => 6,
        'invoice_id' => $invoice,
        'contribution_type_id' => $fieldDetails['contribution_type'],
        'trxn_id' => $invoice,
        'installments' => 90010
      );

      //Prepare params for contribution
      $params = array( 
        'contact_id' => $_GET['cid'],
        'receive_date' => $trxn_date,
        'total_amount' => $fieldDetails['amount'],
        'contribution_type_id' => $fieldDetails['contribution_type'],
        'payment_instrument_id' => $fieldDetails['payment_instrument'],
        'trxn_id' => $invoice,
        'invoice_id' => $invoice,
        'contribution_status_id' => $fieldDetails['payment_status'],
        'priceSetId' => $fieldDetails['pricesetid'],
        'custom_32' => $fieldDetails['nsf'],
        'version' => 3,
      );
      foreach ($lineitem as $lineItemKey => $lineItemValue) {
        if (array_key_exists('price_'.$lineItemKey, $fieldDetails)) {
          $params['price_'.$lineItemKey] = $fieldDetails['price_'.$lineItemKey];
        }
      }
      if (array_key_exists('amount_level', $fieldDetails)) {
        $params['amount_level'] = $fieldDetails['amount_level'];
      }
        
      if ($fieldDetails[ 'payment_instrument' ] != 1) {
        $paymentProcessor = new CRM_Core_Payment_DirectDebit($mode, $paymentProcessor = NULL);
        $recurObj = CRM_Contribute_BAO_ContributionRecur::add($recurParams, $ids = NULL);
        $params['contribution_recur_id'] = $recurObj->id;
        $params['source'] = 'Direct Debit';
        $params['fee_amount'] = 0.00;
        $params['net_amount'] = $params[ 'total_amount' ];
        foreach ($bankDetails as $bankKey => $bankValue) {
          $params[$bankValue] = CRM_Utils_Array::value($bankKey, $fieldDetails);
          if ($bankKey != 'type') {
            $contactCustom[$parAccountDetails[$bankKey]] = CRM_Utils_Array::value($bankKey, $fieldDetails);
          }
        }
        unset($params[$bankDetails['type']]);
        $paymentProcessor->doDirectPayment($params);
        $contactCustom['version'] = 3;
        $contactCustom['contact_id'] = $_GET['cid'];
        $contactCustom['contact_type'] = 'Individual';
        civicrm_api('contact', 'create', $contactCustom);
        $result = civicrm_api('contribution', 'create', $params);
        if (array_key_exists('id', $result) && $fieldDetails['pricesetid']) {
          require_once 'CRM/Contribute/Form/AdditionalInfo.php';
          $lineSet[$fieldDetails['pricesetid']] = $lineitem;
          CRM_Contribute_Form_AdditionalInfo::processPriceSet($result['id'], $lineSet);
          if ($recurObj->id) {
            self::processRecurPriceSet($recurObj->id, $lineSet);
          }
        }
      } 
      elseif ($fieldDetails['payment_instrument'] == 1) {
        if ($fieldDetails['cc_type'] == 1) {
          $bankIDValue =  VISA;
        }
        else {
          $bankIDValue = MASTER_CARD;
        }
        foreach ($bankDetails as $bankKey => $bankValue) {
          $params[$bankValue] = $bankIDValue;
          if ($bankKey != 'type') {
            $contactCustom[$parAccountDetails[$bankKey]] = $bankIDValue;
          }
        }
        $contactCustom['version'] = 3;
        $contactCustom['contact_id'] = $_GET['cid'];
        $contactCustom['contact_type'] = 'Individual';
        $contact = civicrm_api('contact', 'create', $contactCustom);
        $params['fee_amount'] = CRM_Utils_Money::format($params[ 'total_amount' ] * CC_FEES / 100, null, '%a');
        $params['net_amount'] = CRM_Utils_Money::format($params[ 'total_amount' ] - $params[ 'fee_amount' ], null, '%a');
        $params['source'] = 'Moneris';
        $monerisResult = $moneris->doDirectPayment($monerisParams);
        if (is_a($monerisResult, 'CRM_Core_Error')) {
          $extratext = '';
          if ($fieldDetails['old_instrument'] == 6 && !empty($postParams['contribution_id'])) {
            $extratext = 'The direct debit donation has been stopped successfully. ';
          }
          CRM_Core_Session::setStatus(ts($extratext . 'Credit card processor has declined to create/update this recurring payment  with the following Error message: ' . $monerisResult->getMessages($monerisResult)));
          echo ts($extratext . 'Credit card processor has declined to create/update this recurring payment with the following Error message: ' . $monerisResult->getMessages($monerisResult));
          if (!$hasPostValue) {
            CRM_Utils_System::civiExit();
          }
          else {
            return FALSE;
          }
        }
        if (in_array($monerisResult['trxn_result_code'], array(1, 27))) {
          $recurId = array();
          if ($monerisResult['trxn_result_code'] == 1) {
            $recurParams['id'] = $recurId['contribution'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $fieldDetails['contribution_id'], 'contribution_recur_id');
            $params['id'] = $fieldDetails['contribution_id'];
            require_once 'CRM/Price/BAO/LineItem.php';
            CRM_Price_BAO_LineItem::deleteLineItems($params['id'], 'civicrm_contribution');
            CRM_Price_BAO_LineItem::deleteLineItems($recurParams['id'], 'civicrm_contribution_recur');
          }
          $recurObj = CRM_Contribute_BAO_ContributionRecur::add($recurParams, $recurId);
          $params['contribution_recur_id'] = $recurObj->id;
          $result = civicrm_api('contribution', 'create', $params);
          if (array_key_exists('id', $result ) && $fieldDetails['pricesetid']) {
            require_once 'CRM/Contribute/Form/AdditionalInfo.php';
            $lineSet[$fieldDetails['pricesetid']] = $lineitem;
            CRM_Contribute_Form_AdditionalInfo::processPriceSet($result['id'], $lineSet);
            if ($recurObj->id) {
              self::processRecurPriceSet($recurObj->id, $lineSet);
            }
          }
        }
      }
      $logParams = array(
        'primary_contact_id' => $_GET['cid'], 
        'nsf' => $postParams['nsf'],
      );
      make_entry_in_par_log('Update', $logParams);
        
      //UCCPAR-491 
      $fileId = self::save_log_changes($fieldDetails);
      if ($fileId) {
        $successfullDonation .= ' Documentation to support this change should be filed under File # ' . $fileId;
      }
      CRM_Core_Session::setStatus(ts($successfullDonation));
      echo ts($successfullDonation);
      if (!$hasPostValue) {
        CRM_Utils_System::civiExit();
      }
    }
    
    function editContribution($contributionId, $paymentInstrument, $status = 1, $noChanges = FALSE){
      if ($paymentInstrument == 1 && $noChanges) {
        return FALSE;
      }
      self::changeContributionStatus($contributionId, $paymentInstrument, $status, $noChanges);
    }

    function changeContributionStatus($contributionId, $paymentInstrument = 1 , $status = 1, $noChanges = FALSE) { 
      require_once 'CRM/Contribute/BAO/ContributionRecur.php';
      require_once 'api/api.php';
      $getContributionParams = array( 
        'contribution_id' => $contributionId,
        'version' => 3 
      );
      $contributionDetails = civicrm_api('contribution', 'get', $getContributionParams);
      if(array_key_exists('values', $contributionDetails)) {
        $contributions = null;
        $recurId[ 'contribution' ] = $contributionDetails['values'][$contributionId]['contribution_recur_id'];
        $timestamp = date("H:i:s");
        $currentDate = date("Y-m-d");
        $date = explode('-', $currentDate);
        $time = explode(':', $timestamp);
        $trxn_date = CRM_Utils_Date::format(array('Y'=>$date[0], 'M'=>$date[1], 'd'=>$date[2], 'H'=>$time[0], 'i'=>$time[1], 's'=>$time[2]));
        $recurParams   = array( 
          'id' => $recurId['contribution'],
          'modified_date' => $trxn_date,
          'contribution_status_id' => $status 
        );
        $recurObj = CRM_Contribute_BAO_ContributionRecur::add($recurParams, $recurId);
        $contriParams = array( 
          'version' => 3,
          'id' => $contributionId,
          'contribution_status_id' => ($noChanges && $contributionDetails['values'][$contributionId]['contribution_status_id'] != 1) ? 3 : $status,
        );
        $result = civicrm_api('contribution', 'create', $contriParams);
      }  
    }
    public function deleteDonor( ) {
        require_once "CRM/Core/PseudoConstant.php";
        require_once 'api/api.php';
        $contributions = array();
        CRM_Core_PseudoConstant::populate( &$contributions, 'CRM_Contribute_DAO_Contribution', true, 'id', false, "contribution_status_id in ( 5, 7 ) and contact_id = {$_GET['cid']}" );
        if ( count($contributions) ) {
            CRM_Core_Session::setStatus( 'Donor cannot be deleted until all financial transactions have been deleted by the system administrator.' );
            CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/contact/view', "reset=1&selectedChild=donation&cid=".$_SESSION['CiviCRM']['view.id'] ) );
        } else {
            $params = array( 
              'id' => $_SESSION['CiviCRM']['view.id'],
              'version' => 3,
            );
            $result = civicrm_api('contact', 'delete', $params);
            CRM_Core_Session::setStatus( 'Donor deleted successfully, only users with the relevant permission will be able to restore it.' );
            CRM_Utils_System::redirect( CRM_Utils_System::url( 'civicrm/contact/view', "reset=1&selectedChild=donation&cid=".$_SESSION['CiviCRM']['view.id'] ) );
        }
    } 
    
    function processRecurPriceSet($contributionRecurId, $lineItem) {
      if (!$contributionRecurId || !is_array($lineItem)
          || CRM_Utils_system::isNull($lineItem)
          ) {
        return;
      }

      require_once 'CRM/Price/BAO/Set.php';
      require_once 'CRM/Price/BAO/LineItem.php';
      foreach ($lineItem as $priceSetId => $values) {
        if (!$priceSetId) {
          continue;
        }
        foreach ($values as $line) {
          $line['entity_table'] = 'civicrm_contribution_recur';
          $line['entity_id'] = $contributionRecurId;
          CRM_Price_BAO_LineItem::create($line);
        }
        CRM_Price_BAO_Set::addTo('civicrm_contribution_recur', $contributionRecurId, $priceSetId);
      }
    }
    
    function legacysync() {
      $syncOption = CRM_Utils_Request::retrieve('isMonthlySync', 'Positive');
      global $civicrm_root;
      if ($syncOption) {
        $file = 'import';
      }
      else {
        $file = 'export';        
      }
      chdir($civicrm_root. '/bin/');
      $screenName = 'import-' . date('Ymd');
      shell_exec('nohup screen -dmS ' . $screenName);
      shell_exec("php {$file}.php 1 &");
    }
    
    function save_log_changes($params) {
      if (!CRM_Utils_Array::value('cid', $params)) {
        return FALSE;
      }      
      require_once 'CRM/Utils/Money.php';
      $query = "SELECT other_amount, general_amount, `m&s_amount` AS msamount, nsf, par_donor_bank_id, par_donor_branch_id, par_donor_account FROM civicrm_log_par_donor WHERE primary_contact_id = " . $params['cid'];
      $dao =  CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $newData = array(
          'Status' => $params['payment_status'],
          'Payment Instrument' => $_POST['payment_instrument'],
          'Bank #' => $dao->par_donor_bank_id,
          'Branch #' => $dao->par_donor_branch_id,
          'Account #' => $dao->par_donor_account,
          'General' => CRM_Utils_Money::format($dao->general_amount, NULL),
          'M&S' => CRM_Utils_Money::format($dao->msamount, NULL),
          'Other' => CRM_Utils_Money::format($dao->other_amount, NULL),
          'Total' => CRM_Utils_Money::format($params['amount'], NULL),
          'NSF' => $dao->nsf,
        );
      }
      $fileNumber = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(file_number_52), 0)  + 1 FROM civicrm_value_change_log_18');
      if ($fileNumber < MAX_FILE_NUMBER) {
        $fileNumber = MAX_FILE_NUMBER; 
      }
      if (CRM_Utils_Array::value('oldData', $params) !== $newData) {
        $allInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
        $newData['Payment Instrument'] = $allInstruments[$newData['Payment Instrument']];
        $newData['Status'] = CRM_Contribute_PseudoConstant::contributionStatus($newData['Status']);
        $query = "INSERT INTO civicrm_value_change_log_18 (entity_id, file_number_52, modified_by_49, modified_date_50, change_log_data_51) values ({$_GET['cid']}, {$fileNumber}, '" . CRM_Core_Session::singleton()->get('userID') . "', now(), '" . serialize($newData) . "');";
        CRM_Core_DAO::executeQuery($query);
        return $fileNumber;
      }
      return NULL;
    }   
    
    function stopOnHoldPayment($invoice, $statusId, $amount, $moneris = NULL) {
      $monerisParams = array (
        'invoiceID' => $invoice,
        'type' => 'recur_update',
        'payment_status' => $statusId,
        'currencyID' => 'CAD',
        'amount' => $amount,
      );
      
      if (!$moneris) {
        $mode = IS_TEST_PAYMENT ? 'test' : 'live';
        require_once 'CRM/Core/BAO/PaymentProcessor.php';
        require_once 'CRM/Core/Payment/Moneris.php';
        $paymentProcessorDetails = CRM_Core_BAO_PaymentProcessor::getPayment(PAYMENT_PROCESSOR_ID, $mode);
        $moneris =& CRM_Core_Payment_Moneris::singleton($mode, $paymentProcessorDetails);
      }
      return $moneris->doDirectPayment($monerisParams);
    }

    function changeContriStatus($redirect = FALSE) {
      require_once 'CRM/Contribute/BAO/ContributionRecur.php';
      switch ($_GET['mode']) {
        case 'onhold':
          $statuscheck = 5;
          $updateStatus = 7;
          $fromStatusName = 'In Progress';
          $tostatusName = 'On Hold';
          break;
        case 'inprogress':
          $statuscheck = 7;
          $updateStatus = 5;
          $fromStatusName = 'On Hold';
          $tostatusName = 'In Progress';
          break;
        case 'stop':
          $statuscheck = 5;
          $updateStatus = 1;
          $fromStatusName = 'In Progress';
          $tostatusName = 'Stopped';
          break;
      }
      
      $recurValues = CRM_Contribute_BAO_ContributionRecur::getRecurContributions($_GET['cid']);
      
      if (!empty($recurValues)) {
        foreach ($recurValues as $contributionKey => $contributionValues) {
          if ($contributionValues['contribution_status_id'] != $statuscheck) {
            continue;
          }
          $contributionValues['payment_instrument_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $contributionValues['id'], 'payment_instrument_id'); 
          if ($contributionValues['payment_instrument_id'] != 6) {
            $invoice = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $contributionValues['id'], 'invoice_id');
            if ($invoice) {
              $amount = $contributionValues['amount'];        
              $result = self::stopOnHoldPayment($invoice, $updateStatus, $amount);
              if (is_a($result, 'CRM_Core_Error')) {
                CRM_Core_Session::setStatus(ts("Credit card processor has declined to change the status to {$tostatusName} for this recurring payment with the following Error message: " . $result->getMessages($result)));
                CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=donation&cid=" . $_GET['cid']));
                return FALSE;
              }              
            }
          }
          
          $updateParam = array( 
            'id' => $contributionValues['id'],  
            'contact_id' => $_GET['cid'],  
            'modified_date' => date('YmdHis'),
            'contribution_status_id' => $updateStatus
          );
          $ids = array('contribution' => $contributionValues['id']);
          $updateContribute = new CRM_Contribute_BAO_ContributionRecur();
          $recurResult = $updateContribute->add($updateParam, $ids);
          
          // add contribution change log for status change
          $logParams = array(
            'cid' => $_GET['cid'],
            'payment_status' => $updateStatus,
            'amount' => $contributionValues['amount'],
          );
          $oldInstrument = CRM_Utils_Array::value('payment_instrument', $_POST);
          $_POST['payment_instrument'] = $contributionValues['payment_instrument_id'];
          self::save_log_changes($logParams);
          $_POST['payment_instrument'] = $oldInstrument;
        } 
        if (!$redirect) {
          CRM_Core_Session::setStatus("All {$fromStatusName} recurring contributions set to {$tostatusName} successfully.");
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=donation&cid=" . $_GET['cid']));
        }
      } 
      elseif (!$redirect) {
        CRM_Core_Session::setStatus("No {$fromStatusName} recurring contributions available.");
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', "reset=1&selectedChild=donation&cid=" . $_GET['cid']));
      }      
    }    
}