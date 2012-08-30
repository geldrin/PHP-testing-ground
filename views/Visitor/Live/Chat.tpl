<ul id="chatlist">
  {foreach from=$chatitems item=chat}
    <li>
      {if $liveadmin and !$chat.moderated}
        <div class="actions">
          <a href="{$language}/live/moderatechat/{$chat.id}" class="moderate">{#live__moderate#}</a>
        </div>
      {/if}
      <div class="name">{$chat|@nickformat|escape:html}:</div><div class="content">{if $chat.moderated}{#live__chat_moderated#}{else}{$chat.text|mb_wordwrap:70|escape:html}{/if}</div>
    </li>
  {/foreach}
</ul>