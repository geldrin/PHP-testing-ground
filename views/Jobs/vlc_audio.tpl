## VideoLAN VLM config file
# TARGET: picture-in-picture content + audio only master conversion

del all

## Background video (large)
new channel1 broadcast
setup channel1 input {$content_file}
setup channel1 output #duplicate{ldelim}dst=mosaic-bridge{ldelim}id=1,width={$content_x},height={$content_y}{rdelim},select=video,dst=bridge-out{ldelim}id=1{rdelim},select=audio{rdelim}
setup channel1 enabled

# Audio to mix to content
new channel2 broadcast
setup channel2 input {$video_file}
setup channel2 output #duplicate{ldelim}dst=bridge-out{ldelim}id=2{rdelim},select=audio{rdelim}
setup channel2 enabled

## Background video
new bg broadcast enabled
setup bg input {$background}
setup bg option image-width={$content_x}
setup bg option image-height={$content_y}
setup bg option image-fps={$fps}
setup bg option image-duration=-1
setup bg output #transcode{ldelim}sfilter=mosaic{ldelim}delay={$delay}{rdelim},vcodec=h264{ldelim}profile={$h264_profile}{rdelim},width={$content_x},height={$content_y},vb={$video_bw},acodec=mp3,ab={$audio_bw},channels={$audio_ch},samplerate={$audio_sr},scale=1{rdelim}:bridge-in{ldelim}delay={$delay},id-offset=100{rdelim}:standard{ldelim}access=file,mux=mp4,dst={$output_file}{rdelim}

## Play inputs and background
control channel1 play
control channel2 play
control bg play
