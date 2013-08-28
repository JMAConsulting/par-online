 <?php
require_once 'RBCBase.php';
$rbcType = null;
class RBC extends RBCBase
{

  /**
   * Determines the type of the DTA file:
   * DTA file contains debit payments (default).
   *
   * @const RBC_DEBIT
   */
  function __construct($type) {
    $this->rbcType = $type;
    parent::__construct();
  }

  /**
   * Set the sender of the DTA file. Must be set for valid DTA file.
   * The given account data is also used as default sender's account.
   * Account data contains
   *  name            Sender's name. Maximally 27 chars are allowed.
   *  bank_code       Sender's bank code.
   *  account_number  Sender's account number.
   *  additional_name If necessary, additional line for sender's name
   *                  (maximally 27 chars).
   *
   * @param array $account Account data for file sender.
   *
   * @access public
   * @return boolean
   */
  function setAccountFileSender( $account )
  {
    $account['client_number']
      = strval($account['client_number']);

    if (strlen($account['client_name']) > 0
        && strlen($account['client_number']) == 10
        && strlen($account['file_number']) == 4
        && ctype_digit($account['client_number'])
        && ctype_digit($account['file_number'])
        ) {
      $this->account_file_sender = array(
                                         "client_name"            => $account['client_name'],
                                         "client_number"          => $account['client_number'],
                                         "file_number"            => $account['file_number'],
                                         "file_date"              => $account['file_date'],
                                         "currency_type"          => $account['currency_type'],
                                         );

      $result = true;
    } else {
      $result = false;
    }
    return $result;
  }
  
  function addCreditTransactions( $transaction = null, $creditTransactions ) {
    /* if ( !empty( $offset ) ) { */
    /*   $this->recordNumber = $offset;*/
    /* } */
    
    $flag = 0;
    foreach( $creditTransactions as $transactionKey => $transactionValue ) {
      if( array_key_exists( 'contact_id', $transaction  ) && array_key_exists( 'bank_number', $transaction  ) && array_key_exists( 'branch_number', $transaction ) && array_key_exists( 'account_number', $transaction  ) ) {
        if( $transaction[ 'contact_id' ] == $transactionValue[ 'contact_id' ] && $transaction[ 'bank_number' ] == $transactionValue[ 'bank_number' ] && $transaction[ 'branch_number' ] == $transactionValue[ 'branch_number' ] && $transaction[ 'account_number' ] == $transactionValue[ 'account_number' ]  ){
          $creditTransactions[ $transactionKey ][ 'amount' ] += $transaction[ 'amount' ];
          $flag = 1;
          break;
        }
      }
    }
    if( !$flag ) {
      $creditTransactions[] = $transaction;
    }
    
    return $creditTransactions;
  }
  
  function addTransactions( $transaction = null, $offset = null ) {
    if ( !empty( $offset ) ) {
      $this->recordNumber = $offset;
    }
    if( !empty($transaction) ) {
      $flag = 0;
      foreach( $this->transactions as $transactionKey => $transactionValue ) {
        if( array_key_exists( 'contact_id', $transaction  ) && array_key_exists( 'bank_number', $transaction  ) && array_key_exists( 'branch_number', $transaction ) && array_key_exists( 'account_number', $transaction  ) ) {
          if(  $transaction[ 'contact_id' ] == $transactionValue[ 'contact_id' ] && $transaction[ 'bank_number' ] == $transactionValue[ 'bank_number' ] && $transaction[ 'branch_number' ] == $transactionValue[ 'branch_number' ] && $transaction[ 'account_number' ] == $transactionValue[ 'account_number' ]  ){
            $this->transactions[ $transactionKey ][ 'amount' ] += $transaction[ 'amount' ];
            $flag = 1;
            break;
          }
        }
      }
      if( !$flag ){
        $this->transactions[] = $transaction;
        //$this->transactions = array();
      }
    } else {
      // $this->_lastrecord = $this->transactions; 
      $this->transactions = null;
    }
  }
  
  function generateArecord() {
    return $content = $this->_generateArecord();
  }
  function generateZrecord( $debitName ) {
      return $content = $this->_generateZrecord( $debitName );
  }
  /**
   * Returns the full content of the generated RBC file.
   * All added exchanges are processed.
   *
   * @access public
   * @return string
   */

