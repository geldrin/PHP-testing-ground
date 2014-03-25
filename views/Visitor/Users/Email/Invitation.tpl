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
<h1>{#users__email_invitation_title#|sprintf:$name}</h1>
<p>
  {assign var=url value="$BASE_URI$language/users/validateinvite/`$values.id`,`$values.validationcode`"}

  {$template.prefix|default:#users__templateprefix_default#}
  
  {if !empty( $recording )}
    {capture assign=forward}{$BASE_URI}{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}{/capture}
    <br/>
    <b>{#users__email_invitation_recording#}:</b> <a href="{$url}?forward={$forward|escape:url}">{$recording.title|escape:html}</a>
    <br/>
  {/if}

  {if !empty( $livefeed )}
    {capture assign=forward}{$BASE_URI}{$language}/live/view/{$livefeed.id},{$livefeed.name|filenameize}{/capture}
    <br/>
    <b>{#users__email_invitation_livefeed#}:</b> <a href="{$url}?forward={$forward|escape:url}">{$livefeed.name|escape:html}</a>
    <br/>
  {/if}

  {if !empty( $channel )}
    {capture assign=forward}{$BASE_URI}{$language}/channels/details/{$channel.id},{$channel.title|filenameize}{/capture}
    <br/>
    <b>{#users__email_invitation_channel#}:</b> <a href="{$url}?forward={$forward|escape:url}">{$channel.title|escape:html}</a>
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
    <br/>
  {/if}

  {$template.postfix|default:#users__templatepostfix_default#}

</p>
<p>
{#email_linkinfo#}<br/>
{$url}
</p>
<br/>
<br/>
{/newsletter}

{include file="Visitor/Email/_footer.tpl"}