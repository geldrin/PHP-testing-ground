{*} kötelező tagek {/*}
<meta property="og:url"       content="{$FULL_URI|escape:html}"/>
<meta property="og:site_name" content="{#sitename#|escape:html}"/>
<meta property="fb:admins"    content="{$smarty.const.FACEBOOK_IDS|escape:html}"/>
<meta property="og:title"     content="{if $title}{$title|escape:html|titleescape} | {/if}{#sitename#}"/>

{if !empty( $opengraph )}
  
  <meta property="og:image"     content="{$opengraph.image|escape:html|default:"`$STATIC_URI`images/header_logo.png"}"/>
  <meta property="og:type"      content="{$opengraph.type|escape:html}"/>

  {if !$opengraph.description}
    <meta property="og:description" content="{$opengraph.title|escape:html}{if $opengraph.subtitle} - {$opengraph.subtitle|escape:html}{/if}"/>
  {else}
    <meta property="og:description" content="{$opengraph.description|mb_truncate:250|escape:html}"/>
  {/if}

  {if $opengraph.video}
    <meta property="og:video" content="{$opengraph.video|escape:html}"/>
    <meta property="og:video:height" content="{$opengraph.height}"/>
    <meta property="og:video:width" content="{$opengraph.width}"/>
    <meta property="og:video:type" content="application/x-shockwave-flash"/>
  {/if}
{/if}