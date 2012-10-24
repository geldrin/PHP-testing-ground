{if !preg_match('/^failed.*/', $stream.status )}
  {if $stream.status == 'ready'}
    <a href="{$language}/live/togglestream/{$stream.id}?start=1" class="submitbutton">{#live__startrecord#}</a>
  {elseif $stream.status == 'recording'}
    {$l->getLov('streamstatus', $language, $stream.status)}
    <a href="{$language}/live/togglestream/{$stream.id}?start=0" class="submitbutton">{#live__stoprecord#}</a>
  {else}
    {assign var=streamstatus value=$stream.status|default:''}
    {$l->getLov('streamstatus', $language, $streamstatus)}
  {/if}
{else}
  {#live__streamerror#|sprintf:$stream.status}
{/if}
