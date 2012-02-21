{include file="Visitor/_header.tpl"}
{box}
<script type="text/javascript" src="{$STATIC_URI}js/swfobject.full{$VERSION}.js"></script>
<script type="text/javascript" src="{$STATIC_URI}js/swfupload220/swfupload{$VERSION}.js"></script>

<div id="videoupload">
  <noscript id="noscriptcontainer">
    <div class="formerrors">
      <br />
      <ul>
        <li>{l key=sitewide_jsrequired}</li>
      </ul>
      <br />
    </div>
    <br />
  </noscript>
  <br />
  <div id="scriptingcontainer" style="display:none;">
    <script type="text/javascript">
var messages = new Object();
messages.pending            = '{l key=swfupload_pending}';
messages.toomanyfiles       = '{l key=swfupload_toomanyfiles}';
messages.uploadlimit        = '{l key=swfupload_uploadlimit}';
messages.filetoobig         = '{l key=swfupload_filetoobig}';
messages.zerobytefile       = '{l key=swfupload_zerobytefile}';
messages.invalidfiletype    = '{l key=swfupload_invalidfiletype}';
messages.unknownerror       = '{l key=swfupload_unknownerror}';
messages.uploading          = '{l key=swfupload_uploading}';
messages.uploaded           = '{l key=swfupload_uploaded}';
messages.uploaderror        = '{l key=swfupload_uploaderror}';
messages.uploaderrorverbose = '{l key=swfupload_uploaderrorverbose}';
messages.membersonly        = '{l key=swfupload_membersonly}';
messages.configerror        = '{l key=swfupload_configerror}';
messages.uploadfailed       = '{l key=swfupload_uploadfailed}';
messages.serverioerror      = '{l key=swfupload_serverioerror}';
messages.securityerror      = '{l key=swfupload_securityerror}';
messages.filenotfound       = '{l key=swfupload_filenotfound}';
messages.failedvalidation   = '{l key=swfupload_failedvalidation}';
messages.cancelled          = '{l key=swfupload_cancelled}';
messages.stopped            = '{l key=swfupload_stopped}';
messages.accessdenied       = '{l key=swfupload_accessdenied}';
messages.movefailed         = '{l key=swfupload_serverioerror}';
messages.invalidlength      = '{l key=swfupload_invalidlength}';
messages.tosaccept          = '{l module=recordings key=recordingstoshelp}';

$j(document).ready(function() {ldelim}
  
  var settings = {ldelim}
  upload_url:          "{$FULL_URI}",
  file_post_name:      "file",
  post_params:         {ldelim}"PHPSESSID": "{$sessionid}", "swfupload": 1, "language": '{$language}'{rdelim},
  prevent_swf_caching: true,
  
  //file_size_limit:        {$file_upload_limit|default:819200}, // 800MB
  file_size_limit:        "10GB",
  file_upload_limit:      0,
  file_types:             "*.wmv;*.avi;*.mov;*.flv;*.mkv;*.asf;*.mp4;*.mp3;*.ogg;*.wav;*.flac;*.wma;*.mpg;*.mpeg;*.ogm;*.f4v;*.m4v;",
  file_types_description: "Video Files",
  file_upload_limit:      1,
  file_queue_limit:       1,
  
  file_dialog_start_handler:     onFileDialogStart,
  file_dialog_complete_handler:  onFileDialogComplete,
  file_queued_handler:           onFileQueueSuccess,
  file_queue_error_handler:      onFileQueueError,
  
  upload_start_handler:          onUploadStart,
  upload_progress_handler:       onUploadProgress,
  upload_error_handler:          onUploadError,
  upload_success_handler:        onUploadSuccess,
  upload_complete_handler:       onUploadComplete,
  swfupload_load_failed_handler: swfuploadFallback,
  
  // Flash Settings
  flash_url:        "{$BASE_URI}swf/swfupload.swf",
  
  button_placeholder_id:    "videobrowse",
  button_image_url:         STATIC_URI + "images/swfupload_button.png",
  button_width:             "59",
  button_height:            "30",
  button_text:              '<span class="swfuploadbutton">{l module=recordings key=upload_browse}</span>',
  button_text_left_padding: {if $language == 'hu'}4{else}6{/if},
  button_text_top_padding:  4,
  button_text_style:        '.swfuploadbutton {ldelim} color: #ffffff; font-family: Helvetica, Arial, _sans; font-size: 13px; font-weight: normal; letter-spacing: 0.5px; {rdelim}',
  button_action:            SWFUpload.BUTTON_ACTION.SELECT_FILE{*}, // single select
  
  // Debug Settings
  debug: true
  {/*}
  {rdelim};
  
  try {ldelim}
    if ( swfobject.ua.pv[0] == 0 )
      swfuploadFallback();
    else
      swfupload = new SWFUpload( settings ); // global object, not local
  {rdelim} catch( e ) {ldelim}{rdelim}
  
  setupVideoUpload();

{rdelim});
    </script>
    
    {if strlen( $info )}
      <p>{$info}</p>
    {/if}
    
    {$form}
    
  </div>
</div>
{/box}
{if !empty( $help )}
<div class="help right small">
  <h1 class="title">{l key=help}</h1>
  {$help.body}
</div>
{/if}
{include file="Visitor/_footer.tpl"}