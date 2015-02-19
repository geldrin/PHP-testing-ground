
<div id="headerlogo">
  {if ($language == 'hu' and $organization.logofilename) or ($language == 'en' and $organization.logofilenameen)}
    <a href="{$BASE_URI}" title="{#sitename#}"><img src="{$STATIC_URI}files/organizations/{$organization.id}.{$language}.png"/></a>
  {else}
    <a href="{$BASE_URI}" title="{#sitename#}" class="basic"><span></span>{#sitename#}</a>
  {/if}
</div>
</div>
<div id="headerbottom">
<div id="headersearch" class="rightbox">
  
  <form action="{$language}/search/all" method="get">
    <input id="headersearchsubmit" type="image" src="{$STATIC_URI}images/header_searchimage.png"/>
    <input class="inputtext inputbackground clearonclick" type="text" name="q" data-origval="{#sitewide_search_input#|escape:html}" value="{$smarty.request.q|default:#sitewide_search_input#|escape:html}"/>
    <a href="{$language}/search/advanced" id="headersearcharrow" title="{#sitewide_search_advanced#}"></a>
  </form>
  <div id="headersearchlink"><a href="{$language}/search/all">{#sitewide_search#}</a></div>
  <div id="languagewrap">
    <div id="languageselector" class="inputbackground right">
      {foreach from=$organization.languages key=languageid item=item}
        {if $languageid == $language}
          <a href="{$FULL_URI|changelanguage:$language}" class="{$language} active">{l lov=headerlanguages key=$language}<span></span></a>
        {/if}
      {/foreach}
      
      <div id="languages">
        {foreach from=$organization.languages key=languageid item=item}
          {if $languageid != $language}
            <a href="{$FULL_URI|changelanguage:$languageid}" class="{$languageid}">{l lov=headerlanguages key=$languageid}</a>
          {/if}
        {/foreach}
      </div>
    </div>
  </div>
</div>
