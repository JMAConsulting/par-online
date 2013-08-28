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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Contribute/Form/ParServiceFees.tpl"}
{else}

{if $rows}
<div id="ltype">
<p></p>
    <div class="form-item">
        {strip}
        <table cellpadding="0" cellspacing="0" border="0">
           <thead class="sticky">
            <th>{ts}Rate Name{/ts}</th>
            <th>{ts}Per direct debit transaction fee {/ts}</th>
            <th>{ts}Per month ceiling on direct debit transactions{/ts}</th>
            <th>{ts}Per month flat fee on direct debit transactions when number of transactions exceeds ceiling {/ts}</th>
            <th>{ts}Credit card percentage fee{/ts}</th>
            <th></th>
          </thead>
         {foreach from=$rows item=row}
        <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
	        <td>{$row.name}</td>	
	        <td>{$row.transaction_fee}</td>
            	<td>{$row.per_month_ceiling}</td>
	        <td>{$row.flat_feet_ransactions_exceeds_ceiling}</td>
	        <td>{$row.credit_card_percentage_fee}</td>
	        <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
         </table>
        {/strip}

        {if $action ne 1 and $action ne 2}
	    <div class="action-link">
    	<a href="{crmURL q="action=add&reset=1"}" id="newParService" class="button"><span><div class="icon add-icon"></div>{ts}Add Par Service Fee{/ts}</span></a>
        </div>
        {/if}
    </div>
</div>
{else}
    <div class="messages status">
        <div class="icon inform-icon"></div>
        {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Par Service Fees entered. You can <a href='%1'>add one</a>.{/ts}
    </div>    
{/if}
{/if}