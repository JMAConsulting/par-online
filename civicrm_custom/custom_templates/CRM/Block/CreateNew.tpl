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
<div class="block-civicrm">
<div id="crm-create-new-wrapper">
	<div id="crm-create-new-link"><span><div class="icon dropdown-icon"></div>{ts}Main Actions{/ts}</span></div>
		<div id="crm-create-new-list" class="ac_results">
			<div class="crm-create-new-list-inner">
			<ul>
			{foreach from=$shortCuts item=short}
				    <li><a href="{$short.url}" class="crm-{$short.ref}">{$short.title}</a></li>
			    {/foreach}
			</ul>
			</div>
		</div>
	</div>
</div>
<div id = 'sync-dialog'></div>
<div class='clear'></div>
{literal}
<script>

cj('body').click(function() {
	 	cj('#crm-create-new-list').hide();
	 	});
	
	 cj('#crm-create-new-list').click(function(event){
	     event.stopPropagation();
	 	});

cj('#crm-create-new-list li').hover(
	function(){ cj(this).addClass('ac_over');},
	function(){ cj(this).removeClass('ac_over');}
	);

cj('#crm-create-new-link').click(function(event) {
	cj('#crm-create-new-list').toggle();
	event.stopPropagation();
	});
cj('#crm-create-new-list a').click(function(event){ 
  if (cj(this).hasClass('crm-montly-sync-legacy') || cj(this).hasClass('crm-sync-legacy')) {
   var isMonthlySync = 0;
   var diaText = '';
   if (cj(this).hasClass('crm-montly-sync-legacy')) {
     isMonthlySync = 1;
     cj("#sync-dialog").html('The monthly synchronization between PAR Legacy and PAR Online will take many hours. PAR Online will not be available for use by Local PAR Admins until a subsequent Sync from Legacy occurs. Please confirm that you would like to proceed with the monthly synchronization.');
   }
   else {
     cj("#sync-dialog").html('The Sync from Legacy will take many hours. During this period Local PAR Admins will not be able to access PAR Online. Please confirm that you would like to proceed with the Sync from Legacy');   	
   }
   var url = {/literal}"{crmURL p='civicrm/legacysync' h=0 q='reset=1'}"{literal} + '&isMonthlySync=' + isMonthlySync;
   var logoutUrl = {/literal}"{crmURL p='civicrm/logout' h=0 q='reset=1'}"{literal};
   cj('#sync-dialog').dialog({
     width : 500,
     height : 200,
     resizable : false,
     bgiframe : true,
     draggable : true,
     closeOnEscape : false,
     overlay : { opacity: 0.5, background: "black" },
     buttons: { 
       "OK": function() {
         cj.ajax({
           type: "POST",
           url: url,
           dataType: "json",
           success: function( response ) {
           }
         });
         cj(this).dialog("close");
     	 cj("#sync-dialog").html('The Super Admin may log back in but should not change any data until a Synch from Legacy has been completed.');  
	 cj('#sync-dialog').dialog({
     	   width : 500,
     	   height : 200,
     	   resizable : false,
    	   bgiframe : true,
    	   draggable : true,
   	   closeOnEscape : false,
     	   overlay : { opacity: 0.5, background: "black" },
     	   buttons: { 
       	   "OK": function() {
             cj(this).dialog("close");
	     window.location.href = logoutUrl;
       	   }
          } 
         });
       },
       "CANCEL": function() { 	 
	 cj(this).dialog("close"); 
       }
      } 
    });
    return false;	
 }
});

</script>

{/literal}
