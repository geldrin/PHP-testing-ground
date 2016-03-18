{include file="Visitor/Email/_header.tpl"}

{newsletter}
<h1>{#users__email_validation_title#}</h1>
<p>
  {assign var=BASE_URI value=$organization|@uri:base}
  {assign var=url value="$BASE_URI$language/users/validate/`$values.id`,`$values.validationcode`"}
  {if $invitationid}
    {assign var=url value=$url|cat:",`$invitationid`"}
  {/if}
  {if $forwardurl}
    {assign var=forwardurl value=$forwardurl|escape:url}
    {assign var=url value=$url|cat:"?forward=`$forwardurl`"}
  {/if}
  {#users__email_validation_body#|sprintf:$url}
</p>
<p>
{#email_linkinfo#}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}