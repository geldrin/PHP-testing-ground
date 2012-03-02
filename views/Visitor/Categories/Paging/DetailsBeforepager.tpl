<div class="heading categories">
  {*}<a href="{$language}/categories">{l module=categories key=categories_title#}</a> &raquo; {$currentcategory|@orteliusbreadcrumb}<br />{/*}
  <h1>{$category.name|escape:html}</h1>
  {*}<a href="{$language}/search/advanced?ortelius={$category.id}&showform=1">{l module=categories key=searchincategory#|sprintf:$category.name}</a><br />{/*}
</div>
<div class="sorter">
  <ul>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=timestamp">{l module=categories key=timestamp assign=timestamp}{$timestamp|sortarrows:null:timestamp:$order}</a></h3>
      <ul>
        <li><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=timestamp_desc">{l module=categories key=timestamp_desc assign=timestamp_desc}{$timestamp_desc|sortarrows:null:timestamp_desc:$order}</a></li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=views">{l module=categories key=views assign=views}{$views|sortarrows:null:views:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=views_desc">
            {l module=categories key=views_desc assign=views_desc}{$views_desc|sortarrows:null:views_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthisweek">
            {l module=categories key=viewsthisweek assign=viewsthisweek}{$viewsthisweek|sortarrows:null:viewsthisweek:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthisweek_desc">
            {l module=categories key=viewsthisweek_desc assign=viewsthisweek_desc}{$viewsthisweek_desc|sortarrows:null:viewsthisweek_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthismonth">
            {l module=categories key=viewsthismonth assign=viewsthismonth}{$viewsthismonth|sortarrows:null:viewsthismonth:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthismonth_desc">
            {l module=categories key=viewsthismonth_desc assign=viewsthismonth_desc}{$viewsthismonth_desc|sortarrows:null:viewsthismonth_desc:$order}
          </a>
        </li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=comments">{l module=categories key=comments assign=comments}{$comments|sortarrows:null:comments:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=comments_desc">
            {l module=categories key=comments_desc assign=comments_desc}{$comments_desc|sortarrows:null:comments_desc:$order}
          </a>
        </li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=rating">{l module=categories key=rating assign=rating}{$rating|sortarrows:null:rating:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=rating_desc">
            {l module=categories key=rating_desc assign=rating_desc}{$rating_desc|sortarrows:null:rating_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthisweek">
            {l module=categories key=ratingthisweek assign=ratingthisweek}{$ratingthisweek|sortarrows:null:ratingthisweek:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthisweek_desc">
            {l module=categories key=ratingthisweek_desc assign=ratingthisweek_desc}{$ratingthisweek_desc|sortarrows:null:ratingthisweek_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthismonth">
            {l module=categories key=ratingthismonth assign=ratingthismonth}{$ratingthismonth|sortarrows:null:ratingthismonth:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthismonth_desc">
            {l module=categories key=ratingthismonth_desc assign=ratingthismonth_desc}{$ratingthismonth_desc|sortarrows:null:ratingthismonth_desc:$order}
          </a>
        </li>
      </ul>
    </li>
  </ul>
</div>