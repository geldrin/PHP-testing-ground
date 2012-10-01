{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
  <div id="indexcontainer">
    <div class="leftdoublebox">
      {assign var=recording value=$recordings[0]}
      <a class="imageinfo wlarge" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}" title="{$recording.title|escape:html}">
        <img src="{$recording|@indexphoto:player}"/>
        <div class="playpic"></div>
        <div class="imageinfowrap">
          <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png" width="36" height="36"/></div>
          <div class="content">
            <h1>{$recording.title|mb_truncate:60|escape:html}</h1>
            <h2>{$recording|@nickformat|mb_truncate:90|escape:html}</h2>
          </div>
        </div>
      </a>
      
    </div>
    
    <div class="rightbox">
      {if isset( $recordings[1] )}
        {assign var=recording value=$recordings[1]}
        <a class="imageinfo wwide first" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}" title="{$recording.title|escape:html}">
          <img src="{$recording|@indexphoto:wide}"/>
          <div class="imageinfowrap">
            <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png" width="36" height="36"/></div>
            <div class="content">
              <h1>{$recording.title|mb_truncate:23|escape:html}</h1>
              <h2>{$recording|@nickformat|mb_truncate:60|escape:html}</h2>
            </div>
          </div>
        </a>
      {/if}
      {if isset( $recordings[2] )}
        {assign var=recording value=$recordings[2]}
        <a class="imageinfo wwide" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}" title="{$recording.title|escape:html}">
          <img src="{$recording|@indexphoto:wide}"/>
          <div class="imageinfowrap">
            <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png" width="36" height="36"/></div>
            <div class="content">
              <h1>{$recording.title|mb_truncate:23|escape:html}</h1>
              <h2>{$recording|@nickformat|mb_truncate:60|escape:html}</h2>
            </div>
          </div>
        </a>
      {/if}
    </div>
    <div class="clear"></div>
  </div>
{/if}

<div class="leftdoublebox indexnews">
  {if !empty( $news )}
    <div class="title">
      <h1>{#index__news#}</h1>
    </div>
    <ul class="newslist">
    {foreach from=$news item=item name=news}
      <li class="listingitem{if $smarty.foreach.news.last} last{/if}">
        <h2><a href="{$language}/organizations/newsdetails/{$item.id},{$item.title|filenameize}">{$item.title|mb_wordwrap:55|escape:html}</a><span class="subtitle">{$item.starts|date_format:#smarty_dateformat_long#}</span></h2>
        <p>{$item.lead|escape:html|nl2br}</p>
        <a href="{$language}/organizations/newsdetails/{$item.id},{$item.title|filenameize}" class="more">{#index__more#}</a>
      </li>
    {/foreach}
    </ul>
  {/if}
</div>

<div class="rightbox">
  <div class="title"><h1>Üdvözöljük</h1></div>
  {$introduction}
</div>


{include file="Visitor/_footer.tpl"}
