<?php
require_once 'importExport.php';
Class CRM_par_export extends CRM_par_ImportExport {

  function __construct( ) { 
    parent::__construct();
  }
}  

$exportObj = new CRM_par_export();
$exportObj->startProcess();
$exportObj->exportCSV();
$exportObj->createActivity( 
  PAROnline2PAR_ACTIVITY_TYPE_ID,
  'PAR Online to PAR Legacy Export', 
  '', 
  TRUE 
);

?>