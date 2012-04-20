<div class="heading">
  <h1>{#recordings__myrecordings_title#}</h1>
  <h2>{#recordings__myrecordings_subtitle#}</h2>
</div>

{if !$nosearch}
<div id="myrecordingsquicksearch" class="form">
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
    </div>
    <div class="submitwrap">
      <input class="submitbutton" type="submit" value="{#recordings__myrecordings_filter#}"/>
    </div>
  </form>
</div>
{/if}

{if !empty( $items )}
{capture assign=url}{$language}/{$module}/myrecordings?order=%s&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}{/capture}

<div class="sort">
  <div class="item">
    <a href="{$url|replace:'%s':timestamp}">{#recordings__myrecordings_timestamp#|sortarrows:null:timestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':timestamp_desc}">{#recordings__myrecordings_timestamp_desc#|sortarrows:null:timestamp_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a href="{$url|replace:'%s':recordedtimestamp}">{#recordings__myrecordings_recordedtimestamp#|sortarrows:null:recordedtimestamp:$order}</a>
    <ul>
      <li>
        <a href="{$url|replace:'%s':recordedtimestamp_desc}">{#recordings__myrecordings_recordedtimestamp_desc#|sortarrows:null:recordedtimestamp_desc:$order}</a>
      </li>
    </ul>
  </div>
</div>
{/if}
