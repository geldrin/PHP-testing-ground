{if !empty( $navigation )}
  <ul id="navigation">
    {foreach from=$navigation item=item}
      <li><a href="{$item.link|escape:html}">{if $item.icon}<img src="{$item.icon|escape:html}" class="icon"/>{/if}{$item.key|escape:html}</a></li>
    {/foreach}
  </ul>
{/if}
