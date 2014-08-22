{capture assign=value}
{$author|@nickformat} - {$recording.title}
{if $recording.subtitle|stringempty}({$recording.subtitle}){/if}
{if $recording.description|stringempty}- {$recording.description}{/if}
{if !empty( $recording.presenters )}
  {include file=Visitor/presenters.tpl presenters=$recording.presenters skippresenterbolding=true presenterdelimiter="; "}
{/if}
{/capture}
<meta name="description" content="{$value|onelinestring|trim|escape:html}"/>