  function getCreditFileContent( $creditTransactions, $sender, $lastNumber, $serviceCharge, $paymentDate, $nsfCount ) {
    $content = null;
    $this->account_file_sender = $sender;
    $this->recordNumber = $lastNumber;
    /**
     * data record A
     */
    //$content .= $this->_generateArecord();
    foreach( $creditTransactions as $transaction ) {
      $content .= $this->_generateDrecord( $transaction );
      if( $this->rbcType ){
        $this->sum_amounts += $transaction['amount'];
      } else {
        $this->credit_sum_amounts += $transaction['amount'];
      }
    }
    $creditTransactions = null; 
    $creditTransactions = array();
    $content .= $this->_generateSCrecord( $serviceCharge, $paymentDate );
    $content .= $this->_generateZrecord( null, $nsfCount );
    return $content;
  }

  function getFileContent() {
    $content = null; 
    /**
     * data record A
     */
    //$content .= $this->_generateArecord();
    foreach( $this->transactions as $transaction ) {
      $content .= $this->_generateDrecord( $transaction );
      if( $this->rbcType ){
        $this->sum_amounts += $transaction['amount'];
      } else {
        $this->credit_sum_amounts += $transaction['amount'];
      }
    }
    $this->sum_amounts = $this->credit_sum_amounts =  $this->transactions = null; 
    $this->transactions = array();
    //$content .= $this->_generateZrecord();
    return $content;
  }

  function getLastNumber( ) {
      return $this->recordNumber;
  }
  
  function getFileSummary(  ) {
      $content = "";
      foreach( $this->transactions as $transaction ) {
          $content .= $this->_generateSummary( $transaction );
      }
      return $content;
  }

  private function _generateSummary( $transaction ) {
      $masterDonor = 0;
      $visaDonor = 0;
      if( array_key_exists( 'master_donors', $transaction ) ){
          $masterDonor = $transaction['master_donors'];
      }
      if( array_key_exists( 'visa_donors', $transaction ) ){
          $visaDonor = $transaction['visa_donors'];
      }
      return "
<html>
<body>
<h5>".str_pad( $transaction['debit_header'], 35, " ", STR_PAD_LEFT)."</h5>
<table>
<tr><td>".'Client Number'."</td><td>:</td><td>".$transaction['client_number']."</td></tr>
<tr><td>".'Service'."</td><td>:</td><td>".$transaction['debit_service']."</td></tr>
<tr><td>".'Client Name'."</td><td>:</td><td>".$transaction['client_name']."</td></tr>
<tr><td>".'Data Set Name'."</td><td>:</td><td>".$transaction['debit_data_set']."</td></tr>
<tr><td>".'# of Transactions'."</td><td>:</td><td>".$transaction['no_of_transactions']."</td></tr>
<tr><td>".'Dollar Value'."</td><td>:</td><td>".$transaction['debitDollarValue']."</td></tr>
<tr><td>".'Creation Date'."</td><td>:</td><td>".strftime("%b.%d, %Y", strtotime(date('Ymd')))."</td></tr>
<tr><td>".'File Creation #'."</td><td>:</td><td>".$transaction['file_creation_no']."</td></tr>
<tr><td>".'# of diskettes'."</td><td>:</td><td>".$transaction['no_of_diskettes']."</td></tr>
<tr><td></td><td></td></tr>
<tr><td></td><td></td></tr>
<tr><td>".'# of MASTER Donors'."</td><td>:</td><td>".$masterDonor."</td></tr>
<tr><td>".'# of VISA Donors'."</td><td>:</td><td>".$visaDonor."</td></tr>
<tr><td>".'Dollar Value of MASTER'."</td><td>:</td><td>".$transaction['dollar_master']."</td></tr>
<tr><td>".'Dollar Value of VISA'."</td><td>:</td><td>".$transaction['dollar_visa']."</td></tr>
</table>
<br><br/><p></p>
<h5>".str_pad( $transaction['credit_header'], 35, " ", STR_PAD_LEFT)."</h5>
<table>
<tr><td>".'Client Number'."</td><td>:</td><td>".$transaction['client_number']."</td></tr>
<tr><td>".'Service'."</td><td>:</td><td>".$transaction['credit_service']."</td></tr>
<tr><td>".'Client Name'."</td><td>:</td><td>".$transaction['client_name']."</td></tr>
<tr><td>".'Data Set Name'."</td><td>:</td><td>".$transaction['credit_data_set']."</td></tr>
<tr><td>".'# of Transactions'."</td><td>:</td><td>".$transaction['credit_transactions']."</td></tr>
<tr><td>".'Dollar Value'."</td><td>:</td><td>".$transaction['dollar_value']."</td></tr>
<tr><td>".'Creation Date'."</td><td>:</td><td>".date('m/d/Y')."</td></tr>
<tr><td>".'File Creation #'."</td><td>:</td><td>".$transaction['file_creation_no']."</td></tr>
<tr><td></td><td></td></tr>
<tr><td></td><td></td></tr>
<tr><td>".'S/C Generated'."</td><td>:</td><td>".$transaction['total_service_charge']."</td></tr>
<tr><td>".'NSF Deducted'."</td><td>:</td><td>".$transaction['nsf_deducted']."</td></tr>
<tr><td>".'Negative Amounts'."</td><td>:</td><td>".$transaction['negative_amount']."</td></tr>
<tr><td></td><td></td></tr>
<tr><td></td><td></td></tr>
<tr><td>".'VISA CARD Service Charge(2.5%)'."</td><td>:</td><td>".$transaction['visa_service_charge']."</td></tr>
<tr><td>".'MASTERCARD Service Charge(2.5%)'."</td><td>:</td><td>".$transaction['master_service_charge']."</td></tr>
</table>
</body>
</html>"; 
        
  }

