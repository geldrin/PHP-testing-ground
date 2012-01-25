<div id="sideBar">
  <div id="sideBarContents">
    <div id="sideBarContentsInner">
      <a href="index"><h2><span></span>springBoard</h2></a>
      <ul>
        {foreach from=$menu item=item}
          <li><a href="{$item.link|escape:html}">{$item.text|escape:html}</a>
            {if !empty( $item.menu )}
              <ul class="submenu">
                {foreach from=$item.menu item=subitem}
                  <li><a href="{$subitem.link|escape:html}">{$subitem.text|escape:html}</a></li>
                {/foreach}
              </ul>
            {/if}
          </li>
        {/foreach}
      </ul>
    </div>
    <div id="CMScopyright">&copy; <a href="http://www.dotsamazing.com">dotsamazing.com</a></div>
  </div>
  <a href="#" id="sideBarTab"><img src="images/slide-button-left.gif" alt="sideBar" title="sideBar"/></a>
</div>
