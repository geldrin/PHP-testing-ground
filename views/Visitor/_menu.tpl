<div id="headermenu">
  <nav>
    <ul>
      <li class="{if $module == 'index'}active {/if}first"><a href="{$organization|@uri:base}">{#sitewide_home#}</a></li>
      <li{if $module == 'categories'} class="active"{/if}><a href="{$language}/categories">{#sitewide_categories#}</a></li>
      <li{if $module == 'channels'} class="active"{/if}><a href="{$language}/channels/listing">{#sitewide_channels#}</a></li>
      <li{if $module == 'featured'} class="active"{/if}><a href="{$language}/featured">{#sitewide_featured#}</a></li>
    </ul>
  </nav>
</div>
