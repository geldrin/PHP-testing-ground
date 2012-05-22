<div class="heading">
  <h1>{#search__all_title#}</h1>
</div>

<div class="form search">
  <form action="{$language}/search/all" method="get">
    <input class="inputtext inputbackground clearonclick" type="text" name="q" data-origval="{#sitewide_search_input#|escape:html}" value="{$smarty.request.q|default:#sitewide_search_input#|escape:html}"/>
    <input class="submitbutton" type="submit" value="{#sitewide_search#}"/>
  </form>
</div>

{if !empty( $items )}
{capture assign=url}{$language}/search/all?q={$searchterm|escape:url}&order=%s{/capture}

<div class="sort">
  <div class="item">
    <a class="title" href="{$url|activesortlink:relevancy_desc:$order}">{#search__all_relevancy#|activesortarrow:relevancy_desc:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':relevancy_desc}">{#search__all_relevancy#|sortarrows:null:relevancy_desc:$order}</a></li>
    </ul>
  </div>
  
  <div class="item">
    <a class="title" href="{$url|activesortlink:recordedtimestamp:$order}">{#search__recordedtimestamp#|activesortarrow:recordedtimestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':recordedtimestamp}">{#search__recordedtimestamp#|sortarrows:null:recordedtimestamp:$order}</a></li>
      <li><a href="{$url|replace:'%s':recordedtimestamp_desc}">{#search__recordedtimestamp_desc#|sortarrows:null:recordedtimestamp_desc:$order}</a></li>
    </ul>
  </div>
</div>
{/if}
