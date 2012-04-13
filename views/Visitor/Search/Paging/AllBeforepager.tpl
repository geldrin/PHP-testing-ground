<div class="heading">
  <h1>{#search__all_title#}</h1>
</div>

{if !empty( $items )}
<div class="sorter">
{*}
  <ul>
    <li>
      <h3><a href="{$language}/{$module}/myrecordings?order=timestamp&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">{#recordings__myrecordings_timestamp#|sortarrows:null:timestamp:$order}</a></h3>
      <ul>
        <li><a href="{$language}/{$module}/myrecordings?order=timestamp_desc&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">{#recordings__myrecordings_timestamp_desc#|sortarrows:null:timestamp_desc:$order}</a></li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/myrecordings?order=recordedtimestamp&&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">{#recordings__myrecordings_recordedtimestamp#|sortarrows:null:recordedtimestamp:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/myrecordings?order=recordedtimestamp_desc&&start={$smarty.get.start|escape:uri}&perpage={$smarty.get.perpage|escape:uri}&myrecordingsq={$smarty.get.myrecordingsq|escape:uri}&status={$smarty.get.status|escape:uri}&publishstatus={$smarty.get.publishstatus|escape:uri}&publicstatus={$smarty.get.publicstatus|escape:uri}">
            {#recordings__myrecordings_recordedtimestamp_desc#|sortarrows:null:recordedtimestamp_desc:$order}
          </a>
        </li>
      </ul>
    </li>
  </ul>
{/*}
</div>
{/if}
