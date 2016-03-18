<div id="pagetitle">
  <h1>{#recordings__manageattachments_title#} - {$recording.title|escape:html} <a class="back" href="{$back|escape:html}">{#sitewide_back#|lower}</a></h1>
</div>
<div class="channelgradient"></div>
<br/>

<ul>
  {foreach from=$attachments item=attachment}
    <li>
      {if $attachment.status == 'onstorage'}<a href="{$attachment|@attachmenturl:$recording:$STATIC_URI}">{/if}{$attachment.title|escape:html}{if $attachment.masterextension} ({$attachment.masterextension|escape:html}){/if}{if $attachment.status == 'onstorage'}</a>{/if}
      - <span class="bold"><a href="{$language}/recordings/modifyattachment/{$attachment.id}?forward={$FULL_URI|escape:url}">{#modify#}</a></span>
      {if preg_match( '/^onstorage$|^failed.*$/', $attachment.status )}
        - <span class="bold"><a href="{$language}/recordings/deleteattachment/{$attachment.id}?forward={$FULL_URI|escape:url}" class="confirm delete">{#delete#}</a></span>
      {else}
        - <span class="bold">{#recordings__attachment_waitingforcopy#}</span>
      {/if}
    </li>
  {foreachelse}
    <li>{#recordings__attachment_foreachelse#}</li>
  {/foreach}
</ul>
<br/>
