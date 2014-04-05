<div class="heading categories">
  <h1>{#channels__index_title#}</h1>
</div>

{capture assign=url}{$language}/{$module}?order=%s{/capture}
<div class="sort">
  <div class="item">
    <a class="title" href="{$url|activesortlink:lastmodified:$order}">{#channels__sort_lastmodified#|activesortarrow:lastmodified:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':lastmodified}">{#channels__sort_lastmodified#|sortarrows:null:lastmodified:$order}</a></li>
      <li><a href="{$url|replace:'%s':lastmodified_desc}">{#channels__sort_lastmodified_desc#|sortarrows:null:lastmodified_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:creation:$order}">{#channels__sort_creation#|activesortarrow:creation:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':creation}">{#channels__sort_creation#|sortarrows:null:creation:$order}</a></li>
      <li><a href="{$url|replace:'%s':creation_desc}">{#channels__sort_creation_desc#|sortarrows:null:creation_desc:$order}</a></li>
    </ul>
  </div>
  <div class="item">
    <a class="title" href="{$url|activesortlink:title:$order}">{#channels__sort_title#|activesortarrow:title:$order}</a>
    <ul>
      <li><a href="{$url|replace:'%s':title}">{#channels__sort_title#|sortarrows:null:title:$order}</a></li>
      <li><a href="{$url|replace:'%s':title_desc}">{#channels__sort_title_desc#|sortarrows:null:title_desc:$order}</a></li>
  </div>
</div>
