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
<div class="crm-block crm-contact-task-addtogroup-form-block" id ='crm-container'>
<div class="form-layout form-contact household">
   <div class='label'>{$form.monthly_contact_id.label}</div>
   <div class='content'>{$form.monthly_contact_id.html}</div>
   <div class='label mnth-donate'>{$form.monthly_donation.label}</div>
   <div class='content'>{$form.monthly_donation.html}</div>
   <div id='donations-household'></div>
</div>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>


{literal}
<script>
   var postUrl = {/literal}"{crmURL p='civicrm/contact/view/donations' h=0 q="reset=1&snippet=5&&force=1&tabIndex=1&addNew=&cid="}"{literal}; 	
   postUrl = postUrl + {/literal}{$mainContactId}{literal};      
   cj.ajax({
           type: "GET",
           url: postUrl,
           async: false,
           success: function( response ) {
	     cj('#donations-household').html(response);
	     cj('#donations-household').html(cj('#donations-household #Donation').html());
	     cj('#donations-household #Donation').remove();
	     cj('#donations-household .crm-submit-buttons').remove();
           }});

</script>
{/literal}