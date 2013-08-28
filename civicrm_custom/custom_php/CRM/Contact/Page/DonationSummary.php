<?php
require_once 'CRM/Core/Page.php';
class CRM_Contact_Page_DonationSummary extends CRM_Core_Page {
  function run() {
    if( array_key_exists( 'gid', $_GET ) ){
      $groupId = $_GET[ 'gid' ];
    }
    if ( !empty( $_GET[ 'id' ] ) ) {
      $cid     = $_GET[ 'id' ];
    }
    $summary = array();
    if( empty( $_GET[ 'id' ] ) ){
      $cid = $_SESSION['CiviCRM']['userID'];
    }
    require_once 'CRM/Contribute/BAO/ContributionType.php';
    //$cid = 13479;
    if( $cid ){
      $query = "SELECT related_id, contact_sub_type FROM custom_relatedContacts
           LEFT JOIN civicrm_contact ON civicrm_contact.id = custom_relatedContacts.related_id WHERE contact_id = {$cid}";
      $dao    =  CRM_Core_DAO::executeQuery( $query );
 
      while( $dao->fetch() ){
        if( $dao->contact_sub_type  ){
          $cids[ $dao->related_id ] = $dao->related_id;
        }
      }
      $summary = CRM_Contact_Page_DonationSummary::getSummary( $cid, 'month');
      $summary = CRM_Contact_Page_DonationSummary::getSummary( $cid, 'monthAnticipated', $summary);
      $summary = CRM_Contact_Page_DonationSummary::getSummary( $cid, 'year', $summary);
      $summary = CRM_Contact_Page_DonationSummary::getSummaryAverage( $cid, $summary );
    }
    $smarty =  CRM_Core_Smarty::singleton( );
        
    require_once 'CRM/Contact/BAO/GroupContact.php';
    if ( CRM_Contact_BAO_GroupContact::isContactInGroup( $_SESSION['CiviCRM']['userID'] , PAR_ADMIN_GROUP_ID ) ) {
      $url = CRM_Utils_System::url('civicrm/contact/search/custom',"reset=1&csid=18&force=1");
    }
    if ( CRM_Contact_BAO_GroupContact::isContactInGroup( $_SESSION['CiviCRM']['userID'] , DENOMINATION_ADMIN_GROUP_ID ) ) {
      $url = CRM_Utils_System::url('civicrm/contact/search/custom',"reset=1&csid=16&force=1");
    } 
    $mtd = CRM_Utils_System::url('civicrm/report/instance/40',"force=1");
    $mAnti = CRM_Utils_System::url('civicrm/report/instance/39',"force=1");
    $ytd = CRM_Utils_System::url('civicrm/report/instance/38',"force=1");
        
    if ( !empty( $url ) ) {
      $smarty->_tpl_vars['url'] = $url;
    }    
    if ( !empty( $mtd  ) ) {
      $smarty->_tpl_vars['mtd'] = $mtd;
    }    
    if ( !empty( $ytd ) ) {
      $smarty->_tpl_vars['ytd'] = $ytd;
    }
    if ( !empty( $mAnti ) ) {
      $smarty->_tpl_vars['mAnti'] = $mAnti;
    }
    if( $summary ){
      $smarty->_tpl_vars['thisYear']  = $summary['year'];
      $smarty->_tpl_vars['thisMonth'] = $summary['month'];
      $smarty->_tpl_vars['thisMonthAnticipated'] = $summary['monthAnticipated'];
    }
    parent::run();
  }
    
  static function getSummary( $cid, $duration, $summary = NULL) {
    civicrm_initialize();
    if (!CRM_Utils_Array::value($duration ,$summary)) {
      $summary[$duration] = array(
        'General' => 0,
        'M&S' => 0,
        'Other' => 0
      );
    }
    $total   = 0;
    if( $duration == 'month' ){
      $todaysDay = date('d',time());
      if( $todaysDay >= 20 ){
        $startDate = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 20, date( 'Y', time() ) ) );
        $endDate   = date( 'Y-m-d',strtotime("$startDate -1 day +1 month"));
      } else {
        $endDate   = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 19, date( 'Y', time() ) ) );
        $startDate = date( 'Y-m-d',strtotime("$endDate +1 day -1 month"));
      }
      $query = "SELECT cct.label, SUM(cct.line_total) sm FROM custom_relatedContacts crc
