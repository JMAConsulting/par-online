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
<div id="help" style="width: 48%;">{ts}Use this import of Moneris monthly donation data just before enabling a new PAR Charge to use PAR Online.
<br>
This import should be done <b>AFTER</b> the monthly synchronization has taken PAR Online offline and completed the initial synchronization, and <b>BEFORE</b> the synchronization of data from RBC and PAR Legacy brings PAR Online back online.{/ts}</div>
<div class='clear'>&nbsp;</div>
<table class="form-layout">
  <tr>
    <td class="label">{$form.ms_number.label}</td>
    <td>{$form.ms_number.html} 
    </td>
  </tr>
  <tr>
    <td class="label">{$form.uploadFile.label}</td>
    <td>{$form.uploadFile.html}<br />
      <div class="description">{ts}File format must be comma-separated-values (CSV). File must be UTF8 encoded if it contains special characters (e.g. accented letters, etc.).{/ts}</div>
      {ts 1=$uploadSize}Maximum Upload File Size: %1 MB{/ts}
    </td>
  </tr>
  <tr>
    <td></td>
    <td>{$form.skipColumnHeader.html} {$form.skipColumnHeader.label}
      <div class="description">{ts}Check this box if the first row of your file consists of field names (Example: 'Customer id','Order id','Transaction Name'){/ts}</div>
    </td>
  </tr>
</table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"} 
<span class="action-link"><a class="button" href="{crmURL p='civicrm' q='reset=1'}">Cancel</a></span></div> 