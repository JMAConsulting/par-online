<?php
Class CRM_par_ImportExport {

  public $dbName = NULL;
  public $pass = NULL;
  public $userName = NULL;
  public $monthlySync = FALSE;
  public $isMonthlySync = FALSE;

  function __construct() {
    // you can run this program either from an apache command, or from the cli
    $this->initialize();
  }

  function initialize( ) {
    $path = explode('sites', getcwd());
    $this->root_path = $path[0];
    require_once $this->root_path.'sites/all/modules/civicrm/civicrm.config.php';
    require_once $this->root_path.'sites/all/modules/civicrm/CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();
    $getDBdetails = explode( '/',  $config->dsn);
    $this->par2parOnlinePath = $this->root_path.'sites/default/files/PAR2PAROnline/';
    $this->parOnline2ParPath = $this->root_path.'sites/default/files/PAROnline2PAR/';
    $this->newDirectory = date('Ymd_His');
    $this->accountFile = 'par_charge_accounts.csv';
    $this->donorFile = 'par_donor.csv';
    $this->localAdminFile = 'par_local_admin.csv';
    $this->organizationFile = 'par_organization.csv';
    $this->transactionFile = 'par_donor_transactions.csv';
    $this->transactionNSFFile = 'par_donor_transactions_nsf.csv';
    $this->synchFile = 'civicrm_log_par_donor.txt';
    $this->notImportedNSF = 'notImportedDonorNsfData.csv';
    $this->notImportedOrg = 'notImportedOrganizations.csv';
    $this->notImportedAdmin = 'notImportedAdmin.csv';
    $this->notImportedDonor = 'notImportedDonor.csv';
    $this->notImportedCharge = 'notImportedCharge.csv';
    $this->notImportedTransactions = 'notImportTransactions.csv';
    $this->notUpdatedTransactions = 'notUpdatedTransactions.csv';
    $this->error = array( 0 => "Error Reason");
    $this->importLog = 'import.log';
    $this->dbBackup = "dbBackup";
    $this->dbName = explode( '?',  $getDBdetails[3]);
    $this->dbName = $this->dbName[0];
    $this->userName = explode( '@', $getDBdetails[2] );
    $this->userName = explode( ':', $this->userName[0] );
    $this->pass = $this->userName[1];
    $this->userName = $this->userName[0];
    $this->flag = FALSE;
    $this->localhost = '10.50.0.30';
    $this->monthlySync = CRM_Core_DAO::singleValueQuery('SELECT value FROM civicrm_option_value WHERE id = 824');
  }

  public function exportCSV() {
    require_once 'CRM/Contribute/PseudoConstant.php';
    mkdir($this->parOnline2ParPath . $this->newDirectory, 01777);
    $con = mysql_connect($this->localhost, $this->userName, $this->pass);
    if (!$con) {
      die('Could not connectss: ' . mysql_error());
    }
    mysql_select_db("$this->dbName", $con);
    $getTable = "SELECT  clpd.*, IFNULL(activated__48,0) is_online_par, NULL par_donor_owner_id
      FROM civicrm_log_par_donor clpd
      LEFT JOIN civicrm_value_is_online_17 cv ON cv.entity_id = clpd.primary_contact_id";
    $table  = mysql_query ($getTable) or die ("Sql error : " . mysql_error());
    $exportCSV  = fopen($this->parOnline2ParPath . $this->newDirectory . '/' . $this->synchFile, 'w');
    $parOwnerId = array();
    $dao = CRM_Core_DAO::executeQuery("SELECT cr.is_active, clpd.primary_contact_id, REPLACE(civicrm_contact.external_identifier, 'O-', '') par_donor_owner_id
      FROM civicrm_log_par_donor clpd
      LEFT JOIN civicrm_relationship cr ON cr.contact_id_a = clpd.primary_contact_id
      LEFT JOIN civicrm_contact ON civicrm_contact.id = cr.contact_id_b
      WHERE cr.relationship_type_id = " . SUPPORTER_RELATION_TYPE_ID);
    while ($dao->fetch()) {
      if (array_key_exists($dao->primary_contact_id, $parOwnerId)
        && $parOwnerId[$dao->primary_contact_id]['is_active']) {
        continue;
      }
      $parOwnerId[$dao->primary_contact_id] = array(
        'par_donor_owner_id' => $dao->par_donor_owner_id,
        'is_active' => $dao->is_active,
      );
    }
    // fetch a row and write the column names out to the file
    $row = mysql_fetch_assoc($table);
    $line = "";
    $comma = "";
    foreach($row as $name => $value) {
      $line .= $comma . '"' . str_replace('"', '""', $name) . '"';
      $comma = "\t";
    }
    $line .= "\n";
    fputs($exportCSV, $line);

    // remove the result pointer back to the start
    mysql_data_seek($table, 0);
    // and loop through the actual data
    while($row = mysql_fetch_assoc($table)) {
      if (array_key_exists($row['primary_contact_id'], $parOwnerId)) {
        $row['par_donor_owner_id'] = $parOwnerId[$row['primary_contact_id']]['par_donor_owner_id'];
      }
      if ($row['log_action'] != 'Delete') {
        $statusID = CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution_recur 
          WHERE contact_id = {$row['primary_contact_id']} ORDER BY id DESC LIMIT 1");
        
        if ($statusID 
          && CRM_Contribute_PseudoConstant::contributionStatus($statusID) == 'Stopped') {
          continue;
        }
      }
      $line = "";
      $comma = "";
      foreach($row as $value) {
        $line .= $comma . '"' . str_replace('"', '""', $value) . '"';
        $comma = "\t";
      }
      $line .= "\n";
      fputs($exportCSV, $line);
    }
    fclose($exportCSV);
  }

  function startProcess() {
    define('DRUPAL_ROOT',$this->root_path);
    include_once DRUPAL_ROOT.'includes/bootstrap.inc';
    drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
    if ($this->isMonthlySync) {
      $result = db_query("SELECT value FROM `variable` WHERE  name = 'maintenance_mode'");
      if (unserialize($result->fetchField())) {
        return;
      }
      $var = db_query("update variable set value = 'i:1;' where name = 'maintenance_mode'")->execute();
    }
    cache_clear_all('variables', 'cache_bootstrap');
  }

  function createActivity($activityTypeID, $subject, $description, $attachFile = FALSE) {
    require_once('CRM/Contact/BAO/Group.php');
    $params = array(
      'source_contact_id' => 1,
      'activity_type_id' => $activityTypeID,
      'assignee_contact_id' => array_keys(CRM_Contact_BAO_Group::getGroupContacts(SYSTEM_ADMIN)),
      'subject' => $subject,
      'details' => $description,
      'activity_date_time' => date('Y-m-d H:i:s'),
      'status_id' => 2,
      'priority_id' => 2,
      'version' => 3,
    );
    if ($attachFile && isset($attachFile)) {
      $newFileName = 'civicrm_log_par_donor_' . md5(date('YmdHis')) . '.txt';

      $newDirectory = $this->parOnline2ParPath . '/' . $this->newDirectory . '/';
      copy($newDirectory . $this->synchFile, $newDirectory . $newFileName);
      $params['attachFile_1'] = array(
        'uri' => $newDirectory . $newFileName,
        'type' => 'text/csv',
        'location' => $newDirectory . $newFileName,
        'upload_date' => date('YmdHis'),
      );
    }
    civicrm_api('activity', 'create', $params);
  }
}
?>

