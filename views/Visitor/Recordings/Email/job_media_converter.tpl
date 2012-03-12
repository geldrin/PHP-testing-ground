{include file="Visitor/Email/_header.tpl"}

{newsletter}

<h1>{#email_conversiondone_title#}</h1>
<p>
  {assign var=playurl value="$BASE_URI$language/recordings/details/$recid"}
  {assign var=editurl value="$BASE_URI$language/recordings/modifybasics/$recid"}
  {l module=recordings key=email_conversiondone_body|sprintf:$filename:$playurl:$playurl:$editurl:$editurl}
</p>

<br /><br />

<p>
{l module=recordings key=email_conversion_signature}
<a href="mailto:support@teleconnect.hu">support@teleconnect.hu</a><br /><br />
<a style="font-size: 15px;" href="http://video.teleconnect.hu/" target="_blank">http://video.teleconnect.hu/</a>
</p>

{/newsletter}

{include file="Visitor/Email/_footer.tpl"}
