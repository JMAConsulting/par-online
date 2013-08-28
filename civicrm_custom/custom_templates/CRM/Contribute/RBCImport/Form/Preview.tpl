{*
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
*}
{* Contribution Import Wizard - Step 2 (map incoming data fields) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
{if $errors}
<div class="messages crm-error">
  <div class="icon red-icon alert-icon"></div>
  Please correct the following errors in the uploaded file:	   
  <ul id="errorList">
  {$errors}
  </ul>
</div>
{/if}
<div class="crm-block crm-form-block crm-contribution-import-form-block id="upload-file">
 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}


<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
<table class="report" id="preview-counts">
    <tbody><tr><td class="label">Total Transactions.</td>
        <td class="data">{$totalRecords}</td>
        <td class="explanation">Total transactions (RBC records) in uploaded file.</td>
    </tr>
    <tr><td class="label">Error Records.</td>
    <td class="data">{$errorRecords}</td>
    <td class="explanation">Total error records in uploaded file.</td>
    </tr>
    <tr><td class="label">Error Message Records.</td>
    <td class="data">{$errorMessageRecords}</td>
    <td class="explanation">Total error message records in uploaded file.</td>
    </tr>
    <tr><td class="label">Debit Records.</td>
    <td class="data">{$debitMessageRecords}</td>
    <td class="explanation">Total debit records in uploaded file.</td>
    </tr>
    <tr><td class="label">Credit Records.</td>
    <td class="data">{$creditMessageRecords}</td>
    <td class="explanation">Total credit records in uploaded file.</td>
    </tr>
       {* <tr class="error"><td class="label">Rows with Errors</td>
        <td class="data">{$totalErrors}</td>
	{if $totalErrors eq 0 }
	<td class="explanation">No invalid data records in uploaded file.</td>
	{else}
        <td class="explanation">Rows with invalid data in one or more fields.</td>
	{/if}
    </tr>*}


    <tr><td class="label">Valid Transactions.</td>
        <td class="data">{$validRecords}</td>
        <td class="explanation">Valid rows in uploaded file.</td>
    </tr>
 </tbody></table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 {$initHideBoxes}
</div>
{literal}
<script type="text/javascript" >
if ( document.getElementsByName("saveMapping")[0].checked ) {
    document.getElementsByName("updateMapping")[0].checked = true;
    document.getElementsByName("saveMapping")[0].checked = false;
} 
</script>
{/literal}