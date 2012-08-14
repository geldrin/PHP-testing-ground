<div id="headermenu"{if $pagebgclass} class="black"{/if}>
  <nav>
    <ul>
      <li class="{if $module == 'index'}active {/if}first"><a href="{$BASE_URI}">{#sitewide_home#}</a></li>
      <li{if $module == 'categories'} class="active"{/if}><a href="{$language}/categories">{#sitewide_categories#}</a></li>
      <li{if $module == 'live'} class="active"{/if}><a href="{$language}/live">{#sitewide_live#}</a></li>
      <li{if $module == 'channels'} class="active"{/if}><a href="{$language}/channels">{#sitewide_channels#}</a></li>
      {*}
      <li{if $module == 'featured'} class="active"{/if}><a href="{$language}/featured">{#sitewide_featured#}</a></li>
      {/*}
    </ul>
  </nav>
</div>
