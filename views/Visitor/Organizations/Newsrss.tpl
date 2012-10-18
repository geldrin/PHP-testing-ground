<?xml version="1.0" encoding="utf-8"?><rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/"> 
<channel>
  <title><![CDATA[{#rss_news#|sprintf:$organization.name}]]></title>
  <link>{$FULL_URI}</link>
  <description></description>
  <language>{$language}</language>
  <lastBuildDate>{$builddate}</lastBuildDate>
  <docs>http://www.rssboard.org/rss-specification</docs>
  {foreach from=$items item=item}
    <item>
      <title><![CDATA[{$item.title}]]></title>
      <link>{$BASE_URI}{$language}/organizations/newsdetails/{$item.id},{$item.title|filenameize}</link>
      <guid>{$BASE_URI}{$language}/organizations/newsdetails/{$item.id},{$item.title|filenameize}</guid>
      <pubDate>{$item.starts|dateformat}</pubDate>
      {if $item.lead}<description><![CDATA[{$item.lead|escape:'html'}]]></description>{/if}
    </item>
  {/foreach}
</channel>
</rss>