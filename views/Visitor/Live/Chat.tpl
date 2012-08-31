<ul id="chatlist">
  {foreach from=$chatitems item=chat}
    <li class="{if $chat.moderated < 0}waitingforapproval {/if}{if $member.id and $member.id == $chat.userid}self{/if}">
      {if $liveadmin}
        <div class="actions">
          {if $chat.moderated < 0}
            <a href="{$language}/live/moderatechat/{$chat.id}?moderate=0" class="moderate">{#live__approve#}</a> |
            <a href="{$language}/live/moderatechat/{$chat.id}?moderate=1" class="moderate">{#live__moderate#}</a>
          {elseif $chat.moderated}
            <a href="{$language}/live/moderatechat/{$chat.id}?moderate=0" class="moderate">{#live__approve#}</a>
          {else}
            <a href="{$language}/live/moderatechat/{$chat.id}?moderate=1" class="moderate">{#live__moderate#}</a>
          {/if}
        </div>
      {/if}
      <div class="name">{$chat|@nickformat|escape:html}:</div><div class="content">{if $chat.moderated < 0 and $liveadmin}{#live__chat_waitingforapproval#}: {$chat.text|mb_wordwrap:70|escape:html}{elseif $chat.moderated}{#live__chat_moderated#}{else}{$chat.text|mb_wordwrap:70|escape:html}{/if}</div>
    </li>
  {/foreach}
</ul>