<div id="headermenu">
  <nav>
    <ul>
      <li class="{if $module == 'index'}active {/if}first"><a href="{$organization|@uri:base}">{l key=sitewide_home}</a></li>
      <li{if $module == 'categories'} class="active"{/if}><a href="{$language}/categories">{l key=sitewide_categories}</a></li>
      <li{if $module == 'channels'} class="active"{/if}><a href="{$language}/channels/listing">{l key=sitewide_channels}</a></li>
      <li{if $module == 'featured'} class="active"{/if}><a href="{$language}/featured">{l key=sitewide_featured}</a></li>
    </ul>
  </nav>
</div>
