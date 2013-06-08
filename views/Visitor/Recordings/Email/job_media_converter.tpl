{include file="Visitor/Email/_header.tpl"}

{newsletter}

<h1>{#recordings__email_conversiondone_title#}</h1>
<p>
  {assign var=BASE_URI value=$organization|@uri:base}
  {assign var=playurl value="http://$domain/$language/recordings/details/$recid"}
  {assign var=editurl value="http://$domain/$language/recordings/modifybasics/$recid"}
  {#recordings__email_conversiondone_body#|sprintf:$filename:$playurl:$playurl:$editurl:$editurl}
</p>

<br /><br />

<p>
{#recordings__email_conversion_signature#}
<a href="mailto:{$supportemail}">{$supportemail}</a><br /><br />
<a href="http://{$domain}/" target="_blank">http://{$domain}/</a>
</p>

{/newsletter}

{include file="Visitor/Email/_footer.tpl"}
