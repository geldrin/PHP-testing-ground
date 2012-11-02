{include file="Admin/_header.tpl"}

<div class="clear"></div>
<div id="listcontainer">
  <table id="frame" border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td class="listing">
        <table width="100%" border="1" cellpadding="4" cellspacing="0">
          <tr>
            <th>Modul</th>
            <th></th>
          </tr>
          {foreach from=$modules item=module}
            <tr>
              <td>{$module|escape:html}</td>
              <td><center><input class="modify" type="button" onclick="location.href='{$BASEURI}localization/modify?id={$module|escape:url}';" value="{#admin__title_modify#}"></center></td>
            </tr>
          {/foreach}
        </table>
      </td>
    </tr>
  </table>
</div>

{include file="Admin/_footer.tpl"}