  /**
   * Auxillary method to write the Sc record.
   *
   * @access private
   * @return string
   */
  private function _generateSCrecord( $serviceCharge, $paymentDate ) {
    $content = "\n";
    //$tempNumber = 0;
    //add record number to file
    $content .= str_pad( $this->recordNumber, 6, "0", STR_PAD_LEFT);
    //$tempNumber = $this->recordNumber;
    //add record type to file
    $content .= 'C';
    //add transaction code to file
    $content .= str_pad( 480, 3, " ", STR_PAD_RIGHT);
    //add client number to file
    $content .= str_pad( $this->account_file_sender['client_number'], 10, " ", STR_PAD_RIGHT);
    //add filler character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add transaction code to file
    //add client number to file
    $content .= str_pad( 1, 19, " ", STR_PAD_RIGHT );
    //add payment number to file
    $content .= str_pad( $transaction['payment_number'], 2, "0 ", STR_PAD_RIGHT);
    //add bank number to file
    $content .= str_pad( '3', 4, "0", STR_PAD_LEFT);
    //add branch number to file
    $content .= str_pad( '06702', 5, "0", STR_PAD_LEFT);
    //add branch number to file
    $content .= str_pad( '0000380', 18, " ", STR_PAD_RIGHT);
    //add filler character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add amount to file
    $content .= str_pad( $serviceCharge*100, 10, "0", STR_PAD_LEFT);
    // $this->recordNumber = $tempNumber; 
    // $this->recordNumber++;  
    // $content .= "\n"; 
    //$content .= str_pad( $this->recordNumber, 6, " ", STR_PAD_LEFT);
    $content .= str_pad( '', 6, " ", STR_PAD_RIGHT);
    //add payment date to file
    $content .= str_pad( $paymentDate, 7, "0", STR_PAD_LEFT);
    //add customer name to file
    $content .= str_pad( 'UNITED CHURCH S/C ACCOUNT', 30, " ", STR_PAD_RIGHT);
    //add language to file
    $content .= str_pad( 'E', 1, " ", STR_PAD_RIGHT);
    //add reserved character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add client short name to file
    $content .= str_pad( 'UNITED CHURCH', 15, " ", STR_PAD_RIGHT); 
    $content .= str_pad( '', 11, " ", STR_PAD_RIGHT);
    //add optional record to file
    $content .= str_pad( 'N', 1, " ", STR_PAD_RIGHT); 
    $this->recordNumber++;
    return $content;
  }
  
