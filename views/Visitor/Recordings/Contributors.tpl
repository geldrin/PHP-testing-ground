{foreach from=$contributors key=key item=contributor name=contrib}
  {assign var=prev value=false}
  {assign var=next value=false}
  {assign var=prevkey value=$key-1}
  {assign var=nextkey value=$key+1}
  {if isset( $contributors[$nextkey] )}
    {assign var=next value=$contributors[$nextkey].id}
  {/if}
  {if isset( $contributors[$prevkey] )}
    {assign var=prev value=$contributors[$prevkey].id}
  {/if}
  <li>
    <div class="actions">
      {if $prev}
        <a href="{$language}/recordings/swapcontributor?what={$contributor.id}&where={$prev}" class="move ui-state-default ui-corner-all" title="{#contributors__moveprev#}"><span class="ui-icon ui-icon-triangle-1-n"></span></a>
      {else}
        <div class="iconplaceholder"></div>
      {/if}
      {if $next}
        <a href="{$language}/recordings/swapcontributor?what={$contributor.id}&where={$next}" class="move ui-state-default ui-corner-all" title="{#contributors__movenext#}"><span class="ui-icon ui-icon-triangle-1-s"></span></a>
      {else}
        <div class="iconplaceholder"></div>
      {/if}
      <a href="{$language}/contributors/modify/{$contributor.contributorid}?crid={$contributor.id}" class="edit ui-state-default ui-corner-all" title="{#edit#}"><span class="ui-icon ui-icon-gear"></span</a>
      <a href="{$language}/recordings/deletecontributor/{$contributor.id}" class="delete ui-state-default ui-corner-all" title="{#delete#}"><span class="ui-icon ui-icon-trash"></span></a>
    </div>
    {$contributor|@nameformat|escape:html} - {$contributor.rolename|escape:html}
  </li>
{/foreach}