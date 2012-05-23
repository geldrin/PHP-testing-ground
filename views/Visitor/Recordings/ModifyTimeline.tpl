
<div class="timeline">
  <ul>
    <li{if $step == 'modifybasics'} class="active"{/if}>
      <a href="{$language}/recordings/modifybasics/{$recording.id}?forward={$smarty.request.forward|escape:url}">{#recordings__timeline_modifybasics#}</a>
    </li>
    <li{if $step == 'modifyclassification'} class="active"{/if}>
      <a href="{$language}/recordings/modifyclassification/{$recording.id}?forward={$smarty.request.forward|escape:url}">{#recordings__timeline_modifyclassification#}</a>
    </li>
    <li{if $step == 'modifydescription'} class="active"{/if}>
      <a href="{$language}/recordings/modifydescription/{$recording.id}?forward={$smarty.request.forward|escape:url}">{#recordings__timeline_modifydescription#}</a>
    </li>
    <li{if $step == 'modifycontributors'} class="active"{/if}>
      {*}<a href="{$language}/recordings/modifycontributors/{$recording.id}?forward={$smarty.request.forward|escape:url}">{#recordings__timeline_modifycontributors#}</a>{/*}
      <a href="{$FULL_URI}">{#recordings__timeline_modifycontributors#}</a>
    </li>
    <li{if $step == 'modifysharing'} class="active"{/if}>
      <a href="{$language}/recordings/modifysharing/{$recording.id}?forward={$smarty.request.forward|escape:url}">{#recordings__timeline_modifysharing#}</a>
    </li>
  </ul>
</div>

<div class="title">
  <h1>{$recording.title|escape:html}</h1>
</div>
