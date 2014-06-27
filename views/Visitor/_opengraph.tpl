{*} kötelező tagek {/*}
<meta property="og:url"       content="{$FULL_URI|escape:html}"/>
<meta property="og:site_name" content="{#sitename#|escape:html}"/>
<meta property="fb:admins"    content="{$smarty.const.FACEBOOK_IDS|escape:html}"/>
<meta property="og:title"     content="{if $title}{$title|strip_tags|escape:html|titleescape} | {/if}{#sitename#}"/>
{if $defaultimage and !$opengraph.image}
  <meta property="og:image"     content="{$defaultimage|escape:html}"/>
{/if}

{if !empty( $opengraph )}

  <meta property="og:image"     content="{$opengraph.image|escape:html|default:"`$STATIC_URI`images/header_logo.png"}"/>
  {if $opengraph.type}
    <meta property="og:type"    content="{$opengraph.type|escape:html}"/>
  {/if}

  {if !$opengraph.description}
    <meta property="og:description" content="{$opengraph.title|strip_tags|escape:html}{if $opengraph.subtitle} - {$opengraph.subtitle|strip_tags|escape:html}{/if}"/>
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