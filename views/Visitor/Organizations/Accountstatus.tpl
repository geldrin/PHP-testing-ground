{include file="Visitor/_header.tpl" title=#organizations__accountstatus_title#|sprintf:$organization.name pagebgclass=$pagebgclass}
<div class="title">
  <h1>{#organizations__accountstatus_title#|sprintf:$organization.name|escape:html}</h1>
</div>

<table id="accountstatus">
  <tr>
    <th colspan="2">{#organizations__accountstatus_basic#}</th>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_name#}:</td>
    <td>{$organization.name|escape:html}{if $organization.nameshort|stringempty} ({$organization.nameshort|escape:html}){/if}</td>
  </tr>
  {if $organization.url|stringempty}
    <tr>
      <td>{#organizations__accountstatus_url#}:</td>
      <td>{$organization.url|escape:html}</td>
    </tr>
  {/if}
  {if $organization.domain|stringempty}
    <tr>
      <td>{#organizations__accountstatus_domain#}:</td>
      <td>{$organization.domain|escape:html}</td>
    </tr>
  {/if}
  {if $organization.registrationtype|stringempty}
    <tr>
      <td>{#organizations__accountstatus_registrationtype#}:</td>
      <td>{if $organization.registrationtype == 'open'}{#organizations__accountstatus_registrationtype_open#}{elseif $organization.registrationtype == 'closed'}{#organizations__accountstatus_registrationtype_closed#}{/if}</td>
    </tr>
  {/if}
  {if $organization.supportemail|stringempty}
    <tr>
      <td>{#organizations__accountstatus_supportemail#}:</td>
      <td>{$organization.supportemail|escape:html}</td>
    </tr>
  {/if}
  <tr>
    <td>{#organizations__accountstatus_fullnames#}:</td>
    <td>{if $organization.displaynametype != "shownickname"}{#organizations__accountstatus_fullnames_yes#}{else}{#organizations__accountstatus_fullnames_no#}{/if}</td>
  </tr>
  <tr>
    <th colspan="2">{#organizations__accountstatus_subscription#}</th>
  </tr>
  {if !empty( $contract )}
    <tr>
      <td>{#organizations__accountstatus_subscriptionperiod#}:</td>
      <td>{$contract.startdate} - {$contract.enddate}</td>
    </tr>
  {/if}
  <tr>
    <td>{#organizations__accountstatus_islivestreamingenabled#}:</td>
    <td>{if $organization.islivestreamingenabled}{#organizations__accountstatus_islivestreamingenabled_yes#}{else}{#organizations__accountstatus_islivestreamingenabled_no#}{/if}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_isvcrenabled#}:</td>
    <td>{if $organization.isvcrenabled}{#organizations__accountstatus_isvcrenabled_yes#}{else}{#organizations__accountstatus_isvcrenabled_no#}{/if}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_issecurestreamingenabled#}:</td>
    <td>{if $organization.issecurestreamingenabled}{#organizations__accountstatus_issecurestreamingenabled_yes#}{else}{#organizations__accountstatus_issecurestreamingenabled_no#}{/if}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_issessionvalidationenabled#}:</td>
    <td>{if $organization.issessionvalidationenabled}{#organizations__accountstatus_issessionvalidationenabled_yes#}{else}{#organizations__accountstatus_issessionvalidationenabled_no#}{/if}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_isplayerlogolinkenabled#}:</td>
    <td>{if $organization.isplayerlogolinkenabled}{#organizations__accountstatus_isplayerlogolinkenabled_yes#}{else}{#organizations__accountstatus_isplayerlogolinkenabled_no#}{/if}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_presencechecktimeinterval#}:</td>
    <td>{$organization.presencechecktimeinterval|timeformat|escape:html}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_presencecheckconfirmationtime#}:</td>
    <td>{$organization.presencecheckconfirmationtime|timeformat|escape:html}</td>
  </tr>
  <tr>
    <th colspan="2"><a href="{$language}/users/admin">{#organizations__accountstatus_users#}</a></th>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_activeusercount#}:</td>
    <td>{$usercount.active|numberformat|escape:html}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_inactiveusercount#}:</td>
    <td>{$usercount.inactive|numberformat|escape:html}</td>
  </tr>
  <tr>
    <th colspan="2">{#organizations__accountstatus_recordings#}</th>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_uploadedsize#}</td>
    <td>{$recordingstats.masterdatasizemb|sizeformat:2:2}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_recordingsize#}</td>
    <td>{$recordingstats.recordingdatasizemb|sizeformat:2:2}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_masterlength#}</td>
    <td>{$recordingstats.masterlength|timeformat}</td>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_contentmasterlength#}</td>
    <td>{$recordingstats.contentmasterlength|timeformat}</td>
  </tr>
  <tr>
    <th colspan="2"><a href="{$language}/departments/admin">{#organizations__accountstatus_departments#}</a></th>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_departmentcount#}</td>
    <td>{$departmentcount|numberformat}</td>
  </tr>
  <tr>
    <th colspan="2"><a href="{$language}/groups">{#organizations__accountstatus_groups#}</a></th>
  </tr>
  <tr>
    <td>{#organizations__accountstatus_groupcount#}</td>
    <td>{$groupcount|numberformat}</td>
  </tr>
</table>
{include file="Visitor/_footer.tpl"}