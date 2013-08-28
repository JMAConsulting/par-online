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

require_once 'CRM/Contribute/DAO/ParServiceFees.php';

/**
 * This class contains  par service fees related functions.
 */
class CRM_Contribute_BAO_ParServiceFees extends CRM_Contribute_DAO_ParServiceFees 
{
    /**
     * class constructor
     */
    function __construct( ) 
    {
        parent::__construct( );
    }

    /**
     * Takes a bunch of params that are needed to match certain criteria and
     * retrieves the relevant objects. It also stores all the retrieved
     * values in the default array
     *
     * @param array $params   (reference ) an assoc array of name/value pairs
     * @param array $defaults (reference ) an assoc array to hold the flattened values
     *
     * @return object CRM_Contribute_DAO_ParServiceFees object on success, null otherwise
     * @access public
     * @static
     */
    static function retrieve( &$params, &$defaults ) 
    {
        $parServiceFees = new CRM_Contribute_DAO_ParServiceFees( );
        $parServiceFees->copyValues( $params );
        if ( $parServiceFees->find( true ) ) {
            CRM_Core_DAO::storeValues( $parServiceFees, $defaults );
            return $parServiceFees;
        }
        return null;
    }
    
    /**
     * Function  to delete Par Service Fee
     * 
     * @param  int  $parServiceFeesID     ID of the par service fee to be deleted.
     * 
     * @access public
     * @static
     */
    static function del( $parServiceFeesID ) {
        if ( ! $parServiceFeesID ) {
            CRM_Core_Error::fatal( ts( 'Invalid value passed to delete function' ) );
        }

        $dao            = new CRM_Contribute_DAO_ParServiceFees( );
        $dao->id        =  $parServiceFeesID;
        if ( ! $dao->find( true ) ) {
            return null;
        }
        $dao->delete( );
    }

}