  /**
   * Auxillary method to write the Z record.
   *
   * @access private
   * @return string
   */
  private function _generateZrecord( $debitName = null, $nsfCount = null ) {
    $content = "\n";
    //$tempNumber = 0;
      //add record number to file
      if (!empty( $debitName ) ) {
        $content .= str_pad( $debitName['last_number'], 6, "0", STR_PAD_LEFT);
      } else {
        $content .= str_pad( $this->recordNumber, 6, "0", STR_PAD_LEFT);
        //$tempNumber = $this->recordNumber;
      }
      //add record type to file
      $content .= 'Z';
      //add transaction code to file
      $content .= str_pad( 'TRL', 3, " ", STR_PAD_RIGHT);
      //add client number to file
      $content .= str_pad( $this->account_file_sender['client_number'], 10, " ", STR_PAD_RIGHT );
      //add reserved character to file
      $content .= str_pad( '', 6, " ", STR_PAD_RIGHT);
      //add reserved character to file
      $content .= str_pad( '', 14, " ", STR_PAD_RIGHT);
      //add total number of debit payment transaction to file
      if ( !empty( $debitName ) ) {
        $content .= str_pad( $debitName['last_number'] - 2, 6, "0", STR_PAD_LEFT );
        $content .= str_pad( $debitName['amount']*1000, 13, "0", STR_PAD_LEFT );
      } else {
        $content .= str_pad( $this->recordNumber - 1, 6, "0", STR_PAD_LEFT );
        $content .= str_pad( ($this->credit_sum_amounts - $nsfCount )*1000, 13, "0", STR_PAD_LEFT );
      } 
      //if( !$this->rbcType ){
      // $this->recordNumber = $tempNumber;
      // $this->recordNumber++; 
      //  $content .= "\n";
      //  $content .= str_pad( $this->recordNumber, 6, "0", STR_PAD_LEFT);
      //}
      //add reserved character to file
      $content .= str_pad( '', 2, "0", STR_PAD_RIGHT);
      //add total optional customer info to file
      $content .= str_pad( '', 6, "0", STR_PAD_RIGHT);
      //add filler to file
      $content .= str_pad( '', 12, " ", STR_PAD_RIGHT);
      //add reserved character to file
      $content .= str_pad( '', 6, " ", STR_PAD_RIGHT);
      //add filler to file
      $content .= str_pad( '', 63, " ", STR_PAD_RIGHT);
      //add reserved character to file
      $content .= str_pad( '', 2, " ", STR_PAD_RIGHT);
      //add filler to file
      $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
      return $content;
  }

