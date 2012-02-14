{include file="Visitor/Email/_header.tpl"}

{newsletter}
<h1>{l module=users key=email_changepassword_title}</h1>
<p>
  {assign var=url value="$BASE_URI$language/users/changepassword/`$values.id`,`$values.validationcode`"}
  {l module=users key=email_changepassword_body sprintf=$url}
</p>
<p>
{l module=users key=email_linkinfo}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}