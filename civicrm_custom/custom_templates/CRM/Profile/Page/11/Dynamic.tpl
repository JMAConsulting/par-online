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
{if ! empty( $row )} 
{* wrap in crm-container div so crm styles are used *}
    {if $overlayProfile }
        {include file="CRM/Profile/Page/Overlay.tpl"}
    {else}
        <div id="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
	<table class='pastoral-charge-view' border="0">
	  <tr>
	    <td width=50%>
	    <table>
	      {foreach from=$profileFields item=field key=rowName}
	        <tr>
	      	  <td class="label">{$field.label}</td>
	      	  <td class="content">{$field.value}</td>
	    	</tr>
	      {/foreach}
	    </table>
	    </td>
	    <td width=50%>
	      <table>
	        {foreach from=$relations item=relId key=relValue}
		<tr>
		  <td class="label">{$relId.type}</td>
		  <td class="content">
		    {foreach from=$relId.cid item=cid key=id}
		      <a href={crmURL p="civicrm/contact/view" q="reset=1&cid=`$id`"} > {$cid} </a><br/>
		    {/foreach}
		  </td>
		</tr>
		{/foreach}
	      </table>
	    </td>
	  </tr>	  
	</table>
        </div>
    {/if}
{/if} 
{* fields array is not empty *}