  /**
   * Auxillary method to write the A record.
   *
   * @access private
   * @return string
   */
  private function _generateDrecord( $transaction ) {
    $content = "\n";
    //add record number to file
    $content .= str_pad( $this->recordNumber, 6, "0", STR_PAD_LEFT);
    //add record type to file
    if( $this->rbcType ){
        $content .= 'D';
    } else {
        $content .= 'C';
    }
    //add transaction code to file
    $content .= str_pad( $transaction['transaction_code'], 3, " ", STR_PAD_RIGHT);
    //add client number to file
    $content .= str_pad( $this->account_file_sender['client_number'], 10, " ", STR_PAD_RIGHT);
    //add filler character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add transaction code to file
    $content .= str_pad( $transaction['customer_number'], 19, " ", STR_PAD_RIGHT);
    //add payment number to file
    $content .= str_pad( $transaction['payment_number'], 2, "0 ", STR_PAD_RIGHT);
    //add bank number to file
    $content .= str_pad( $transaction['bank_number'], 4, "0", STR_PAD_LEFT);
    //add branch number to file
    $content .= str_pad( $transaction['branch_number'], 5, "0", STR_PAD_LEFT);
    //add branch number to file
    if( array_key_exists( 'account_number', $transaction ) ) {
      $accountNumber = str_pad( $transaction['account_number'], 18, " ", STR_PAD_RIGHT);
    } else {
      $accountNumber = "";
    }
    $content .= $accountNumber;
    // $content .= str_pad( $accountNumber, 18, " ", STR_PAD_RIGHT);
    //add filler character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add amount to file
    $content .= str_pad( $transaction['amount']*100, 10, "0", STR_PAD_LEFT);
    //if( !$this->rbcType ){
    // $this->recordNumber++;
    // $content .= "\n";
      //add record number to file
    //$content .= str_pad( $this->recordNumber, 6, " ", STR_PAD_LEFT);
    $content .= str_pad( '', 6, " ", STR_PAD_RIGHT);
    //}
    //add payment date to file
    $content .= str_pad( $transaction['payment_date'], 7, "0", STR_PAD_LEFT);
    //add customer name to file
    $content .= str_pad( $transaction['customer_name'], 30, " ", STR_PAD_RIGHT);
    //add language to file
    $content .= str_pad( $transaction['language'], 1, " ", STR_PAD_RIGHT);
    //add reserved character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add client short name to file
    $content .= str_pad( $transaction['client_short_name'], 15, " ", STR_PAD_RIGHT); 
    //add destination currency to file
    if( array_key_exists( 'destination_cur', $transaction ) ) {
      $destinationCur = $transaction['destination_cur'];
    } else {
      $destinationCur = "";
    }
    $content .= str_pad( $destinationCur, 3, " ", STR_PAD_RIGHT); 
    //add reserved character to file
    $content .= str_pad( '', 1, " ", STR_PAD_RIGHT);
    //add destination country to file
    if( array_key_exists( 'destination_country', $transaction ) ) {
      $destinationCountry = $transaction['destination_country'];
    } else {
      $destinationCountry = "";
    }
    $content .= str_pad( $destinationCountry, 3, " ", STR_PAD_RIGHT); 
    //add filler character to file
    $content .= str_pad( '', 2, " ", STR_PAD_RIGHT);
    //add reserved character to file
    $content .= str_pad( '', 2, " ", STR_PAD_RIGHT);
    //add optional record to file
    $content .= str_pad( $transaction['optional_record'], 1, " ", STR_PAD_RIGHT); 
    $this->recordNumber++;
    return $content;
  }
  /**
   * Auxillary method to write the A record.
   *
   * @access private
   * @return string
   */
  private function _generateArecord() {
    $content  = '$$AAPDSTD0152[PROD[80$$';
    $content .= "\n";
    //add record number to file
    $content .= str_pad( $this->recordNumber, 6, "0", STR_PAD_LEFT);
    //add record type to file
    $content .= 'A';
    //add transaction code to file
    $content .= 'HDR';
    //add client number to file
    $content .= str_pad( $this->account_file_sender['client_number'], 10, " ", STR_PAD_RIGHT);
    //add client name to file
    $content .= str_pad( $this->account_file_sender['client_name'], 30, " ", STR_PAD_RIGHT);
    //add file number to file
    $content .= str_pad( $this->account_file_sender['file_number'], 4, " ", STR_PAD_RIGHT);
    //add file creation date to file
    $content .= str_pad( $this->account_file_sender['file_date'], 7, " ", STR_PAD_RIGHT);
    //add currency type to file
    if( !$this->account_file_sender['currency_type'] ) {
      $this->account_file_sender['currency_type'] = 'CAD';
    }
    $content .= str_pad( $this->account_file_sender['currency_type'], 3, " ", STR_PAD_RIGHT);
    //add input type to file
    $content .= '1'; 
    //if( !$this->rbcType ){
    //   $this->recordNumber++;
    //  $content .= "\n";
    //  //add record number to file
    //$content .= str_pad( $this->recordNumber, 6, " ", STR_PAD_LEFT);
    $content .= str_pad( '', 6, " ", STR_PAD_RIGHT);
    // }
    //add filler character to file
    $content .= str_pad( '', 15, " ", STR_PAD_RIGHT);
    //add reserved character to file
    $content .= str_pad( '', 7, " ", STR_PAD_RIGHT);
    //add reserved character to file
    $content .= str_pad( '', 10, " ", STR_PAD_RIGHT);
    //add filler character to file
    $content .= str_pad( '', 46, " ", STR_PAD_RIGHT);        
    //add filler character to file
    $content .= str_pad( '', 2, " ", STR_PAD_RIGHT);
    //add filler character to file
    $content .= str_pad( 'N', 1, " ", STR_PAD_RIGHT);
    //$this->recordNumber++;
    return $content;
  }
  function readRBCFile( $fileName ) {
    if ( ! is_array( $fileName ) ) {
      CRM_Core_Error::fatal( );
    }
    $fileName = $fileName['name'];
    $fd = fopen( $fileName, "r" );
    if ( ! $fd ) {
      return false;
    }
    $headerRecord = $creditRecord = $debitRecord = $accountTrailerRecord = $clientTrailerRecord = $headerInfo = $debitRecordInfo = $creditRecordInfo = $accountTrailerRecordInfo = $clientTrailerRecordInfo = $error = $errors = array( );
    $debitKey = $creditKey = $total_amount = 0;

    static $isBreak;
    while ( ! feof( $fd ) ) {

      $fixedLength = fgets( $fd );
      $recordType = substr( $fixedLength, 0, 1 );
      switch( $recordType ) {
      case 0:
        if ( empty( $isBreak ) ) {
          $headerRecord = $this->readHeader( $fixedLength );
          $isBreak = true;
        }
        continue;
      case 1:
        $errorMessage = substr( $fixedLength, 26, 1 );
        if ( $errorMessage == 3 ) {
          $debitRecord[$debitKey] = $this->readErrorRecord( $fixedLength );
          $debitKey ++;
        } else {
          $debitRecord[$debitKey] = $this->readDebitRecord( $fixedLength, $error, $errors );
          $total_amount = $total_amount + $debitRecord[$debitKey]['payment_amount'];
          $debitKey ++;
        }
        continue;
      case 2:
        $errorMessage = substr( $fixedLength, 26, 1 );
        if ( $errorMessage == 3 ) {
          $creditRecord[$creditKey] = $this->readErrorRecord( $fixedLength );
          $creditKey ++;
        } else {
          $creditRecord[$creditKey] = $this->readCreditRecord( $fixedLength, $error, $errors );
          $total_amount = $total_amount + $creditRecord[$creditKey]['payment_amount'];
          $creditKey ++;
        }
        continue;
      case 3: 
        $accountTrailerRecord = $this->readAccountTrailerRecord( $fixedLength );
        continue;
      case 4: 
        $clientTrailerRecord = $this->readClientTrailerInputRecord( $fixedLength );
        continue;
      }
      if ( ! $fixedLength ) {
        break;
      }
    } 
    $recordType = array( '0', '1', '2', '3', '4' );
 
    foreach ( $recordType  as $type ) {
      if ( $type == 0 ) {
        $details[$type] = $headerRecord;
      } else if ( $type == 1 ) {
        $details[$type] = $debitRecord;
      } else if ( $type == 2 ) {
        $details[$type] = $creditRecord;
        
      } else if ( $type == 3 ) {
        $details[$type] = $accountTrailerRecord;
      } else if ( $type == 4 ) {
        $details[$type] = $clientTrailerRecord;
      }
    }
    return $details;
    
  }
  
