{assign var=emailcount value=$item.emailcount|numberformat}
{if $smarty.foreach.paging.first}
<tr>
  <th>{#live__invite_pin#}</th>
  <th>{#live__invite_users#}</th>
  <th>{#live__invite_emails#}</th>
</tr>
{/if}
<tr class="{cycle values='odd, even'}">
  <td>
    <div class="pin">{$item.pin}</div>
    <div class="timestamp">{$item.timestamp|substr:0:16}</div>
    <a href="{$language}/live/inviteteachers/{$feed.id}?frominviteid={$item.id}">{#live__teacher_resend#|sprintf:$emailcount}</a>
  </td>
  <td>
    {foreach from=$item.users item=user}
      <div class="user">{$user|@nickformat} ({$user.email|escape:html})</div>
    {/foreach}
  </td>
  <td>
    {foreach from=$item.emails item=email}
      <div class="email">{$email|escape:html}</div>
    {/foreach}
  </td>
</tr>
