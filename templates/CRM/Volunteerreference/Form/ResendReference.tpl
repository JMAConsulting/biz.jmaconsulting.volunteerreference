{* HEADER *}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

<div class="crm-section">
  <div class="content">Are you sure you want to resend the request for a reference to {$Referee}</div>
  <div class="clear"></div>
</div>

{foreach from=$elementNames item=elementName}
  <div class="crm-section editrow-{$elementName}">
    <div class="label">{$form.$elementName.label}</div>
    <div class="content">{$form.$elementName.html}</div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{literal}
<script type="text/javascript">
(function($) {
  $('.editrow-contact_id').hide();
  $('.editrow-custom_field_id').hide();
  $('.editrow-referee_name').hide();
  $('.editrow-referee_email').hide();
})(CRM.$);
</script>
{/literal}
