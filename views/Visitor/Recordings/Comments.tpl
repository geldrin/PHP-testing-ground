{capture assign=recordingurl}{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}?commentspage={$activepage}{/capture}

<ul id="commentlist">
  {if !empty( $comments )}
    {foreach from=$comments item=item}
      {if $member.id or $recording.isanonymouscommentsenabled}
        {capture assign=replylink}{$recordingurl}&amp;focus={$item.sequenceid}{/capture}
      {else}
        {capture assign=replylink}{$language}/users/login?forward={$recordingurl|escape:url}{/capture}
      {/if}
      <li class="commentlistitem{if $commentfocus == $item.sequenceid} highlight{/if}" id="comment-{$item.sequenceid}" data-nick="{$item.nickname|escape:html}">
        <div class="user">
          <div class="avatar"><img src="{$item|@avatarphoto|escape:html}" width="36" height="36"/></div>
          <div class="name" >{$item|@nickformat|escape:html}</div>
          <div class="timestamp" >{$item.timestamp|date_format:#smarty_dateformat_longer#}</div>
        </div>
        <div class="message">{if $item.moderated == 0}{$item|@commentlinkify:$recordingurl}{else}{#recordings__comment_moderated#}{/if}</div>
        <div class="actions">
          <ul>
            <li><a href="{$replylink}" class="reply" data-commentid="{$item.sequenceid}" data-nick="{$item.nickname|escape:html}">{if $member.id or $recording.isanonymouscommentsenabled}{#recordings__reply#}{else}{#recordings__logintoreply#}{/if}</a></li>
            {*}
            {if $recording|@userHasAccess}
              {if $item.moderated <= 0}
                <li class="moderate"><a href="{$language}/recordings/moderate/{$recording.id}?commentid={$item.sequenceid}&amp;moderate=1">{#recordings__moderate_block#}</a></li>
              {/if}
              {if $item.moderated < 0 or $item.moderated > 0}
                <li class="moderate"><a href="{$language}/recordings/moderate/{$recording.id}?commentid={$item.sequenceid}&amp;moderate=0">{#recordings__moderate_allow#}</a></li>
              {/if}
            {/if}
            {/*}
          </ul>
        </div>
      </li>
    {/foreach}
  {else}
    <li class="nocomments">{#recordings__nocomments#}</li>
  {/if}
</ul>

<div class="widepager" data-getcommenturl="{$language}/recordings/getcomments/{$recording.id}">
  <ul>
    {if $activepage - $maxpages > 1}
      {assign var=minpage value=$activepage-$maxpages}
    {/if}
    {if $activepage + $maxpages < $pagecount}
      {assign var=maxpage value=$activepage+$maxpages}
    {/if}
    {section name=pagecount loop=$pagecount step=-1}
      {if $activepage == $smarty.section.pagecount.index+1}
        {assign var=currentpage value=true}
      {else}
        {assign var=currentpage value=false}
      {/if}
      {if $smarty.section.pagecount.index+1 == $maxpage or $smarty.section.pagecount.index+1 == $minpage}
        <li>
          <a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}?commentspage={$smarty.section.pagecount.index+1}" data-pageid="{$smarty.section.pagecount.index+1}">...</a>
        </li>
        {if $smarty.section.pagecount.index+1 == $maxpage}<span class="divider"></span>{/if}
      {elseif $minpage and $smarty.section.pagecount.index+1 < $minpage}
      {elseif $maxpage and $smarty.section.pagecount.index+1 > $maxpage}
      {else}
        <li {if $currentpage}class="currentpage"{/if}>
          {if !$currentpage}<a href="{$language}/recordings/details/{$recording.id},{$recording.title|filenameize}?commentspage={$smarty.section.pagecount.index+1}" data-pageid="{$smarty.section.pagecount.index+1}">{/if}{$smarty.section.pagecount.index+1}{if !$currentpage}</a>{/if}
        </li>
        {if !$smarty.section.pagecount.last}<span class="divider"></span>{/if}
      {/if}
    {/section}
  </ul>
</div>