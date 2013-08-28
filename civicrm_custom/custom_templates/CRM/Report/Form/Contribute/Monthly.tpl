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
{if $section eq 1}
    <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}
    </div>
{else}
    <div class="crm-block crm-form-block crm-report-field-form-block">
        {include file="CRM/Report/Form/Fields.tpl" componentName='Grant'}
    </div>
    
    <div class="crm-block crm-content-block crm-report-form-block">
        {*include actions*}
        {include file="CRM/Report/Form/Actions.tpl"}

        {*Statistics at the Top of the page*}
        {include file="CRM/Report/Form/Statistics.tpl" top=true}
    
        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}
    
        {*include the table layout*}
        {include file="CRM/Report/Form/Layout/Table.tpl"}    
    	<br />
        <div>
	<table class="report-layout display" style="width:50%; display: inline;">
          {foreach from=$tempTypes key=key item=val}
           <tr>
             <td>{$key}</td>
             <td class="right">{$val|crmMoney}</td>
	   </tr>      
          {/foreach}

           <tr>
	     <td>Grand Total</td>
             <td class="right">{$grandTotal|crmMoney}</td>
           </tr>
       </table>

       <table class="report-layout display" style="width:50%; display: inline; margin-left: 230px;">
           <tr>
	     <td><strong>Grand Total</strong></td>
             <td class="right">{$grandTotal|crmMoney}</td>
           </tr>

           {foreach from=$serviceCharge key=key item=val}
           <tr>
             <td>{$key} : {$val.contributors} X {$val.charge}</td>
             <td class="right">{$val.amount|crmMoney}</td>
           </tr>
           {/foreach}

           <tr>
	     <td>Total Transferred to Church Account(s)</td>
             <td class="right">{$totalTransfered|crmMoney}</td>
           </tr>
       </table>
      </div>
        {include file="CRM/Report/Form/ErrorMessage.tpl"}
    </div>
{/if}