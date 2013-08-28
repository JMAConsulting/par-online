<?php
Class CRM_par_export {
  
  public $dbName = NULL;
  public $pass = NULL;
  public $userName = NULL;

  function __construct( ) {  
    // you can run this program either from an apache command, or from the cli
    $this->initialize( );
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
    $this->synchFile = 'civicrm_log_par_donor.txt';
    $this->exportLog = 'export.log';
    $this->dbBackup = "dbBackup";     
    $this->dbName = explode( '?',  $getDBdetails[3]);
    $this->dbName = $this->dbName[0];
    $this->userName = explode( '@', $getDBdetails[2] );
    $this->userName = explode( ':', $this->userName[0] );
    $this->pass = $this->userName[1];
    $this->userName = $this->userName[0];
    $this->flag = FALSE;
    $this->localhost = '10.50.0.30';
  }
  
  public function exportCSV( ) {
    $con = mysql_connect( $this->localhost, $this->userName, $this->pass );
    if (!$con) {
      die('Could not connectss: ' . mysql_error());
    }
    mysql_select_db( "$this->dbName", $con);
    $getTable = "SELECT * FROM civicrm_log_par_donor";
    $table  = mysql_query ( $getTable ) or die ( "Sql error : " . mysql_error( ) );
    $exportCSV  = fopen($this->parOnline2ParPath.$this->synchFile, 'w' );
    
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
}  

$importObj = new CRM_par_export();
$importObj-> exportCSV();
?>