{if !$presenterdelimiter}
  {assign var=presenterdelimiter value="<br/>"}
{/if}

{*}whitespace miatt van ennyire fosul formazva{/*}
{foreach from=$presenters item=presenter name=presenter}
{if !$skippresenterbolding}<b>{/if}{$presenter|@nameformat:true|escape:html}{if !$skippresenterbolding}</b>{/if}
{php}
  global $presenterjob;
  $presenterjob = array();
{/php}
{foreach from=$presenter.jobs item=job name=job}
  {capture assign=joborganization}
    {if $job.nameshort|stringempty}{$job.nameshort|escape:html}{elseif $job.name|stringempty}{$job.name|escape:html}{/if}
  {/capture}
  
  {capture assign=jobcapture}
    {if $job.job|stringempty and $joborganization|stringempty}{$job.job|escape:html}, {$joborganization|trim|escape:html}{else}{$job.job|escape:html}{$joborganization|trim|escape:html}{/if}
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
{$presenterjob}{if !$smarty.foreach.presenter.last}{$presenterdelimiter}{/if}
{/foreach}