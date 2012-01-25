<div id="CMSheader">
  <div class="clear"></div>
  <div id="CMStitle">
    <a href="index"><h1>{$bootstrap->config.siteid|escape:html}</h1></a>
    springboard cms<br/>
  </div>
  {if !$bareheading}
    <div id="CMScontrols" class="box">
      <div class="control">
        <a href="javascript:history.go(-1);"><img src="images/icon_back.png" alt=""/><br/>
          vissza
        </a>
      </div>
    </div>
  {/if}
  <div id="CMSdate" class="box">
    {$smarty.now|date_format:"%Y. %B %e., %A"}<br /><span id="CMSclock">{$smarty.now|date_format:"%H:%M:%S"}</span>
  </div>
  {if !$bareheading}
    <div id="CMSuser" class="box">
      <div class="control">
        <a href="mailto:sos@dotsamazing.com?subject=Hiba%3A{$bootstrap->config.siteid|escape:html}"><img src="images/splash_green.png"/><br/>
          hibabejelentés
        </a>
      </div>
      <div class="control">
        <a href="http{if $smarty.const.SSL}s{/if}://{$bootstrap->config.adminuri}index/logout"><img src="images/podcast.png"/><br/>
          kilépés
        </a>
      </div>
    </div>
  {/if}
  <div class="clear"></div>
</div>
