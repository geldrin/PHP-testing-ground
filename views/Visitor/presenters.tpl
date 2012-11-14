{foreach from=$presenters item=presenter name=presenter}
  <b>{$presenter|@nameformat:true|escape:html}</b>
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
      {if $smarty.foreach.job.first}({/if}{$jobcapture|trim}{if !$smarty.foreach.job.last} -{/if}{if $smarty.foreach.job.last}){/if}
    {/if}
  {/foreach}
  {if !$smarty.foreach.presenter.last}<br/>{/if}
{/foreach}