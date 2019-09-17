{* HEADER *}

<div class="crm-section">
  <div class="label">To:</div>
  <div class="content">{$reference}</div>
  <div class="clear">{$volunteer}</div>
</div>

<div class="content description-text">
  <p>
  {ts}The above-mentioned applicant has applied for a volunteer position at the Canadian Association for Girls In Science (CAGIS), and has given your name as a reference. Would you please complete this form and return it to us as soon as possible. Please be assured that all information will be held in strictest confidence.
Thank you for your assistance.{/ts}
  </p>
</div>

{assign var="groupID" value="27"}
<div class="crm-public-form-item crm-section reference">
  {include file="CRM/UF/Form/Block.tpl" fields=$reference}
</div>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
