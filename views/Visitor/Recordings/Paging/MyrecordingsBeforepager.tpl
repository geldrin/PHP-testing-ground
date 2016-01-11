<div class="heading">
  <h1>{#recordings__myrecordings_title#}</h1>
  <h2>{#recordings__myrecordings_subtitle#}</h2>
</div>

{if !$nosearch}
<div id="myrecordingsquicksearch" class="form pagingsearch">
  <form method="GET" action="{$language}/recordings/myrecordings">
    <input type="hidden" name="order" value="{$order|escape:html}"/>
    <input type="hidden" name="start" value="0"/>
    <input type="hidden" name="perpage" value="{$smarty.get.perpage|escape:html}"/>
    <div class="textwrap">
      <label for="myrecordingsq">{#recordings__myrecordings_quicksearch#}:</label>
      <input type="text" name="myrecordingsq" value="{$smarty.get.myrecordingsq|escape:html}" id="myrecordingsq"/>
    </div>
    <div class="selectwrap">
      <div class="elem">
        <label for="status">{#recordings__myrecordings_status#}:</label>
        <select name="status" id="status">
          <option value=""{if !in_array( $smarty.get.status, array('converting', 'converted', 'failed') )} selected="selected"{/if}></option>
          <option value="converting"{if $smarty.get.status == "converting"} selected="selected"{/if}>{#recordings__myrecordings_status_converting#}</option>
          <option value="converted"{if $smarty.get.status == "converted"} selected="selected"{/if}>{#recordings__myrecordings_status_converted#}</option>
          <option value="failed"{if $smarty.get.status == "failed"} selected="selected"{/if}>{#recordings__myrecordings_status_failed#}</option>
        </select>
      </div>
      <div class="elem">
        <label for="publishstatus">{#recordings__myrecordings_publishstatus#}:</label>
        <select name="publishstatus" id="publishstatus">
          <option value=""{if !in_array( $smarty.get.publishstatus, array('published', 'nonpublished') )} selected="selected"{/if}></option>
          <option value="published"{if $smarty.get.publishstatus == "published"} selected="selected"{/if}>{#recordings__myrecordings_publishstatus_published#}</option>
          <option value="nonpublished"{if $smarty.get.publishstatus == "nonpublished"} selected="selected"{/if}>{#recordings__myrecordings_publishstatus_nonpublished#}</option>
        </select>
      </div>
      <div class="elem">
        <label for="publicstatus">{#recordings__myrecordings_publicstatus#}:</label>
        <select name="publicstatus" id="publicstatus">
          <option value=""{if !in_array( $smarty.get.publicstatus, array('public', 'private') )} selected="selected"{/if}></option>
          <option value="public"{if $smarty.get.publicstatus == "public"} selected="selected"{/if}>{#recordings__myrecordings_publicstatus_public#}</option>
          <option value="private"{if $smarty.get.publicstatus == "private"} selected="selected"{/if}>{#recordings__myrecordings_publicstatus_private#}</option>
        </select>
      </div>
      {if false and ( $member.isadmin or $member.iseditor or $member.isclientadmin )}
        <div class="elem">
          <label for="isintrooutro">{#recordings__myrecordings_isintrooutro#}:</label>
          <select name="isintrooutro" id="isintrooutro">
            <option value=""{if !in_array( $smarty.get.isintrooutro, array('yes', 'no') )} selected="selected"{/if}></option>
            <option value="yes"{if $smarty.get.isintrooutro == "yes"} selected="selected"{/if}>{#recordings__myrecordings_isintrooutro_yes#}</option>
            <option value="no"{if $smarty.get.isintrooutro == "no"} selected="selected"{/if}>{#recordings__myrecordings_isintrooutro_no#}</option>
          </select>
        </div>
      {/if}
    </div>
    <div class="submitwrap">
      <input class="submitbutton" type="submit" value="{#recordings__myrecordings_filter#}"/>
    </div>
  </form>
</div>
{/if}

{if !empty( $items )}
{capture assign=url}{$language}/{$module}/myrecordings?order=%order%&amp;start={$smarty.get.start|escape:uri}&amp;perpage={$smarty.get.perpage|escape:uri}&amp;myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&amp;status={$smarty.get.status|escape:uri}&amp;publishstatus={$smarty.get.publishstatus|escape:uri}&amp;publicstatus={$smarty.get.publicstatus|escape:uri}{/capture}

{include file=Visitor/_sort.tpl url=$url}
{/if}
