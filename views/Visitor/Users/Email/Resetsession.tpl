{include file="Visitor/Email/_header.tpl"}

{newsletter}
<h1>{#users__email_resetsession_title#}</h1>
<p>
  {assign var=BASE_URI value=$organization|@uri:base}
  {assign var=url value="$BASE_URI$language/users/validateresetsession/`$values.id`,`$values.validationcode`"}
  {#users__email_resetsession_body#|sprintf:$url}
</p>
<p>
{#email_linkinfo#}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}