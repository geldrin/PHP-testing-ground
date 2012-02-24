<div class="heading">
  <h1>{l module=recordings key=myrecordings_title}</h1>
  <h2>{l module=recordings key=myrecordings_subtitle}</h2>
</div>

{if !$nosearch}
<div id="myrecordingsquicksearch">
  <form method="GET" action="{$language}/recordings/myrecordings">
    <input type="hidden" name="order" value="{$order|escape:html}"/>
    <input type="hidden" name="start" value="0"/>
    <input type="hidden" name="perpage" value="{$smarty.get.perpage|escape:html}"/>
    <div class="textwrap">
      <label for="myrecordingsq">{l module=recordings key=myrecordings_quicksearch}:</label>
      <input type="text" name="myrecordingsq" value="{$smarty.get.myrecordingsq|escape:html}" id="myrecordingsq"/>
    </div>
    <div class="selectwrap">
      <div class="elem">
        <label for="status">{l module=recordings key=myrecordings_status}:</label>
        <select name="status" id="status">
          <option value=""{if !in_array( $smarty.get.status, array('converting', 'converted', 'failed') )} selected="selected"{/if}></option>
          <option value="converting"{if $smarty.get.status == "converting"} selected="selected"{/if}>{l module=recordings key=myrecordings_status_converting}</option>
          <option value="converted"{if $smarty.get.status == "converted"} selected="selected"{/if}>{l module=recordings key=myrecordings_status_converted}</option>
          <option value="failed"{if $smarty.get.status == "failed"} selected="selected"{/if}>{l module=recordings key=myrecordings_status_failed}</option>
        </select>
      </div>
      <div class="elem">
        <label for="publishstatus">{l module=recordings key=myrecordings_publishstatus}:</label>
        <select name="publishstatus" id="publishstatus">
          <option value=""{if !in_array( $smarty.get.publishstatus, array('published', 'nonpublished') )} selected="selected"{/if}></option>
          <option value="published"{if $smarty.get.publishstatus == "published"} selected="selected"{/if}>{l module=recordings key=myrecordings_publishstatus_published}</option>
          <option value="nonpublished"{if $smarty.get.publishstatus == "nonpublished"} selected="selected"{/if}>{l module=recordings key=myrecordings_publishstatus_nonpublished}</option>
        </select>
      </div>
      <div class="elem">
        <label for="publicstatus">{l module=recordings key=myrecordings_publicstatus}:</label>
        <select name="publicstatus" id="publicstatus">
          <option value=""{if !in_array( $smarty.get.publicstatus, array('public', 'private') )} selected="selected"{/if}></option>
          <option value="public"{if $smarty.get.publicstatus == "public"} selected="selected"{/if}>{l module=recordings key=myrecordings_publicstatus_public}</option>
          <option value="private"{if $smarty.get.publicstatus == "private"} selected="selected"{/if}>{l module=recordings key=myrecordings_publicstatus_private}</option>
        </select>
      </div>
    </div>
    <div class="submitwrap">
      <input type="submit" value="{l module=recordings key=myrecordings_filter}"/>
    </div>
  </form>
</div>
{/if}

{if !empty( $items )}
<div class="sorter">
  <ul>
    <li>
      <h2><a href="{$language}/{$module}/myrecordings?order=timestamp&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">{l module=recordings key=myrecordings_timestamp assign=timestamp}{$timestamp|sortarrows:null:timestamp:$order}</a></h2>
      <ul>
        <li><a href="{$language}/{$module}/myrecordings?order=timestamp_desc&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">{l module=recordings key=myrecordings_timestamp_desc assign=timestamp_desc}{$timestamp_desc|sortarrows:null:timestamp_desc:$order}</a></li>
      </ul>
    </li>
    <li>
      <h2><a href="{$language}/{$module}/myrecordings?order=recordedtimestamp&&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">{l module=recordings key=myrecordings_recordedtimestamp assign=recordedtimestamp}{$recordedtimestamp|sortarrows:null:recordedtimestamp:$order}</a></h2>
      <ul>
        <li>
          <a href="{$language}/{$module}/myrecordings?order=recordedtimestamp_desc&&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">
            {l module=recordings key=myrecordings_recordedtimestamp_desc assign=recordedtimestamp_desc}{$recordedtimestamp_desc|sortarrows:null:recordedtimestamp_desc:$order}
          </a>
        </li>
      </ul>
    </li>
  </ul>
</div>
{/if}
