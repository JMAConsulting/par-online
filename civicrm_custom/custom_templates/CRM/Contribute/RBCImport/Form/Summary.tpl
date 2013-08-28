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
{* Contribution Import Wizard - Step 4 (summary of import results AFTER actual data loading) *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}
<div class="crm-block crm-form-block  crm-contribution-import-summary-form-block id="upload-file">
 {* WizardHeader.tpl provides visual display of steps thru the wizard as well as title for current step *}
 {include file="CRM/common/WizardHeader.tpl"}
 
 <div id="help">
    <p>
    <strong>{ts}Import has completed successfully.{/ts}</strong> {ts}The information below summarizes the results.{/ts}
    </p>
 </div>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>   
 {* Summary of Import Results (record counts) *}

 <table class="report" id="summary-counts">
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
       {* <tr class="error"><td class="label">Rows with Errors.</td>
        <td class="data">{$totalErrors}</td>
	{if $totalErrors eq 0 }
	<td class="explanation">No invalid data records in uploaded file.</td>
	{else}
        <td class="explanation">Rows with invalid data in one or more fields.</td>
	{/if}
    </tr>*}

    <tr><td class="label">{ts}Returned records processed.{/ts}</td>
        <td class="data">{$importedRecords}</td>
        <td class="explanation">{ts}Total number of returned records processed successfully.{/ts}</td>
    </tr>
    <tr><td class="label">{ts}Records not found.{/ts}</td>
        <td class="data">{$notProcessed}</td>
        <td class="explanation">{ts}Records in import file that could not be matched with information in PAR Online.{/ts}</td>
    </tr>
 </tbody></table>
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
 </div>
