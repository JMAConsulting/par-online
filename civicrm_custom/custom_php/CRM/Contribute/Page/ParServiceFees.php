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

require_once 'CRM/Core/Page.php';
require_once 'CRM/Contribute/BAO/ParServiceFees.php';


/**
 * Page for displaying list of contribution types
 */
class CRM_Contribute_Page_ParServiceFees extends CRM_Core_Page
{

    protected $_id;
    /**
     * The action links that we need to display for the browse screen
     *
     * @var array
     */
    private static $_links;
    /**
     * Get action Links
     *
     * @return array (reference) of action links
     */
    function &links()
    {
        if (!(self::$_links)) {
            self::$_links = array(
                                  CRM_Core_Action::UPDATE  => array(
                                                                    'name'  => ts('Edit'),
                                                                    'url'   => 'civicrm/contribute/par/service/fees',
                                                                    'qs'    => 'action=update&id=%%id%%&reset=1',
                                                                    'title' => ts('Edit Par Service Fees') 
                                                                   ),
                                  CRM_Core_Action::DELETE  => array(
                                                                    'name'  => ts('Delete'),
                                                                    'url'   => 'civicrm/contribute/par/service/fees',
                                                                    'qs'    => 'action=delete&id=%%id%%',
                                                                    'title' => ts('Delete Par Service Fees') 
                                                                   )
                                 );
        }
        return self::$_links;
    }
    function browse( ) {
        
        $parService = array();
        require_once 'CRM/Contribute/DAO/ParServiceFees.php';
        $dao = new CRM_Contribute_DAO_ParServiceFees();

        $dao->orderBy('name');
        $dao->find();

        while ($dao->fetch()) {
            $parService[$dao->id] = array();
            CRM_Core_DAO::storeValues( $dao, $parService[$dao->id]);
            // form all action links
            $action = array_sum(array_keys($this->links()));

            $parService[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action, 
                                                                                    array('id' => $dao->id));
        }
        $this->assign('rows',$parService );
    }
    
    function run( ) 
    {
        
        $action = CRM_Utils_Request::retrieve('action', 'String',
                                              $this, false, 0 );
        if ($action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
            $this->edit($action);  
        }
        $this->assign('action', $action);
        $this->browse();
        
        return parent::run();
    }

    function edit($action)
    {
        // create a simple controller for editing price data
        $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ParServiceFees', ts(''), $action);

        //set the userContext stack
        $controller->setEmbedded(true);
        $result = $controller->process();
        $result = $controller->run();
    }
    
}
