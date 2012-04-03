{include file="Visitor/_header.tpl" module="index"}

{if !empty( $recordings )}
  <div id="indexcontainer">
    <div class="leftdoublebox">
      {assign var=recording value=$recordings[0]}
      <a class="imageinfo wlarge" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}" title="{$recording.title|escape:html}">
        <img src="{$recording|@indexphoto:player}"/>
        <div class="playpic"></div>
        <div class="imageinfowrap">
          <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png"/></div>
          <div class="content">
            <h1>{$recording.title|mb_truncate:60|escape:html}</h1>
            <h2>{$recording.nickname|mb_truncate:90|escape:html}</h2>
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
            <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png"/></div>
            <div class="content">
              <h1>{$recording.title|mb_truncate:23|escape:html}</h1>
              <h2>{$recording.nickname|mb_truncate:60|escape:html}</h2>
            </div>
          </div>
        </a>
      {/if}
      {if isset( $recordings[2] )}
        {assign var=recording value=$recordings[2]}
        <a class="imageinfo wwide" href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}" title="{$recording.title|escape:html}">
          <img src="{$recording|@indexphoto:wide}"/>
          <div class="imageinfowrap">
            <div class="avatar"><img src="{$STATIC_URI}images/avatar_placeholder.png"/></div>
            <div class="content">
              <h1>{$recording.title|mb_truncate:23|escape:html}</h1>
              <h2>{$recording.nickname|mb_truncate:60|escape:html}</h2>
            </div>
          </div>
        </a>
      {/if}
    </div>
  </div>
{/if}

<div class="leftdoublebox indexnews">
  {if !empty( $news )}
    <div class="title">
      <h1>{#index__news#}</h1>
    </div>
    {foreach from=$news item=item name=news}
      {if $language == 'hu'}
        {assign var=title value=$item.titlehungarian}
        {assign var=lead value=$item.leadhungarian}
      {else}
        {assign var=title value=$item.titleenglish}
        {assign var=lead value=$item.leadenglish}
      {/if}
      <div class="newsitem{if $smarty.foreach.news.last} last{/if}">
        <h2>{$title|escape:html}<span class="subtitle">{$item.starts|date_format:#smarty_dateformat_long#}</span></h2>
        <p>{$lead|escape:html|nl2br}</p>
        <a href="{$language}/organizations/newsdetails/{$item.id},{$title|filenameize}" class="more">{#index__more#}</a>
      </div>
    {/foreach}
  {/if}
</div>

<div class="rightbox">
  <div class="title"><h1>Üdvözlünk</h1></div>
  <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo.</p>
  <p>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. Aenean ultricies mi vitae est. Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, commodo vitae, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. Donec non enim in turpis pulvinar facilisis. Ut felis. Praesent dapibus, neque id cursus faucibus, tortor neque egestas augue, eu vulputate magna eros eu erat. Aliquam erat volutpat. Nam dui mi, tincidunt quis, accumsan porttitor, facilisis luctus, metus</p>
</div>


{include file="Visitor/_footer.tpl"}
