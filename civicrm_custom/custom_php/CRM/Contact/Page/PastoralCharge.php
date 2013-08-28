<?php
require_once 'CRM/Profile/Page/View.php';
class CRM_Contact_Page_PastoralCharge extends CRM_Profile_Page_View {
    function run(){
        require_once 'CRM/Contact/Page/CommunicationSummary.php';
        require_once 'CRM/Contact/Page/DonationSummary.php';
        parent::run();
    }
}