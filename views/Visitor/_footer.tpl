    </div>{*}body div{/*}
    <div id="footer">
      
      <div id="logocontainer">
        <a href="{$BASE_URI}" id="footerlogo"><span></span>{l key=sitename}</a>
      </div>

      <div id="copyrightcontainer">
        <p>
          {assign var=currentyear value=$smarty.now|date_format:"%Y"}
          <a href="http://www.niif.hu"><b>{l key=footer_copyright sprintf=$currentyear}</b></a><br/>
          {l key=footer_allrights}<br />
        </p>
      </div>
      <div id="logos">
        <a href="http://www.nfu.hu" id="ujmagyarlogo"><span></span>{l key=footer_ujmagyarorszag}</a>
        <a href="http://www.niif.hu" id="niiflogo"><span></span>{l key=footer_niif}</a>
        <a href="http://www.nfu.hu" id="tamoplogo"><span></span>{l key=footer_tamop}</a>
      </div>
      <div id="footerlist">
        <p>
          &nbsp;
        </p>
      </div>
      <ul id="contactlink">
        {capture assign="contactblock"}admin@teleconnect.hu{/capture}
        <li id="footercontactlink">{$contactblock}</li>
        <li id="footerenablemobile"><a href="{$language}/index/mobile?status=enable&forward={$FULL_URI|escape:url}">{l key=footer_enablemobile}</a></li>
        <li id="footeruserstos"><a href="{$language}/contents/userstos">{l key=footer_userstos}</a></li>
        <li id="footerrecordingstos"><a href="{$language}/contents/recordingstos">{l key=footer_recordingstos}</a></li>
      </ul>
      {if $browserIsMobile}
        <ul id="mobilefooterlist">
          <li id="disablemobile">
            <a href="{$language}/index/mobile?status=disable&forward={$FULL_URI|escape:url}">{l key=footer_disablemobile}</a>
          </li>
          <li id="changelanguage">
            {if $language=='hu'}
              <a href="{$FULL_URI}">{l key=sitewide_menu_inenglish}</a>
            {else}
              <a href="{$FULL_URI}">{l key=sitewide_menu_inhungarian}</a>
            {/if}
          </li>
          <li id="mobilelogin">
            <a href="{$language}/index/mobile?status=disable&forward={"users/login"|escape:url}">{l key=sitewide_login}</a>
          </li>
        </ul>
      {/if}
    </div>
  </div>{*}wrap div{/*}
</div>{*}pagecontainer div{/*}

<div id="footerbg">
</div>

{if $bootstrap->debug and false}{debug}{/if}
{if $smarty.const.PRODUCTION}
{literal}
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-16658651-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
{/literal}
{/if}
</body>
</html>