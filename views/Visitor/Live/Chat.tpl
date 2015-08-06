<ul id="chatlist">
  {foreach from=$chatitems item=chat}
    {if $chat.moderated == 0 or $liveadmin}
      <li class="{if $chat.isquestion}question{/if}{if $chat.moderated < 0} waitingforapproval{/if}{if ( $member.id and $member.id == $chat.userid ) or ( $anonuser.id and $anonuser.id == $chat.anonuserid )} self{/if}">

        <div class="actions">
          {if $liveadmin}
            {if $chat.moderated < 0}
              <a href="{$language}/live/moderatechat/{$chat.id}?moderate=0" class="moderate">{#live__approve#}</a> |
              <a href="{$language}/live/moderatechat/{$chat.id}?moderate=1" class="moderate">{#live__moderate#}</a>
            {elseif $chat.moderated}
              <a href="{$language}/live/moderatechat/{$chat.id}?moderate=0" class="moderate">{#live__approve#}</a>
            {else}
              <a href="{$language}/live/moderatechat/{$chat.id}?moderate=1" class="moderate">{#live__moderate#}</a>
            {/if}
          {/if}
          <a href="#" class="reply" title="{#live__chatreply#}"></a>
          <a href="#" class="copypaste" title="{#live__chatcopypaste#}"></a>
        </div>

        <div class="timestamp">{$chat.timestamp|substr:0:16}</div>
        <div class="name">{if $chat.userid}{$chat|@nickformat|escape:html}{else}{#live__chat_anonuser#|sprintf:$chat.anonuserid}{/if}:</div><div class="content">{if $chat.moderated < 0 and ( $liveadmin or $chat.userid == $member.id )}{#live__chat_waitingforapproval#}: {$chat.text|mb_wordwrap:70|escape:html}{elseif $chat.moderated}{#live__chat_moderated#}{else}{$chat.text|mb_wordwrap:70|escape:html}{/if}</div>
      </li>
    {/if}
  {/foreach}
</ul>