  function readHeader( $header ) {
      
    $headerInfo = array (  
                         'record_type'     => substr( $header, 0, 1 ),
                         'filler_1'        => substr( $header, 1, 3 ),
                         'client_number'   => substr( $header, 4, 10 ), 
                         'filler_2'        => substr( $header, 14, 1 ),
                         'service_type'    => substr( $header, 15, 3 ),
                         'file_number'     => substr( $header, 18, 5 ),
                         'filler_3'        => substr( $header, 23, 2 ),
                         'processing_date' => substr( $header, 25, 8 ),
                         'client_name'     => substr( $header, 33, 10 ),
                         'data_file_type'  => substr( $header, 43, 2 ),
                         'filler_4'        => substr( $header, 45, 145 )
                           );
    return $headerInfo;
  } 

  function readDebitRecord( $debitRecord, $error, $errors ) {
      
    $debitRecordInfo = array (  
                              'record_type'                  => substr( $debitRecord, 0, 1 ), 
                              'filler_1'                     => substr( $debitRecord, 1, 3 ),
                              'client_number'                => substr( $debitRecord, 4, 10 ), 
                              'filler_2'                     => substr( $debitRecord, 14, 1 ),
                              'service_type'                 => substr( $debitRecord, 15, 3 ),
                              'filler_3'                     => substr( $debitRecord, 18, 1 ),
                              'file_number'                  => substr( $debitRecord, 19, 4 ),
                              'filler_4'                     => substr( $debitRecord, 23, 2 ),
                              'payment_type'                 => substr( $debitRecord, 25, 1 ),
                              'debit_deatail_record_type'    => substr( $debitRecord, 26, 1 ),
                              'filler_5'                     => substr( $debitRecord, 27, 11 ), 
                              'customer_number'              => substr( $debitRecord, 38, 19 ),
                              'filler_6'                     => substr( $debitRecord, 57, 6 ),
                              'customer_name'                => substr( $debitRecord, 63, 30 ),
                              'due_date'                     => substr( $debitRecord, 93, 8 ),
                              'filler_7'                     => substr( $debitRecord, 101, 2 ),
                              'payment_number'               => substr( $debitRecord, 103, 2 ),
                              'filler_8'                     => substr( $debitRecord, 105, 5 ),
                              'financial_institution_number' => substr( $debitRecord, 110, 4 ),
                              'branch_number'                => substr( $debitRecord, 114, 5 ),
                              'account_number'               => substr( $debitRecord, 119, 18 ),
                              'currency'                     => substr( $debitRecord, 137, 3 ),
                              'country'                      => substr( $debitRecord, 140, 3 ),
                              'payment_amount'               => substr( $debitRecord, 143, 14 ),
                              'filler_9'                     => substr( $debitRecord, 157, 8 ),
                              'electronic_message'           => substr( $debitRecord, 165, 15 ),
                              'payment_status'               => substr( $debitRecord, 180, 1 ),
                              'transaction_code'             => substr( $debitRecord, 181, 3 ),
                              'filler_10'                    => substr( $debitRecord, 184, 6 ),
                                );
  
    $errors = $this->validate( $debitRecordInfo , $errors );
    if ( !empty( $errors ) ) {
      $debitRecordInfo['is_error'] = $errors;
    }
    return $debitRecordInfo;
  } 

