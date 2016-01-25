<div id="categoryheading">
  <h1>{#sitewide_live#}</h1>
</div>
<div class="channelgradient"></div>
<br/>

{if $showsearch}
<div id="livequicksearch" class="form pagingsearch">
  <form method="GET" action="{$language}/live">
    <input type="hidden" name="order" value="{$order|escape:html}"/>
    <input type="hidden" name="start" value="0"/>
    <input type="hidden" name="perpage" value="{$smarty.get.perpage|escape:html}"/>
    <div class="textwrap">
      <label for="term">{#live__quicksearch#}:</label>
      <input type="text" name="term" value="{$smarty.get.term|escape:html}" id="term"/>
    </div>
    <div class="selectwrap">
      <div class="elem">
        <label for="showall">{#live__quicksearch_showall#}:</label>
        <select name="showall" id="showall">
          <option value="0"{if $smarty.get.showall == "0"} selected="selected"{/if}>{#live__quicksearch_showall_new#}</option>
          <option value="1"{if $smarty.get.showall == "1"} selected="selected"{/if}>{#live__quicksearch_showall_all#}</option>
        </select>
      </div>
    </div>
    <div class="submitwrap">
      <input class="submitbutton" type="submit" value="{#live__filter#}"/>
    </div>
  </form>
</div>
{/if}
