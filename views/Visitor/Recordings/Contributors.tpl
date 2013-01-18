{foreach from=$contributors item=contributor}
  <li>
    <div class="actions">
      <a href="{$language}/contributors/modify/{$contributor.contributorid}?crid={$contributor.id}" class="edit ui-state-default ui-corner-all" title="{#edit#}"><span class="ui-icon ui-icon-gear"></span</a>
      <a href="{$language}/recordings/deletecontributor/{$contributor.id}" class="delete ui-state-default ui-corner-all" title="{#delete#}"><span class="ui-icon ui-icon-trash"></span></a>
    </div>
    {$contributor|@nameformat|escape:html} - {$contributor.rolename|escape:html}
  </li>
{/foreach}