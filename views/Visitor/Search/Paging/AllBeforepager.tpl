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
{capture assign=url}{$language}/search/all?q={$searchterm|escape:url}&amp;order=%order%{/capture}
{include file=Visitor/_sort.tpl url=$url}
{/if}
