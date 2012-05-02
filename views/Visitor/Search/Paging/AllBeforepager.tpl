<div class="heading">
  <h1>{#search__all_title#}</h1>
</div>

{if !empty( $items )}
{capture assign=url}{$language}/search/all?q={$searchterm|escape:url}&order=%s{/capture}

<div class="sort">
  <div class="item">
    <a class="title" href="{$url|replace:'%s':relevancy}">{#search__all_relevancy#|activesortarrow:relevancy:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':relevancy}">{#search__all_relevancy#|sortarrows:null:relevancy:$order}</a></li>
    </ul>
  </div>
  
  <div class="item">
    <a class="title" href="{$url|replace:'%s':recordedtimestamp}">{#search__recordedtimestamp#|activesortarrow:recordedtimestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':recordedtimestamp}">{#search__recordedtimestamp#|sortarrows:null:recordedtimestamp:$order}</a></li>
      <li><a href="{$url|replace:'%s':recordedtimestamp_desc}">{#search__recordedtimestamp_desc#|sortarrows:null:recordedtimestamp_desc:$order}</a></li>
    </ul>
  </div>
</div>
{/if}
