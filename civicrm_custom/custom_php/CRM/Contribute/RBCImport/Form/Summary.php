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
require_once 'CRM/Contribute/RBCImport/Parser.php';

/**
 * This class summarizes the import results
 */
class CRM_Contribute_RBCImport_Form_Summary extends CRM_Core_Form 
{

    /**
     * Function to set variables up before form is built
     *
     * @return void
     * @access public
     */
    public function preProcess( ) 
    {
        $totalRecords = $this->get( 'totalRecords' );
        $importedRecords = $this->get( 'importedRecords' );
        $errorMessageRecords = $this->get( 'errorMessageRecords' );
        $debitMessageRecords = $this->get( 'debitMessageRecords' );
        $creditMessageRecords = $this->get( 'creditMessageRecords' );
        $notProcessed = $this->get( 'notProcessed' );
        $recordsWithErrorMessages = $this->get( 'recordsWithErrorMessages' );
        $this->assign( 'errorRecords', count( $recordsWithErrorMessages ) );
        $this->assign( 'notProcessed', $notProcessed );
        $this->assign( 'errorMessageRecords', $errorMessageRecords );
        $this->assign( 'debitMessageRecords', $debitMessageRecords );
        $this->assign( 'creditMessageRecords', $creditMessageRecords );
        $this->assign( 'importedRecords', $importedRecords );
        $this->assign( 'totalRecords', $totalRecords );
    }
    
    /**
     * Function to actually build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        $this->addButtons( array(
                                 array ( 'type'      => 'next',
                                         'name'      => ts('Done'),
                                         'isDefault' => true   ),
                                 )
                           );
    }

    /**
     * Return a descriptive name for the page, used in wizard header
     *
     * @return string
     * @access public
     */
    public function getTitle( ) 
    {
        return ts('Summary');
    }

}


