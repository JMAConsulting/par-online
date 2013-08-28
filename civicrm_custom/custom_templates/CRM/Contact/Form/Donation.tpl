<div>
<span></span>
</div>
<div id="dialog" title="Error Message">
<p>During the transition to Online PAR, direct debit donations have to be recorded with the head of household. Would you like to update the donations for this contact's head of household?</p>
</div>
{if $status}
    <div class="message status"><div class="icon inform-icon"></div> {$status}</div>
{/if}
<table id="donation_summary">
    <tr>
        <th colspan = 3><h3>Pre-Authorized Payment Details</h3></th>
    </tr>
    <tr>
	<td colspan = 3>
	   <table id='payment-details' >
	       <tr>
	           <th>Status</th>
	           <th>Payment Instrument</th>
		   <th class='dd'>Bank #</th>
		   <th class='dd'>Branch #</th>
		   <th class='dd'>Account #</th>
		   <th class='cc'>Credit Card #</th>
		   <th class='cc'>Expires on</th>
		   <th class='cc'>CAVV</th>
	       </tr>
	       <tr id='instrument-deatils'>
	           <td width='130px'>
		       {$form.payment_status.html}
		       <span style='display:none'>{$form.contribution_type.html}</span>
		       <span style='display:none'>{$form.frequency_unit.html}</span>
		       <span style='display:none'>{$form.contribution_id.html}</span>
		   </td>
	           <td>
		       {$form.payment_instrument.html}
		       {$form.cc_type.html}
		   </td>
		   <td class='dd'>{$form.bank.html}</td>
		   <td class='dd'>{$form.branch.html}</td>
		   <td class='dd'>{$form.account.html}</td>
		   <td class='cc'>{$form.cc_number.html}</td>
		   <td class='cc'>{$form.cc_expire.html}</td>
		   <td class='cc'>{$form.cavv.html}</td>
	       </tr>
	   </table>
	</td>
    </tr>
    <tr>
        <td colspan = 2><h3>Pre-Authorized Amounts</h3></td>
    </tr>
    <tr>
        <th width = 130px>Fund</th>
	<th>Amount</th>
    </tr>
    <tr>
	<td colspan = 2 id = 'priceset-details'>
	    {include file="CRM/Price/Form/PriceSet.tpl" extends="Contribution"}
	</td>
    </tr>
    <!--{foreach from=$fieldList item=value key=id}
    {assign var="field" value=$value.name|cat:"_"|cat:$id}
        <tr id={$field} class={$value.name}>
	    <td width='130px'>{$form.$field.label}</td>
	    <td>{$form.$field.html}</td>
	</tr>
    {/foreach}-->
</table>
    <div class="crm-submit-buttons"> 
         {include file="CRM/common/formButtons.tpl"}{if $isDuplicate}<span class="crm-button">{$form._qf_Edit_upload_duplicate.html}</span>{/if}
    </div>
{literal}
<script type="text/javascript">
function showHideCC( obj ){
  if( cj(obj).val() != 1 ) {
    var id = cj(obj).attr( 'id' );
    id = id.replace( /payment_instrument/i, 'cc_type' );
    cj("#"+id).hide();
    cj(".cc").hide();
    cj(".dd").show();
  } else {
    var id = cj(obj).attr( 'id' );
    id = id.replace( /payment_instrument/i, 'cc_type' );
    cj("#"+id).show();
    cj(".cc").show();
    cj(".dd").hide();
  }
}

cj(document).ready(function(){
    showHideCC( cj( ".payment_instrument" ) );
    cj( ".payment_instrument" ).change(function(){
        showHideCC(this);
    });
});

cj('#dialog').hide();
cj( '#_qf_Donation_save' ).click( function(){
   var parentID = {/literal}{if $householdExt neq ''}{$householdExt}{else}0{/if}{literal};
   if (parentID && cj('.payment_instrument').val() == 6) {
   var url = {/literal}"{crmURL p='civicrm/contact/view' h=0 q=reset=1}"{literal};
   cj('#dialog').dialog({
		width         : 500,
		height        : 200,
		resizable     : false,
		bgiframe      : true,
		draggable     : true,
		closeOnEscape : false,
		overlay       : { opacity: 0.5, background: "black" },
		buttons: { 
		  "YES": function() { 
		    cj(this).dialog("close"); 
		 {/literal}{if $householdExt neq ''}{literal}   url = url + '&cid='+{/literal}{$householdExt}{/if}{literal};
		    cj(location).attr('href',url);
		  },
		  "CANCEL": function() { 	 
		    cj(this).dialog("close"); 
		    url = url + '&cid='+{/literal}{$cid}{literal};
		    cj(location).attr('href',url);
		  }
		} 
	});
    return false;	
    }	
    prepareContributionParams();
    cj("#mainTabContainer").tabs( "load" , {/literal}{$tabIndex}{literal} );
});

function prepareContributionParams(){
    var data = [];
    
    data = 'payment_instrument='+cj('#payment_instrument').val()+'&';
    data = data+'payment_status='+cj('#payment_status').val()+'&';
    data = data+'frequency_unit='+cj('#frequency_unit').val()+'&';
    data = data+'old_status='+cj('#old_status').val()+'&';
    data = data+'pricesetid='+cj('#pricesetid').val()+'&';
    cj('#instrument-deatils td input').each( function(){
         var id = cj(this).attr('id');
	 var value = cj(this).val( );
         data = data+id+'='+value+'&';           
    } );
    cj('#instrument-deatils td select').each( function(){
         var id = cj(this).attr('id');
	 var value = cj(this).val( );
         data = data+id+'='+value+'&';           
    } );

    cj('#priceset-details #priceset .crm-section .content input').each( function(){
         var id = cj(this).attr('id');
	 var value = cj(this).val( );
         data = data+id+'='+value+'&';    			  
    } );
    var dataURL = {/literal}"{crmURL p='civicrm/contact/add/new/contribution' h=0 q='snippet=4'}"{literal};
    dataURL = dataURL + '&cid='+{/literal}{$cid}{literal};
    cj.ajax({
      "url":   dataURL,
      "type": "POST", 
      "data":  data,
      "async"    : false,
      "dataType": 'json',
      "success": function(html){
        console.debug(html);
      }
    });
}
</script>
{/literal}
