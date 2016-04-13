{if !preg_match('/^failed.*/', $feed.status )}
  {if $feed.status == 'ready'}
    <a href="{$language}/live/togglestream/{$feed.id}?start=1" class="submitbutton">{#live__startrecord#}</a>
  {elseif $feed.status == 'recording'}
    {$l->getLov('streamstatus', $language, $feed.status)}
    <a href="{$language}/live/togglestream/{$feed.id}?start=0" class="submitbutton">{#live__stoprecord#}</a>
  {else}
    {assign var=streamstatus value=$feed.status|default:''}
    {$l->getLov('streamstatus', $language, $feedstatus, '')}
  {/if}
{else}
  {#live__streamerror#|sprintf:$feed.status}
{/if}
