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
	{assign var="relationCount" value=$relationCount}
	  <table class='individual-profile'>
            {foreach from=$profileFields item=field key=rowName}
	    {assign var="relationCount" value=$relationCount-1}	
               <tr>
	         <td class="label" width = "25%">{$field.label}{$count}</td>
		 <td class="content" width = "25%">{$field.value}</td>
		 {if $relationCount >= 0}
		 <td class="label" width = "25%">{$relation.$relationCount.type}</td>
		 <td class="content" width = "25%">{$relation.$relationCount.name}</td>
		 {else}
		 <td></td>
	 	 <td></td>
		 {/if}
	       </tr>  
            {/foreach}
	  </table>
        </div>
    {/if}
{/if} 
{* fields array is not empty *}
<div id='contact-dialog-1'></div>
 {literal}
  <script type="text/javascript">
  
  function createHousehold(gid, cid) {
    var dataURL = {/literal}"{crmURL p='civicrm/profile/edit' q="reset=1&snippet=5&context=dialog&blockNo=1" h=0 }"{literal};
    dataURL = dataURL + '&gid=' + gid + '&id=' + cid;
    cj.ajax({
      url: dataURL,
      success: function( content ) {
        cj( '#contact-dialog-1').show( ).html( content ).dialog({
          title: "{/literal}{ts escape='js'}Banking Details{/ts}{literal}",
          modal: true,
          width: 680,
          overlay: {
            opacity: 0.9,
            background: "black"
          },

          close: function(event, ui) {
	    window.location.href = {/literal}"{crmURL p='civicrm/contact/view?' q="reset=1&cid=" h=0 }"{literal} + cid;
          }
        });
      }
    });
    return false;
  }

  </script>
  {/literal}