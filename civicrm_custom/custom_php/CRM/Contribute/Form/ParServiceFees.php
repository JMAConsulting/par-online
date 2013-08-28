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
require_once 'CRM/Contribute/BAO/ParServiceFees.php';
/**
 * This class generates form components for Par Service Fees
 * 
 */
class CRM_Contribute_Form_ParServiceFees extends CRM_Core_Form
{
    protected $_id     = null;

    protected $_fields = null;


    function preProcess( ) {

        $this->_action     = CRM_Utils_Request::retrieve('action', 'String', $this );
        $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this );
        
        $title = null;
        if ( $this->_action & CRM_Core_Action::UPDATE ) $title = ts('Edit Par Service');
        if ( $this->_action & CRM_Core_Action::DELETE ) $title = ts('Delete Par Service');
        if ( $title ) CRM_Utils_System::setTitle( $title );
        
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext( CRM_Utils_System::url('civicrm/contribute/par/service/fees', 'reset=1') );
        $this->assign( 'action', $this->_action );

        $this->_values = $this->get( 'values' );
        if ( !is_array( $this->_values ) ) {
            $this->_values = array( );
            
            // if we are editing
            if ( isset( $this->_id ) && $this->_id ) {
                $params = array( 'id' => $this->_id );
                CRM_Contribute_BAO_ParServiceFees::retrieve( $params, $this->_values );
            }
            
            //lets use current object session.
            $this->set( 'values', $this->_values );
        }
        
    }
    
    function setDefaultValues( ) 
    {
        $defaults = $this->_values;
        
        if ( !$this->_id ) {
            return $defaults;
        }

        return $defaults;
       
    }
    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( $check = false ) 
    {
        parent::buildQuickForm( );
        
        if ( $this->_action & CRM_Core_Action::DELETE ) {
            
            $this->addButtons( array(
                                     array ( 'type'      => 'next',
                                             'name'      => ts('Delete'),
                                             'isDefault' => true   ),
                                     array ( 'type'      => 'cancel',
                                             'name'      => ts('Cancel') ),
                                     )
                               );
            return;
        }

        $this->applyFilter('__ALL__','trim');

        $attributes = CRM_Core_DAO::getAttribute( 'CRM_Contribute_DAO_ParServiceFees' );

        $this->add( 'text', 'name', ts( 'Rate name' ),
                    $attributes['rate_name'], true );
        
        $this->add( 'text', 'transaction_fee', ts( 'Per direct debittransaction fee' ),
                    $attributes['transaction_fee'], true );
        
        $this->add( 'text', 'per_month_ceiling', ts( 'Per month ceiling on direct debit transactions' ),
                    $attributes['per_month_ceiling'], true );
        
        $this->add( 'text', 'flat_feet_ransactions_exceeds_ceiling', ts( 'Per month flat fee on direct debit transactions when number of transactions exceeds ceiling' ),
                    $attributes['flat_feet_ransactions_exceeds_ceiling'], true );
        
        $this->add( 'text', 'credit_card_percentage_fee', ts( 'Credit card percentage fee' ),
                    $attributes['credit_card_percentage_fee'], true );
        $this->addButtons(array(
                                array ('type'      => 'next',
                                       'name'      => ts('Save'),
                                       'isDefault' => true),
                                array ('type'      => 'cancel',
                                       'name'      => ts('Cancel')),
                                )
                          );

    }

    /**
     * Function to process the form
     *
     * @access public
     * @return None
     */
    public function postProcess() 
    {
        CRM_Utils_System::flushCache( 'CRM_Contribute_DAO_ParServiceFees' );

        if ( $this->_action & CRM_Core_Action::DELETE ) {
            CRM_Contribute_BAO_ParServiceFees::del( $this->_id );
            CRM_Core_Session::setStatus( ts('Selected Par Service has been deleted successfully.') );
            return;
        }

        $values   = $this->controller->exportValues( $this->_name );
        $domainID = CRM_Core_Config::domainID( );

        $result = $this->updateParServiceFees( $values, $domainID );
        if ( $result ) {
            CRM_Core_Session::setStatus( ts( 'Par Service Fee  %1 has been saved.', array( 1 => $result->name ) ) );
            $session = CRM_Core_Session::singleton();
            $session->pushUserContext(CRM_Utils_System::url('civicrm/contribute/par/service/fees', 'reset=1&action=browse&id=' . $result->id ));
        }
        
    }//end of function

    function updateParServiceFees( &$values, $domainID ) {
        $dao = new CRM_Contribute_DAO_ParServiceFees( );
        $dao->id         = $this->_id;
        $dao->domain_id  = $domainID;
        $dao->name                                  = $values['name'];
        $dao->transaction_fee                       = $values['transaction_fee'];
        $dao->per_month_ceiling                     = $values['per_month_ceiling'];
        $dao->flat_feet_ransactions_exceeds_ceiling = $values['flat_feet_ransactions_exceeds_ceiling'];
        $dao->credit_card_percentage_fee            = $values['credit_card_percentage_fee'];
        return $dao->save( );
    }
}


