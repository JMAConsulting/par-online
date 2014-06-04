<?php
require_once 'importExport.php';
Class CRM_par_export extends CRM_par_ImportExport {

  function __construct( ) { 
    parent::__construct();
  }
}  

$exportObj = new CRM_par_export();
$exportObj->isMonthlySync = CRM_Utils_Array::value(1, $argv);
if ($exportObj->monthlySync) {
  $details = 'EXPORT FAILED SINCE :Only one sync can run at a time';
  $attachFile = FALSE;
}
else {
  $exportObj->startProcess();
  $exportObj->exportCSV();
  $details = '';
  $attachFile = TRUE;
}
$exportObj->createActivity( 
  PAROnline2PAR_ACTIVITY_TYPE_ID,
  'PAR Online to PAR Legacy Export', 
  $details, 
  $attachFile
);

?>