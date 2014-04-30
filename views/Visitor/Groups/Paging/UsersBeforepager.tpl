<div class="title">
  <h1>{#groups__users_title#}</h1>
  <h2>{$group.name|escape:html}</h2>
  <a href="{$language}/groups/invite/{$group.id},{$group.name|filenameize}">{#groups__invite#}</a>
  <br/>
</div>

{if !$nosearch}
<div id="useradminquicksearch" class="form pagingsearch">
  <form method="GET" action="{$language}/groups/users/{$group.id},{$group.name|filenameize}">
    <input type="hidden" name="order" value="{$order|escape:html}"/>
    <input type="hidden" name="start" value="0"/>
    <input type="hidden" name="perpage" value="{$smarty.get.perpage|escape:html}"/>
    <div class="textwrap">
      <label for="term">{#groups__users_quicksearch#}:</label>
      <input type="text" name="term" value="{$smarty.get.term|escape:html}" id="term"/>
    </div>
    <div class="submitwrap">
      <input class="submitbutton" type="submit" value="{#groups__users_filter#}"/>
    </div>
  </form>
</div>
{/if}