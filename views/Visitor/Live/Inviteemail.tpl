{include file="Visitor/Email/_header.tpl"}

{newsletter}
<h1>{#live__inviteemail_title#}</h1>
<p>
  {assign var=BASE_URI value=$organization|@uri:base}
  {#live__inviteemail_body#|sprintf:$pin}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}