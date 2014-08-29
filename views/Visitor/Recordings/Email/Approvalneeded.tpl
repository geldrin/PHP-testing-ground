{include file="Visitor/Email/_header.tpl"}

{newsletter}
{assign var=name value=$user|@nickformat}
{capture assign=url}{$BASE_URI}{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}{/capture}
{capture assign=modifyurl}{$BASE_URI}{$language}/recordings/modifysharing/{$recording.id},{$recording.title|filenameize}{/capture}
<h1>{#recordings__approvalstatus_title#|sprintf:$name},</h1>
<p>
  {#recordings__approvalstatus_body#|sprintf:$name:$url:$recording.title:$modifyurl}
</p>
<p>
{#email_linkinfo#}<br/>
{$url}<br/>
{$modifyurl}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}