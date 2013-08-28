{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
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
<div class="crm-block crm-form-block crm-contact-task-addtogroup-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
<div class="form-layout form-contact">
    <div class="label-txt">{$text}</div>    
    {if $rows}
      <div class="label-txt bba">Bank Branch Account, etc.</div>
      <table>
        <thead>
	  <th></th>
	  <th>Contact Name</th>
	  <th>Bank #</th>
	  <th>Branch</th>
	  <th>Account Number</th>
	  <th>CC Type</th></thead>
	<tbody>
	  {foreach from=$rows key=idx item=row}
	   {assign var="timeElement" value="contact_id"}
            <tr class="{cycle values="odd-row,even-row"}">
            <td>{$form.$timeElement.$idx.html}</td>
            <td>{$row.contact_name}</td>
            <td>{$row.bank_name}</td>
            <td>{$row.branch_name}</td>
            <td>{$row.account_number}</td>
            <td>{$row.cc_type}</td>
	    </tr>
	  {/foreach}
	</tbody>
      </table>
    {/if}
</div>
<table class="form-layout">
   
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
{literal}
<script type="text/javascript">
cj(document).ready(function() {
  cj('.form-radio').click(function(){
    var selectedId = cj(this).val();
    var data = 'contact={/literal}{$houseHold}{literal}';
    var data = data + '&selectedId='+ selectedId;
    var dataURL = {/literal}"{crmURL p='civicrm/contact/household/contacts'}"{literal};
    cj.ajax({ 
      url: dataURL,	
      data: data,
      type: 'POST',
      success: function(output) { 
        setTimeout("",1500);
      }
    });
  });
});
</script>
{/literal}

