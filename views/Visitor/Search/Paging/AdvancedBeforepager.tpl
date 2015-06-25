<div class="heading">
  <h1>{#search__advanced_title#}</h1>
</div>
<div class="form search">
  {$form}
</div>

{if !empty( $items )}
{capture assign=url}{$searchurl|escape:html}&amp;order=%s{/capture}

<div class="sort">
  <div class="item">
    <a class="title" href="{$url|activesortlink:relevancy_desc:$order}">{#search__all_relevancy#|activesortarrow:relevancy_desc:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':relevancy_desc}">{#search__all_relevancy#|sortarrows:null:relevancy_desc:$order}</a></li>
    </ul>
  </div>
  
  <div class="item">
    <a class="title" href="{$url|activesortlink:recordedtimestamp:$order}">{#search__recordedtimestamp#|activesortarrow:recordedtimestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':recordedtimestamp}">{#search__recordedtimestamp#|sortarrows:null:recordedtimestamp:$order}</a></li>
      <li><a href="{$url|replace:'%s':recordedtimestamp_desc}">{#search__recordedtimestamp_desc#|sortarrows:null:recordedtimestamp_desc:$order}</a></li>
    </ul>
  </div>

  <div class="item">
    <a class="title" href="{$url|activesortlink:uploadedtimestamp:$order}">{#search__uploadedtimestamp#|activesortarrow:uploadedtimestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':uploadedtimestamp}">{#search__uploadedtimestamp#|sortarrows:null:uploadedtimestamp:$order}</a></li>
      <li><a href="{$url|replace:'%s':uploadedtimestamp_desc}">{#search__uploadedtimestamp_desc#|sortarrows:null:uploadedtimestamp_desc:$order}</a></li>
    </ul>
  </div>

  <div class="item">
    <a class="title" href="{$url|activesortlink:lastmodifiedtimestamp:$order}">{#search__lastmodifiedtimestamp#|activesortarrow:lastmodifiedtimestamp:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':lastmodifiedtimestamp}">{#search__lastmodifiedtimestamp#|sortarrows:null:lastmodifiedtimestamp:$order}</a></li>
      <li><a href="{$url|replace:'%s':lastmodifiedtimestamp_desc}">{#search__lastmodifiedtimestamp_desc#|sortarrows:null:lastmodifiedtimestamp_desc:$order}</a></li>
    </ul>
  </div>
</div>
{/if}
