{foreach from=$presenters item=presenter name=presenter}
  <b>{$presenter|@nameformat:true|escape:html}</b>
  {php}
    global $presenterjob;
    $presenterjob = array();
  {/php}
  {foreach from=$presenter.jobs item=job name=job}
    {capture assign=joborganization}
      {if strlen( trim( $job.nameshort ) )}
        {$job.nameshort|escape:html}
      {elseif strlen( trim( $job.name ) )}
        {$job.name|escape:html}
      {/if}
    {/capture}
    
    {capture assign=jobcapture}
      {if strlen( trim( $job.job ) ) and strlen( trim( $joborganization ) )}
        {$job.job|escape:html}, {$joborganization|trim|escape:html}
      {else}
        {$job.job|escape:html}{$joborganization|trim|escape:html}
      {/if}
    {/capture}
    
    {if strlen( trim( $jobcapture ) )}
      {php}
        global $presenterjob;
        $presenterjob[] = trim( $this->get_template_vars('jobcapture') );
      {/php}
    {/if}
  {/foreach}
  {php}
    global $presenterjob;
    $presenterjob = implode(' - ', $presenterjob );
    if ( strlen( trim( $presenterjob ) ) )
      $presenterjob = '(' . $presenterjob . ')';

    $this->assign('presenterjob', $presenterjob );
  {/php}
  {$presenterjob}
  {if !$smarty.foreach.presenter.last}<br/>{/if}
{/foreach}