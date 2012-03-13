<div class="heading categories">
  {*}<a href="{$language}/categories">{#categories__categories_title#}</a> &raquo; {$currentcategory|@orteliusbreadcrumb}<br />{/*}
  <h1>{$category.name|escape:html}</h1>
  {*}<a href="{$language}/search/advanced?ortelius={$category.id}&showform=1">{#categories__searchincategory#|sprintf:$category.name}</a><br />{/*}
</div>
<div class="sorter">
  <ul>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=timestamp">{#categories__timestamp#|sortarrows:null:timestamp:$order}</a></h3>
      <ul>
        <li><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=timestamp_desc">{#categories__timestamp_desc#|sortarrows:null:timestamp_desc:$order}</a></li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=views">{#categories__views#|sortarrows:null:views:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=views_desc">
            {#categories__views_desc#|sortarrows:null:views_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthisweek">
            {#categories__viewsthisweek#|sortarrows:null:viewsthisweek:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthisweek_desc">
            {#categories__viewsthisweek_desc#|sortarrows:null:viewsthisweek_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthismonth">
            {#categories__viewsthismonth#|sortarrows:null:viewsthismonth:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=viewsthismonth_desc">
            {#categories__viewsthismonth_desc#|sortarrows:null:viewsthismonth_desc:$order}
          </a>
        </li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=comments">{#categories__comments#|sortarrows:null:comments:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=comments_desc">
            {#categories__comments_desc#|sortarrows:null:comments_desc:$order}
          </a>
        </li>
      </ul>
    </li>
    <li>
      <h3><a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=rating">{#categories__rating#|sortarrows:null:rating:$order}</a></h3>
      <ul>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=rating_desc">
            {#categories__rating_desc#|sortarrows:null:rating_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthisweek">
            {#categories__ratingthisweek#|sortarrows:null:ratingthisweek:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthisweek_desc">
            {#categories__ratingthisweek_desc#|sortarrows:null:ratingthisweek_desc:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthismonth">
            {#categories__ratingthismonth#|sortarrows:null:ratingthismonth:$order}
          </a>
        </li>
        <li>
          <a href="{$language}/{$module}/details/{$category.id},{$category.name|filenameize}?order=ratingthismonth_desc">
            {#categories__ratingthismonth_desc#|sortarrows:null:ratingthismonth_desc:$order}
          </a>
        </li>
      </ul>
    </li>
  </ul>
</div>