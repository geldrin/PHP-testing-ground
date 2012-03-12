{include file="Visitor/Email/_header.tpl"}

{newsletter}
<h1>{l module=users key=email_invitation_title}</h1>
<p>
  {assign var=BASE_URI value=$organization|@uri:base}
  {assign var=url value="$BASE_URI$language/users/validateinvite/`$values.id`,`$values.validationcode`"}
  {l module=users key=email_invitation_body sprintf=$url}
</p>
<p>
{l module=users key=email_linkinfo}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}