INNER JOIN civicrm_contact cc ON cc.id = crc.related_id 
INNER JOIN civicrm_relationship org_supporter ON org_supporter.contact_id_b = crc.related_id
INNER JOIN civicrm_contribution donation ON org_supporter.contact_id_a = donation.contact_id
INNER JOIN civicrm_line_item cct ON cct.entity_id = donation.id
WHERE crc.contact_id = ".$cid."
AND org_supporter.relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID."
AND donation.id IS NOT NULL
AND org_supporter.is_active = 1
AND donation.receive_date BETWEEN '$startDate' AND '$endDate'
AND donation.contribution_status_id = 1
AND cc.is_deleted != 1
AND donation.is_test = 0
GROUP BY cct.label";
    } 
    elseif ($duration == 'monthAnticipated') {
      $query = "SELECT cct.label, SUM(cct.line_total) sm FROM custom_relatedContacts crc
INNER JOIN civicrm_contact cc ON cc.id = crc.related_id 
INNER JOIN civicrm_relationship org_supporter ON org_supporter.contact_id_b = crc.related_id
INNER JOIN civicrm_contribution donation ON org_supporter.contact_id_a = donation.contact_id
INNER JOIN civicrm_line_item cct ON cct.entity_id = donation.id
WHERE crc.contact_id = ".$cid."
AND org_supporter.relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID."
AND donation.id IS NOT NULL
AND org_supporter.is_active = 1
AND donation.contribution_status_id = 5
AND cc.is_deleted != 1
AND donation.is_test = 0
GROUP BY cct.label";
    } 
    else {
      $query = "SELECT cct.label, SUM(cct.line_total) sm FROM custom_relatedContacts crc
INNER JOIN civicrm_contact cc ON cc.id = crc.related_id 
INNER JOIN civicrm_relationship org_supporter ON org_supporter.contact_id_b = crc.related_id
INNER JOIN civicrm_contribution donation ON org_supporter.contact_id_a = donation.contact_id
INNER JOIN civicrm_line_item cct ON cct.entity_id = donation.id
WHERE crc.contact_id = ".$cid."
AND org_supporter.relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID."
AND donation.id IS NOT NULL
AND org_supporter.is_active = 1
AND YEAR( donation.receive_date ) = YEAR(NOW())
AND donation.contribution_status_id = 1
AND cc.is_deleted != 1
AND donation.is_test = 0
GROUP BY cct.label";
    }
    $dao = CRM_Core_DAO::executeQuery( $query );
    while( $dao->fetch() ){
      $summary[$duration][$dao->label] = $dao->sm;
      $total += $dao->sm;
    }
    $summary[$duration]['total'] = $total;
    $summary[$duration]['avg'] = $total;
    return $summary;
  }

  function getTemplateFileName() {
    $templateFile = "CRM/Contact/Page/DonationSummary.tpl";
    $template     = CRM_Core_Page::getTemplate( );
    if ( $template->template_exists( $templateFile ) ) {
      return $templateFile;
    }
  }
    
  static function getSummaryAverage( $cid, $summary ) { 
    //  foreach ( $cids as $id ) {
    $todaysDay = date('d',time());
    if( $todaysDay >= 20 ){
      $startDate = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 20, date( 'Y', time() ) ) );
      $endDate   = date( 'Y-m-d',strtotime("$startDate -1 day +1 month"));
    } else {
      $endDate   = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm', time() ), 19, date( 'Y', time() ) ) );
      $startDate = date( 'Y-m-d',strtotime("$endDate +1 day -1 month"));
    }
      
    $query = "SELECT count(DISTINCT(donation.contact_id)) count FROM custom_relatedContacts crc
INNER JOIN civicrm_contact cc ON cc.id = crc.related_id 
INNER JOIN civicrm_relationship org_supporter ON org_supporter.contact_id_b = crc.related_id
INNER JOIN civicrm_contribution donation ON org_supporter.contact_id_a = donation.contact_id
WHERE crc.contact_id = ".$cid." 
AND org_supporter.relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID."
AND donation.id IS NOT NULL
AND org_supporter.is_active = 1
AND donation.receive_date BETWEEN '$startDate' AND '$endDate'
AND donation.contribution_status_id = 1
AND cc.is_deleted != 1
AND donation.is_test = 0";
    $mcount = CRM_Core_DAO::singleValueQuery( $query );

    $query = "SELECT count(DISTINCT(donation.contact_id)) count FROM custom_relatedContacts crc
INNER JOIN civicrm_contact cc ON cc.id = crc.related_id 
INNER JOIN civicrm_relationship org_supporter ON org_supporter.contact_id_b = crc.related_id
INNER JOIN civicrm_contribution donation ON org_supporter.contact_id_a = donation.contact_id
WHERE crc.contact_id = ".$cid." 
AND org_supporter.relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID."
AND donation.id IS NOT NULL
AND org_supporter.is_active = 1
AND donation.contribution_status_id = 5
AND cc.is_deleted != 1
AND donation.is_test = 0";

    $acount = CRM_Core_DAO::singleValueQuery( $query );
    $query = "SELECT count(DISTINCT(donation.contact_id)) count FROM custom_relatedContacts crc
INNER JOIN civicrm_contact cc ON cc.id = crc.related_id 
INNER JOIN civicrm_relationship org_supporter ON org_supporter.contact_id_b = crc.related_id
INNER JOIN civicrm_contribution donation ON org_supporter.contact_id_a = donation.contact_id
WHERE crc.contact_id = ".$cid." 
AND org_supporter.relationship_type_id = ".SUPPORTER_RELATION_TYPE_ID."
AND donation.id IS NOT NULL
AND org_supporter.is_active = 1
AND YEAR( donation.receive_date ) = YEAR(NOW())
AND donation.contribution_status_id = 1
AND cc.is_deleted != 1
AND donation.is_test = 0";
      
    $ycount = CRM_Core_DAO::singleValueQuery( $query );
    if ( !empty($summary['month']['avg']) ) {
      $summary['month']['avg'] = $summary['month']['avg']/$mcount;
    }
    if ( !empty($summary['monthAnticipated']['avg']) ) {
      $summary['monthAnticipated']['avg'] = $summary['monthAnticipated']['avg']/$acount;
    }
    if ( !empty($summary['year']['avg']) ) {
      $summary['year']['avg'] = $summary['year']['avg']/$ycount;
    }
    return $summary;
  }
}
