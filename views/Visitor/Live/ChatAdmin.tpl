{include file="Visitor/_header.tpl" islive=true}

<div class="title">
  <div class="actions"><a href="{$language}/live/chatexport/{$feed.id}">{#live__chatexport#}</a></div>
  <h1>{#live__chatadmin_title#}</h1>
  <h2><a href="{$language}/live/view/{$feed.id},{$feed.name|filenameize}">{$channel.title|escape:html|mb_wordwrap:25}</a></h2>
</div>
<div class="clear"></div>

<script type="text/javascript">
  var chatpollurl  = '{$language}/live/getchat/{$feed.id}';
  var chatpolltime = {$chatpolltime};
  var chatloginurl = '{$language}/live/refreshchatinput/{$feed.id}';
</script>

<div id="chat">
  <a href="#" id="chatnewmessages" style="display: none;">{#live__chatnewmessages#}</a>

  <div id="chatcontainer" style="height: 500px;" data-lastmodified="{$lastmodified}">
    {$chat}
  </div>
  <div id="chatinputcontainer">
    {include file=Visitor/Live/Chatinput.tpl}
  </div>
  <br/>
</div>

{include file="Visitor/_footer.tpl"}