  function readCreditRecord( $creditRecord, $error, $errors  ) {

    $creditRecordInfo = array (  
                               'record_type'                  => substr( $creditRecord, 0, 1 ), 
                               'filler_1'                     => substr( $creditRecord, 1, 3 ),
                               'client_number'                => substr( $creditRecord, 4, 10 ), 
                               'filler_2'                     => substr( $creditRecord, 14, 1 ),
                               'service_type'                 => substr( $creditRecord, 15, 3 ),
                               'filler_3'                     => substr( $creditRecord, 18, 1 ),
                               'file_number'                  => substr( $creditRecord, 19, 4 ),
                               'filler_4'                     => substr( $creditRecord, 23, 2 ),
                               'payment_type'                 => substr( $creditRecord, 25, 1 ),
                               'credit_deatail_record_type'   => substr( $creditRecord, 26, 1 ),
                               'filler_5'                     => substr( $creditRecord, 27, 11 ), 
                               'customer_number'              => substr( $creditRecord, 38, 19 ),
                               'filler_6'                     => substr( $creditRecord, 57, 6 ),
                               'customer_name'                => substr( $creditRecord, 63, 30 ),
                               'due_date'                     => substr( $creditRecord, 93, 8 ),
                               'filler_7'                     => substr( $creditRecord, 101, 2 ),
                               'payment_number'               => substr( $creditRecord, 103, 2 ),
                               'filler_8'                     => substr( $creditRecord, 105, 5 ),
                               'financial_institution_number' => substr( $creditRecord, 110, 4 ),
                               'branch_number'                => substr( $creditRecord, 114, 5 ),
                               'account_number'               => substr( $creditRecord, 119, 18 ),
                               'currency'                     => substr( $creditRecord, 137, 3 ),
                               'country'                      => substr( $creditRecord, 140, 3 ),
                               'payment_amount'               => substr( $creditRecord, 143, 14 ),
                               'filler_9'                     => substr( $creditRecord, 157, 8 ),
                               'electronic_message'           => substr( $creditRecord, 165, 15 ),
                               'payment_status'               => substr( $creditRecord, 180, 1 ),
                               'transaction_code'             => substr( $creditRecord, 181, 3 ),
                               'filler_10'                    => substr( $creditRecord, 184, 6 ),
                                 );
    $errors = $this->validate( $creditRecordInfo, $errors );
    if ( !empty( $errors ) ) {
      $creditRecordInfo['is_error'] = $errors;
    }
    return $creditRecordInfo;
  }
  
  function readErrorRecord( $errorRecord ) {
    $errorRecordInfo = array (  
                              'record_type'          => substr( $errorRecord, 0, 1 ),
                              'filler_1'             => substr( $errorRecord, 1, 3 ),
                              'client_number'        => substr( $errorRecord, 4, 10 ), 
                              'filler_2'             => substr( $errorRecord, 14, 1 ),
                              'service_type'         => substr( $errorRecord, 15, 3 ),
                              'filler_3'             => substr( $errorRecord, 18, 1 ),
                              'file_number'          => substr( $errorRecord, 19, 4 ),
                              'filler_4'             => substr( $errorRecord, 23, 2 ),
                              'payment_type'         => substr( $errorRecord, 25, 1 ),
                              'error_message_record' => substr( $errorRecord, 26, 1 ),
                              'error_code'           => substr( $errorRecord, 27, 4 ),
                              'error_message'        => substr( $errorRecord, 31 , 80 ),
                              'filler_5'             => substr( $errorRecord, 111, 79 ),
                                );
    return $errorRecordInfo;
  } 

  function readAccountTrailerRecord( $accountRecord ) {
    
    $accountTrailerRecordInfo = array (  
                                       'record_type'                    => substr( $accountRecord, 0, 1 ),
                                       'filler_1'                       => substr( $accountRecord, 1, 3 ),
                                       'client_number'                  => substr( $accountRecord, 4, 10 ), 
                                       'filler_2'                       => substr( $accountRecord, 14, 1 ),
                                       'service_type'                   => substr( $accountRecord, 15, 3 ),
                                       'file_number'                    => substr( $accountRecord, 18, 5 ),
                                       'filler_3'                       => substr( $accountRecord, 23, 2 ),
                                       'filler_4'                       => substr( $accountRecord, 25, 52 ),
                                       'filler_5'                       => substr( $accountRecord, 77, 3 ),
                                       'total_number_of_credit_records' => substr( $accountRecord, 80, 8 ),
                                       'total_amount_of_credit_records' => substr( $accountRecord, 88, 18 ),
                                       'filler_6'                       => substr( $accountRecord, 106, 26 ),
                                       'total_number_of_debit_records'  => substr( $accountRecord, 132, 8 ),
                                       'total_amount_of_debit_records'  => substr( $accountRecord, 140, 18 ),
                                       'filler_7'                       => substr( $accountRecord, 158, 2 ),
                                       'clients_return_bank_number'     => substr( $accountRecord, 160, 4 ),
                                       'clients_return_branch_number'   => substr( $accountRecord, 164, 5 ),
                                       'clients_return_account_number'  => substr( $accountRecord, 169, 12 ),
                                       'filler_8'                       => substr( $accountRecord, 181, 9 ),
                                         );
    return $accountTrailerRecordInfo;
  } 
    
