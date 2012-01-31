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
  file_size_limit:        "2000MB",
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
    <form enctype="multipart/form-data" id="input" name="input" action="{$language}/recordings/uploadrecording" method="post">
    
      <input type="hidden" id="target" name="target" value="submituploadrecording"  />
      <input type="hidden" id="recordid" name="recordid" value="{$record|default:''}" />
      
      <fieldset id="fs1">
        <legend>{l module=recordings key=upload_title}</legend>
        <span class="legendsubtitle">{l key=upload_subtitle}</span>
        
        <div class="formrow">
          <span class="label"><label for="videolanguage">{l key=language}</label></span>
          <div class="element">
            <select  id="videolanguage" name="videolanguage">
              {foreach from=$languages key=key item=item}
              <option value="{$key}">{$item}</option>
              {/foreach}
            </select>
            <div id="cf_errorvideolanguage" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white; clear: both;"></div>
          </div>
        </div>
        
        <div class="formrow"> 
          <span class="label">{l key=isinterlaced}</span> 
          <div class="element">
            <input id="radio8375" type="radio" name="isinterlaced" checked="checked" value="0"/>&nbsp;<label for="radio8375">{l module=recordings key=isinterlaced_normal}</label>
            <input id="radio8376" type="radio" name="isinterlaced" value="1"/>&nbsp;<label for="radio8376">{l module=recordings key=isinterlaced_interlaced}</label>
          </div>
        </div>
        
        <div class="formrow">
          <span class="label left" style="width: 300px;"><label for="tos">{l module=recordings key=recordingstos}</label></span>
          <div class="element">
            <input type="checkbox" name="tos" id="tos" value="1"/>
            <span class="postfix"><a href="hu/contents/recordingstos" id="termsofservice" target="_blank">{l module=recordings key=recordingstospostfix}</a></span>
            <div id="cf_errortos" style="display: none; visibility: hidden; padding: 2px 5px 2px 5px; background-color: #d03030; color: white; clear: both;"></div>
          </div>
        </div>
        
        <div class="formrow" id="uploadrow">
          <span class="label"></span>
          <div class="element">
            <div id="videobrowsecontainer">
              <span id="videobrowse">{l module=recordings key=uploadnoflash}</span>
            </div>
            <div id="videouploadprogress" style="display:none;">
              <div class="progresswrap">
                <div class="progressname"></div>
                <div class="progressspeed"></div>
                <div class="clear"></div>
                <div class="progressbar"></div>
                <div class="progressstatus"></div>
                <div class="progresstime"></div>
              </div>
            </div>
          </div>
        </div>
      </fieldset>
      <input type="submit" value="OK" class="submitbutton" />
    </form>
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