{include file="Visitor/Email/_header.tpl"}

{newsletter}
<h1>{#users__email_invitation_title#}</h1>
<p>
  {assign var=url value="$BASE_URI$language/users/validateinvite/`$values.id`,`$values.validationcode`"}
  {assign var=inviter value=$user|@nickformat|escape:html}
  {#users__email_invitation_body#|sprintf:$inviter:$url}
</p>
<p>
{#email_linkinfo#}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}