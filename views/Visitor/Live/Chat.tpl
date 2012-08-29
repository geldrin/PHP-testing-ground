<ul id="chatlist" data-lastmodified="{$lastmodified|escape:html}">
  {foreach from=$chatitems item=chat}
    <li>
      <div class="title">
        {if $liveadmin}
          <div class="actions">
            <a href="{$language}/live/moderatechat/{$chat.id}">{#live__moderate#}</a>
          </div>
        {/if}
        {$chat.nickname|escape:html}
      </div>
      <div class="content">{if $chat.moderated}{#live__chat_moderated#}{else}{$chat.text|mb_wordwrap:70|escape:html}{/if}</div>
    </li>
  {/foreach}
</ul>