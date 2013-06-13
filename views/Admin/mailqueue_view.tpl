{include file="Admin/_header.tpl"}

<div class="clear"></div>
<div id="listcontainer">
  <p>
    <table class="tabular tdup">
      <tr><td><b>Feladó</b></td><td>{$mail.fromname|escape:html} - {$mail.fromemail|escape:html}</td></tr>
      <tr><td><b>Küldési sorba kerül</b></td><td>{$mail.timestamp}</td></tr>
      <tr><td><b>Kézbesítve</b></td><td>{$mail.timesent}</td></tr>
      <tr><td><b>Címzett</b></td><td>{if $mail.toname}{$mail.toname|escape:html} - {/if}{$mail.toemail|escape:html}</td></tr>
      <tr><td><b>Téma</b></td><td>{$mail.subject}</td></tr>
      
      {if strpos( $mail.bodyencoded, 'text/html' ) !== false}
        <tr><td><b>Szöveg</b></td><td>{$mail.body}</td></tr>
      {else}
          <tr><td><b>Szöveg</b></td><td>{$mail.body|escape:html|nl2br}</td></tr>
      {/if}

      <tr><td><b>Állapot</b></td><td>{$l->getLov('mailqueueerrors', 'hu', $mail.status)}</td></tr>
      <tr><td><b>Hibaüzenet</b></td><td><pre>{if $mail.errormessage}{$mail.errormessage|escape:html}{else}- nincs -{/if}</pre></td></tr>
    </table>
  </p>
  <p>
    <table class="tabular tdup">
      <caption><h3>Technikai információk</h3></caption>
      <tr><td><b>Levéltörzs formázatlan változata</b></td><td><pre style="height: 300px; width: 650px; overflow: scroll;">{$mail.body|escape:html}</pre></td></tr>
      <tr>
        <td><b>Fejlécek</b></td>
        <td>
          <table>
            {foreach from=$mail.headers key=key item=header}
              <tr>
                <td><b>{$key}</b>:</td>
                <td>{$header|trim|escape:html}</td>
              </tr>
            {/foreach}
          </table>
        </td>
      </tr>
      <tr><td><b>Levéltörzs forrása</b></td><td><pre>{$mail.bodyencoded|escape:html}</pre></td></tr>
    </table>
  </p>
</div>

{include file="Admin/_footer.tpl"}