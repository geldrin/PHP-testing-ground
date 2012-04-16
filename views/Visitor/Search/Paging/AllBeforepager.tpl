<div class="heading">
  <h1>{#search__all_title#}</h1>
</div>

{if !empty( $items )}
{capture assign=url}{$language}/search/all?q={$searchterm|escape:url}&order=%s{/capture}
<div class="sorter">
  <ul>
    <li>
      <h3><a href="{$url|replace:'%s':relevancy}">{#search__all_relevancy#|sortarrows:null:relevancy:$order}</a></h3>
    </li>
    <li>
      <h3><a href="{$url|replace:'%s':recordedtimestamp}">{#search__recordedtimestamp#|sortarrows:null:recordedtimestamp:$order}</a></h3>
      <ul>
        <li>
          <a href="{$url|replace:'%s':recordedtimestamp_desc}">
            {#search__recordedtimestamp_desc#|sortarrows:null:recordedtimestamp_desc:$order}
          </a>
        </li>
      </ul>
    </li>
  </ul>
</div>
{/if}
