{if $member.id}
  <form enctype="multipart/form-data" id="live_createchat" name="live_createchat" action="{$language}/live/createchat/{$feed.id}" method="post">
    <input type="hidden" id="action" name="action" value="submitcreatechat"/>
    <label for="text">{#live__chat_message#}:</label>
    <input type="text" name="text" id="text" value=""/>
    <input type="submit" value="{#live__chat_submit#}"/>
    <div id="spinnercontainer"><img src="{$STATIC_URI}images/spinner.gif" width="16" height="16" id="spinner"/></div>
  </form>
{elseif $smarty.request.chromeless or $chromeless}
  <br/>
  <a href="{$language}/users/login?chromeless=true&force=1&forward={$FULL_URI|escape:url}">{#live__logintochat#}</a>
  <br/>
{/if}