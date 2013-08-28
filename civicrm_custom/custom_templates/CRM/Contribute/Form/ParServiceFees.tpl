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
{* this template is used for adding/editing/deleting contribution type  *}
<h3>{if $action eq 1}{ts}New Par Service Fee{/ts}{elseif $action eq 2}{ts}Edit Par Service Fee{/ts}{else}{ts}Delete Par Service Fees{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-contribution_type-form-block">
   {if $action eq 8}
      <div class="messages status">
          <div class="icon inform-icon"></div>    
           {ts}Deleting a par service fee cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
      </div>
   {else}
     <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
     <table class="form-layout-compressed">
      <tr class="crm-contribution-form-block-label">
 	  <td class="label">{$form.name.label}</td>
	  <td class="html-adjust">{$form.name.html}</td>	
       </tr>
       <tr class="crm-contribution-form-block-per_direct_debit_transaction_fee">	 
    	  <td class="label">{$form.transaction_fee.label}</td>
	  <td class="html-adjust">{$form.transaction_fee.html}</td>
       </tr>
       <tr class="crm-contribution-form-block-direct_debit_transaction_ceiling">
    	  <td class="label">{$form.per_month_ceiling.label}</td>
	  <td class="html-adjust">{$form.per_month_ceiling.html}</td>
       </tr>
       <tr class="crm-contribution-form-block-direct_debit_ceiling_fee">
	  <td class="label">{$form.flat_feet_ransactions_exceeds_ceiling.label}</td>
	  <td class="html-adjust">{$form.flat_feet_ransactions_exceeds_ceiling.html}</td>
       </tr>
       <tr class="crm-contribution-form-block-credit_card_transaction_percentage_fee">	 
          <td class="label">{$form.credit_card_percentage_fee.label}</td>
	  <td class="html-adjust">{$form.credit_card_percentage_fee.html}</td>
       </tr>
      </table> 
   {/if}
   <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="botttom"}</div>
</div>
