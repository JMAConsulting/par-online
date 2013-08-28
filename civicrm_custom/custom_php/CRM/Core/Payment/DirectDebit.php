<?php
require_once 'CRM/Core/Payment.php';
class CRM_Core_Payment_DirectDebit extends CRM_Core_Payment {
    static protected $_mode = null;

    static protected $_params = array();
    /**
     * We only need one instance of this object. So we use the singleton
     * pattern and cache the instance in this variable
     *
     * @var object
     * @static
     */
    static private $_singleton = null;
    function __construct( $mode, &$paymentProcessor ) {
        $this->_mode             = $mode;
        $this->_paymentProcessor = $paymentProcessor;
        $this->_processorName    = ts('Direct Debit');
    }
    
    static function &singleton( $mode, &$paymentProcessor ) {
        $processorName = $paymentProcessor['name'];
        if (self::$_singleton[$processorName] === null ) {
            self::$_singleton[$processorName] = new CRM_Core_Payment_DirectDebit( $mode, $paymentProcessor );
        }
        return self::$_singleton[$processorName];
    }
    
    function doDirectPayment ( &$params ) {
        $cookedParams = $params; // no translation in Dummy processor
        CRM_Utils_Hook::alterPaymentProcessorParams( $this,
                                                     $params,
                                                     $cookedParams );
        //end of hook invokation
        
    }

    function checkConfig( ) {
        return null;
    }
    
    function changeContributionState( $params ) {
        require_once 'api/api.php';
        $result = false;
        require_once 'CRM/Core/Transaction.php';
        $transaction = new CRM_Core_Transaction( );
        $params[ 'id' ] = $params[ 'contribution_id' ];
        switch ($params['contribution_status_id']) {
            case 1: // completed
                $params[ 'receipt_date' ] = date( 'YmdHis' );
                $result = self::completeTransaction( $transaction, $params );
                break;
            case 3: // canceled
                $params[ 'cancel_date' ] =  date( 'YmdHis' );
                $result = self::canceled( $transaction, $params );
                break;
            case 4: // failed
                $result = self::failed( $transaction, $params );
                break;
            case 5: // in_progress
                $result = self::in_progress( $transaction, $params );
                break;
            default:
                // todo: errormessage? (unsupported contribution status)
                $result = false;
        }
        return $result;  
    }
    
    function completeTransaction( &$transaction, $params ) {
        require_once 'CRM/Utils/Array.php';
        $result = civicrm_api( 'contribution', 'create', $params );
        if( civicrm_api3_error( $result ) ) {
            return $result;
        }

        $trxnParams = array(
                            'contribution_id'   => CRM_Utils_Array::value( 'contribution_id', $params ),
                            'trxn_date'         => date('YmdHis'), // now
                            'trxn_type'         => 'Debit',
                            'total_amount'      => CRM_Utils_Array::value( 'total_amount', $params ),
                            'fee_amount'        => CRM_Utils_Array::value( 'fee_amount', $params ),
                            'net_amount'        => CRM_Utils_Array::value( 'net_amount', $params ),
                            'currency'          => CRM_Utils_Array::value( 'currency', $params ),
                            'trxn_id'           => CRM_Utils_Array::value( 'trxn_id', $params ),
                            );
        if ( CRM_Utils_Array::value( 'trxn_id', $params ) ) {
            require_once 'CRM/Core/BAO/FinancialTrxn.php';
            $trxn =& CRM_Core_BAO_FinancialTrxn::create( $trxnParams );
            $transaction->commit(); 
        }
        return TRUE;
    }
    
    function failed( &$transaction, $params ) {
        $result = civicrm_api( 'contribution', 'create', $params );
        if( civicrm_api3_error( $result ) ) {
            return $result;
        }
        $transaction->commit( );
        CRM_Core_Error::debug_log_message( "Setting contribution status to failed" );
        return true;
    }

    function canceled( &$transaction, $params ) {
        $result = civicrm_api( 'contribution', 'create', $params );
        if( civicrm_api3_error( $result ) ) {
            return $result;
        }
        $transaction->commit( );
        CRM_Core_Error::debug_log_message( "Setting contribution status to canceled" );
        return true;
    }
    
    function in_progress( &$transaction, $params ) {
        require_once 'api/api.php';
        $result = civicrm_api( 'contribution', 'create', $params );
        if( civicrm_api3_error( $result ) ) {
            return $result;
        }
        $transaction->commit( );
        CRM_Core_Error::debug_log_message( "Setting contribution status to in progress" );
        return true;
    }
}