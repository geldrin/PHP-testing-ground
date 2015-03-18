{if $member.id or $feed.anonymousallowed}
{if !$anonuser.id and $bootstrap->config.recaptchaenabled}
<script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>
{/if}
  <form enctype="multipart/form-data" id="live_createchat" name="live_createchat" action="{$language}/live/createchat/{$feed.id}" method="post"{if !$member.id} data-ishuman="{if $anonuser.id or !$bootstrap->config.recaptchaenabled}true{else}false{/if}"{/if}>
    <input type="hidden" id="action" name="action" value="submitcreatechat"/>
    <input type="hidden" id="recaptcharesponse" name="action" value=""/>
    <div id="recaptchacontainer" data-recaptchapubkey="{$bootstrap->config.recaptchapub}" style="display: none"></div>
    <label for="text">{#live__chat_message#}:</label>
    <div id="textcontainer">
      <input type="text" name="text" id="text" value=""/>
      <div id="moderationalertcontainer" style="display: none">{#live__chat_alert_waitingforapproval#}</div>
    </div>
    <input type="submit" value="{#live__chat_submit#}"/>
    <div id="spinnercontainer"><img src="{$STATIC_URI}images/spinner.gif" width="16" height="16" id="spinner"/></div>
    
  </form>
{elseif $smarty.request.chromeless or $chromeless}
  <br/>
  <a href="{$language}/users/login?chromeless=true&force=1&forward={$FULL_URI|escape:url}">{if $needauth}{#live__chatneedauth#}{else}{#live__logintochat#}{/if}</a>
  <br/>
{/if}