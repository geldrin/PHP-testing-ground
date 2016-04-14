{if !preg_match('/^failed.*/', $feed.status )}
  {if $feed.status == 'ready'}
    <a href="{$language}/live/togglefeed/{$feed.id}?start=1" class="submitbutton">{#live__startrecord#}</a>
  {elseif $feed.status == 'recording'}
    {$l->getLov('feedstatus', $language, $feed.status)}
    <a href="{$language}/live/togglefeed/{$feed.id}?start=0" class="submitbutton">{#live__stoprecord#}</a>
  {else}
    {assign var=feedstatus value=$feed.status|default:''}
    {$l->getLov('feedstatus', $language, $feedstatus, '')}
  {/if}
{else}
  {#live__feederror#|sprintf:$feed.status}
{/if}
