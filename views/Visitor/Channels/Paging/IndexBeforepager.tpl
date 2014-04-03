<div class="heading categories">
  <h1>{#channels__index_title#}</h1>
</div>

{capture assign=url}{$language}/{$module}?order=%s{/capture}
<div class="sort">
  <div class="item">
    <a class="title" href="{$url|activesortlink:title:$order}">{#channels__sort_title#|activesortarrow:title:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':title}">{#channels__sort_title#|sortarrows:null:title:$order}</a></li>
      <li><a href="{$url|replace:'%s':title_desc}">{#channels__sort_title_desc#|sortarrows:null:title_desc:$order}</a></li>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:creation:$order}">{#channels__sort_creation#|activesortarrow:creation:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':creation}">{#channels__sort_creation#|sortarrows:null:creation:$order}</a></li>
      <li><a href="{$url|replace:'%s':creation_desc}">{#channels__sort_creation_desc#|sortarrows:null:creation_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:starttime:$order}">{#channels__sort_starttime#|activesortarrow:starttime:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':starttime}">{#channels__sort_starttime#|sortarrows:null:starttime:$order}</a></li>
      <li><a href="{$url|replace:'%s':starttime_desc}">{#channels__sort_starttime_desc#|sortarrows:null:starttime_desc:$order}</a></li>
    </ul>
  </div>
  
</div>
