<div class="title">
  <h1>{#users__admin_title#}</h1>
  <a href="{$language}/users/invite">{#users__invite#}</a>
  <br/>
</div>

{if !$nosearch}
<div id="useradminquicksearch" class="form pagingsearch">
  <form method="GET" action="{$language}/users/invitations">
    <input type="hidden" name="order" value="{$order|escape:html}"/>
    <input type="hidden" name="start" value="0"/>
    <input type="hidden" name="perpage" value="{$smarty.get.perpage|escape:html}"/>
    <div class="textwrap">
      <label for="term">{#users__admin_quicksearch#}:</label>
      <input type="text" name="term" value="{$smarty.get.term|escape:html}" id="term"/>
    </div>
    <div class="submitwrap">
      <input class="submitbutton" type="submit" value="{#users__admin_filter#}"/>
    </div>
  </form>
</div>
{/if}
