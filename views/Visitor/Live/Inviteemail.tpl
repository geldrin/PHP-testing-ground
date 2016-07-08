{include file="Visitor/Email/_header.tpl"}

{newsletter}
{if $user}
{assign var=name value=$user|@nickformat|escape:html}
{assign var=title value=#live__inviteemail_title#}
{assign var=title value=$title|sprintf:$name}
{else}
{assign var=title value=#users__templatetitle_default#}
{/if}
{assign var=supportemail value=$organization.supportemail|default:$organization.name|escape:html}
{assign var=event value=$channel.title|escape:html}
{assign var=feedname value=$feed.name|escape:html}
<h1>{$title}</h1>
<p>
  {#live__inviteemail_body#|sprintf:$event:$feedname:$pin:$supportemail:$supportemail}
</p>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}