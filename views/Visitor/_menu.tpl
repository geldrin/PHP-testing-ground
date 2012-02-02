<div id="menu">
  <div class="wrap">
    <ul class="left">
      <li class="{if $module == 'index'}active {/if}first"><a href="{$BASE_URI}">{l key=sitewide_home}</a></li>
      <li{if $module == 'categories'} class="active"{/if}><a href="{$language}/categories">{l key=sitewide_categories}</a></li>
      <li{if $module == 'recordings'} class="active"{/if}><a href="{$language}/recordings">{l key=sitewide_recordings}</a></li>
      <li{if $module == 'featured'} class="active"{/if}><a href="{$language}/featured">{l key=sitewide_featured}</a></li>
    </ul>
  </div>
</div>