  function readClientTrailerRecord( $clientRecord ) {
    $clientTrailerRecordInfo = array (  
                                      'record_type'                           => substr( $clientRecord, 0, 1 ),
                                      'filler_1'                              => substr( $clientRecord, 1, 3 ),
                                      'client_number'                         => substr( $clientRecord, 4, 10 ), 
                                      'filler_2'                              => substr( $clientRecord, 14, 1 ),
                                      'service_type'                          => substr( $clientRecord, 15, 3 ),
                                      'file_number'                           => substr( $clientRecord, 18, 5 ),
                                      'filler_3'                              => substr( $clientRecord, 23, 2 ),
                                      'filler_4'                              => substr( $clientRecord, 25, 52 ),
                                      'filler_5'                              => substr( $clientRecord, 77, 3 ),
                                      'total_number_of_credit_records'        => substr( $clientRecord, 80, 8 ),
                                      'total_amount_of_credit_records'        => substr( $clientRecord, 88, 18 ),
                                      'filler_6'                              => substr( $clientRecord, 106, 26 ),
                                      'total_number_of_debit_records'         => substr( $clientRecord, 132, 8 ),
                                      'total_amount_of_debit_records'         => substr( $clientRecord, 140, 18 ),
                                      'total_number_of_error_message_records' => substr( $clientRecord, 158, 8 ),
                                      'filler_7'                              => substr( $clientRecord, 166, 8 ),
                                      'filler_8'                              => substr( $clientRecord, 174, 8 ),
                                      'foreign_currency_information_records'  => substr( $clientRecord, 182, 8 ),
                                        );
    return $clientTrailerRecordInfo;
  } 
  
  function readClientTrailerInputRecord( $clientInputRecord ) {
    $clientTrailerInputRecordInfo = array (  
                                           'record_type'                      => substr( $clientInputRecord, 0, 1 ),
                                           'filler_1'                         => substr( $clientInputRecord, 1, 3 ),
                                           'client_number'                    => substr( $clientInputRecord, 4, 10 ), 
                                           'filler_2'                         => substr( $clientInputRecord, 14, 1 ),
                                           'service_type'                     => substr( $clientInputRecord, 15, 3 ),
                                           'file_number'                      => substr( $clientInputRecord, 18, 5 ),
                                           'filler_3'                         => substr( $clientInputRecord, 23, 2 ),
                                           'total_number_of_records'          => substr( $clientInputRecord, 25, 8 ),
                                           'total_amount_of_records'          => substr( $clientInputRecord, 33, 18 ),
                                           'total_number_of_valid_records'    => substr( $clientInputRecord, 51, 8 ),
                                           'total_amount_of_valid_records'    => substr( $clientInputRecord, 59, 18 ),
                                           'total_number_of_invalid_records'  => substr( $clientInputRecord, 77, 8 ),
                                           'total_amount_of_invalid_records'  => substr( $clientInputRecord, 84, 18 ),
                                           'total_number_of_returned_records' => substr( $clientInputRecord, 103, 8 ),
                                           'total_amount_of_returned_records' => substr( $clientInputRecord, 111, 18 ),
                                           'total_number_of_reversed_records' => substr( $clientInputRecord, 129, 8 ),
                                           'total_amount_of_reversed_records' => substr( $clientInputRecord, 137, 18 ),
                                           'total_number_of_rejected_records' => substr( $clientInputRecord, 155, 8 ),
                                           'total_amount_of_rejected_records' => substr( $clientInputRecord, 163, 18 ),
                                           'filler_4'                         => substr( $clientInputRecord, 181, 9 ),
                                             );
    return $clientTrailerInputRecordInfo;
  } 
       
  function validate( $params, $errors ) {
    foreach ( $params as $key => $value ) {
      if ( $key == 'client_number' && $value != CLIENT_NUMBER ) {
        $errors['client_number'] = 'Uploaded file has a critical error: the client number is incorrect.';
      } else if ( $key == 'currency' && $value != CURRENCY_TYPE ) {
        $errors['currency'] = 'Uploaded file has a critical error: the currency type is not Canadian.';
      } else if ( $key == 'country' && $value != COUNTRY_NAME ) {
        $errors['country'] = 'Uploaded file has a critical error: the country name is not Canada.';
      }
    }
    return $errors;
  }
}
