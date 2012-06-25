<script type="text/javascript">
$f("{$htmlid}", "{$BASE_URI}swf/flowplayer-3.2.7.swf", {ldelim}
    onError: function() {ldelim}
        return true;
    {rdelim},
    onBeforePause: function() {ldelim}
        return false;
    {rdelim},
    onFail: function() {ldelim}
        $j('#{$htmlid}').text(
            "You need the latest Flash version to see MP4 movies. " +
            "Your version is " + this.getVersion()
        );
    {rdelim},
    showErrors: false,
    fadeInSpeed: 1500,
    fadeOutSpeed: 1500,
    clip: {ldelim}
        autoBuffering: true,
        scaling: 'fit'
    {rdelim},
    playlist: [
        {if $external == 0}
            {ldelim}
                url: '{$STATIC_URI}images/videosquare_stream{if $aspectratio == '16:9'}_wide{elseif $aspectratio == '5:4'}_sd{/if}.jpg',
                duration: 5
            {rdelim},
            {ldelim}
                url: '{$keycode}',
                provider: 'live'
            {rdelim}
        {elseif $external != 0 and $keycode}
            {ldelim}
                netConnectionUrl: '{$url}',
                url: '{$keycode}',
                provider: 'live'
            {rdelim}
        {else}
            {ldelim}
                url: '{$url}'
            {rdelim}
        {/if}
    ],
    plugins: {ldelim}
      live: {ldelim}
          url: 'flowplayer.rtmp-3.2.3.swf',
          netConnectionUrl: '{$liveurl}?sessionid={$sessionid}_{$recordingid}'
      {rdelim},
      controls: {ldelim}
          url: '{$BASE_URI}swf/flowplayer.controls-3.2.5.swf',
          play: false,
          time: false,
          slider: false,
          sliderColor: '#000000',
          scrubber: false,
          backgroundGradient: 'none',
          progressColor: '#000000'
      {rdelim}
    {rdelim}
{rdelim});
</script>
