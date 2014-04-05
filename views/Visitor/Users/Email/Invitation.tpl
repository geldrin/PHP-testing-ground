{include file="Visitor/Email/_header.tpl"}
{*}
// ezek egyetlen rekordok (1dimenzios tomb)
$recording
$livefeed
$channel
$template -- a prefix/postfix mezok htmlt tartalmaznak, nem kell oket escapelni
$user -- meghivo fel
$values -- a meghivo maga
// ezek tombok amiknek ertekei a rekordok (2dimenzios tomb)
$permissions -- ertekei a mar lov-bol atforditott permissionok
$groups
$departments
{/*}

{newsletter}
{assign var=name value=$values.name|default:#users__email_invitation_namedefault#}
<h1>{#users__email_invitation_title#|sprintf:$name},</h1>

  {assign var=url value="$BASE_URI$language/users/validateinvite/`$values.id`,`$values.validationcode`"}

  {$template.prefix|default:#users__templateprefix_default#}

<table border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td style="width: 40px; background-color:'#e0e0e0';" bgcolor="#e0e0e0"></td>
    <td style="background-color:'#e0e0e0';" bgcolor="#e0e0e0">
      {if !empty( $recording )}
        {capture assign=forward}{$BASE_URI}{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}{/capture}
        <b>{#users__email_invitation_recording#}:</b><br/>
        <a href="{$url}?forward={$forward|escape:url}">{$recording.title|escape:html}{if $recording.subtitle|stringempty} - <i>{$recording.subtitle|escape:html}</i>{/if}</a>
        <br/>
      {/if}

      {if !empty( $livefeed )}
        {capture assign=forward}{$BASE_URI}{$language}/live/view/{$livefeed.id},{$livefeed.name|filenameize}{/capture}
        <b>{#users__email_invitation_livefeed#}:</b><br/>
        {$livefeed.channel.title|escape:html}: <a href="{$url}?forward={$forward|escape:url}">{$livefeed.name|escape:html}</a>
        <br/>
      {/if}

      {if !empty( $channel )}
        {capture assign=forward}{$BASE_URI}{$language}/channels/details/{$channel.id},{$channel.title|filenameize}{/capture}
        <b>{#users__email_invitation_channel#}:</b><br/>
        <a href="{$url}?forward={$forward|escape:url}">{$channel.title|escape:html}</a>
        <br/>
      {/if}

      {if !empty( $permissions )}
        <br/>
        <b>{#users__email_invitation_permissions#}:</b>
        {foreach from=$permissions item=item name=permission}
          {$item|escape:html}{if !$smarty.foreach.permission.last},{/if}
        {/foreach}
        <br/>
      {/if}

      {if !empty( $groups )}
        <br/>
        <b>{#users__email_invitation_groups#}:</b><br/>
        {foreach from=$groups item=item name=group}
          {$item.name|escape:html}{if !$smarty.foreach.group.last}<br/>{/if}
        {/foreach}
        <br/>
      {/if}

      {if !empty( $departments )}
        <br/>
        <b>{#users__email_invitation_departments#}:</b><br/>
        {foreach from=$departments item=item name=department}
          {$item.name|escape:html}{if !$smarty.foreach.department.last}<br/>{/if}
        {/foreach}
      {/if}
      <br/>
    <td style="width: 40px; background-color:'#e0e0e0';" bgcolor="#e0e0e0"></td>
  </tr>
</table>

{$template.postfix|default:#users__templatepostfix_default#}

<p>
{#email_linkinfo#}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}