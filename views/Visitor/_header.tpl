<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language}" xml:lang="{$language}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta http-equiv="imagetoolbar" content="no" />
  <meta name="author" content="Dots Amazing - www.dotsamazing.com" />
  <meta name="MSSmartTagsPreventParsing" content="true" />
  <meta name="format-detection" content="telephone=no" />{*} iphone ne vegye a random szamokat telefonszamoknak {/*}
  <base href="{$BASE_URI}" /><!--[if IE]></base><![endif]-->
  <title>{if $title}{$title|escape:html|titleescape} | {/if}{#sitename#}</title>
  {include file="Visitor/_opengraph.tpl"}
  <link rel="icon" type="image/png" href="{$STATIC_URI}images/favicon.png"/>
  {csscombine}
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style{$VERSION}.css" media="screen"/>
  {if $browser.mobile}
    <meta name="viewport" content="width=device-width, maximum-scale=1.0"/>
    <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_mobile{$VERSION}.css" media="screen"/>
  {/if}
  {/csscombine}
  
  <!--[if lte IE 8]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie{$VERSION}.css" />
  <![endif]-->
  <!--[if lte IE 7]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie7{$VERSION}.css" />
  <![endif]-->
  <!--[if lte IE 6]>
  <link rel="StyleSheet" type="text/css" href="{$STATIC_URI}css/style_ie6{$VERSION}.css" />
  <![endif]-->
  {jscombine}
  <script type="text/javascript" src="{$STATIC_URI}js/jquery-1.7.1.min.js"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/swfobject.full.js"></script>
  <script type="text/javascript" src="{$bootstrap->scheme}{$bootstrap->config.baseuri}{$language}/contents/language"></script>
  <script type="text/javascript" src="{$STATIC_URI}js/tools{$VERSION}.js"></script>
  {/jscombine}
  <script type="text/javascript">
  var BASE_URI   = '{$BASE_URI}';
  var STATIC_URI = '{$STATIC_URI}';
  var VERSION    = '{$VERSION}';
  var language   = '{$language}';
  var BROWSER    = {ldelim}
    mobile: {if $browser.mobile}true{else}false{/if},
    tablet: {if $browser.tablet}true{else}false{/if},
    obsolete: {if $browser.obsolete}true{else}false{/if} 
  {rdelim};
  </script>
  <link rel="alternate" type="application/rss+xml" title="{#rss_news#|sprintf:$organization.name|escape:html}" href="{$language}/organizations/newsrss" />
</head>
<body>
{if $browser.obsolete}
  <a class="openinlayer" target="_blank" href="{$BASE_URI}{$language}/tools/updateyourbrowser" id="browserAlert">{#sitewide_updateyourbrowser#}</a>
{/if}
<div id="headerbg"></div>
{if $pagebgclass}
  <div id="pagebg" class="{$pagebgclass}"></div>
{/if}
<div id="pagecontainer">
  <div id="wrap">
    <div id="header">
      <div id="headertop">
        {include file="Visitor/_login.tpl"}
        
        <div id="headerlogo">
          <a href="{$BASE_URI}" title="{#sitename#}"><span></span>{#sitename#}</a>
        </div>
      </div>
      <div id="headerbottom">
        <div id="headersearch" class="rightbox">
          
          <form action="{$language}/search/all" method="get">
            <input id="headersearchsubmit" type="image" src="{$STATIC_URI}images/header_searchimage.png"/>
            <input class="inputtext inputbackground clearonclick" type="text" name="q" data-origval="{#sitewide_search_input#|escape:html}" value="{$smarty.request.q|default:#sitewide_search_input#|escape:html}"/>
            <a href="#" id="headersearcharrow"></a>
          </form>
          <div id="headersearchlink"><a href="{$language}/search/all">{#sitewide_search#}</a></div>
          <div id="languageselector" class="inputbackground right">
            {foreach from=$organization.languages key=languageid item=item}
              {if $languageid == $language}
                <a href="{$FULL_URI|changelanguage:$language}" class="{$language} active">{l lov=headerlanguages key=$language}<span></span></a>
              {/if}
            {/foreach}
            
            <div id="languages">
              {foreach from=$organization.languages key=languageid item=item}
                {if $languageid != $language}
                  <a href="{$FULL_URI|changelanguage:$languageid}" class="{$languageid}">{l lov=headerlanguages key=$languageid}</a>
                {/if}
              {/foreach}
            </div>
          </div>
        </div>
        
        {include file="Visitor/_menu.tpl"}
      </div>
    </div>
    
    {if $sessionmessage and !$skipsessionmessage}
      {include file="Visitor/_message.tpl" message=$sessionmessage}
    {/if}
    
    <div id="body">
