{include file="Visitor/Email/_header.tpl"}

{newsletter}
{assign var=name value=$replyuser|@nickformat|default:#users__email_invitation_namedefault#}
{capture assign=url}{$BASE_URI}{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}{/capture}
{assign var=commenturl value="`$url`#comment-`$comment.id`"}
{capture assign=commentusername}{$commentuser|@nickformat|escape:html}{/capture}
<h1>{#users__email_invitation_title#|sprintf:$name},</h1>
<p>
  {#recordings__comments_reply_body#|sprintf:$commenturl}
</p>
<blockquote>
  {$commentusername}<br/>
  {$comment|@commentlinkify:$url}
</blockquote>
<p>
{#email_linkinfo#}<br/>
{$commenturl}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}