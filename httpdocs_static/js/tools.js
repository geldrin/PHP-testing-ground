var $j = jQuery.noConflict();
$j(document).ready(function() {
  
  $j('#systemmessageclose a').click( function() {
    
    $j('#systemmessage').slideUp(150);
    return false;
    
  });
  
  runIfExists('#headerlogin', setupHeaderLogin );
  runIfExists('#headersearch', setupHeaderSearch );
  runIfExists('.ratewidget', setupRateWidget );
  
  $j('#scriptingcontainer').show();
  
  $j('.clearonclick').on('focusin', function() {
    
    if ( $j(this).val() == $j(this).attr('data-origval') )
      $j(this).val('');
    
  }).on('focusout', function() {
    
    if ( !$j(this).val() )
      $j(this).val( $j(this).attr('data-origval') );
    
  });
  
});

function runIfExists( selector, func ) {
  
  if ( $j( selector ).length > 0 )
    func();
  
}

function setupHeaderSearch() {
  
  $j('#headersearcharrow').on('click', function( e ) {
    e.preventDefault();
  });
  
  $j('#languageselector a.active').on('click', function( e ) {
    e.preventDefault();
    $j('#languageselector').toggleClass('active');
  });
  
  var languageselectortimeout;
  var clearLanguageSelector = function() {
    
    if ( languageselectortimeout ) {
      
      clearTimeout( languageselectortimeout );
      languageselectortimeout = null;
      
    }
    
  };
  
  $j('#languageselector').on('mouseenter', clearLanguageSelector );
  $j('#languageselector').on('mouseleave', function() {
    
    clearLanguageSelector();
    languageselectortimeout = setTimeout( function() {
      $j('#languageselector').toggleClass('active', false );
    }, 1750 );
    
  });
  
}

function setupHeaderLogin() {
  
  $j('#headerloginactionlink').on('click', function( e ) {
    e.preventDefault();
    $j('#headerloginform, #headerloginactions').toggle();
  });
  
  $j('#currentusername').on('click', function( e ) {
    e.preventDefault();
    $j('#currentuser').toggleClass('active');
  });
  
  var currentusertimeout;
  var clearCurrentUser = function() {
    
    if ( currentusertimeout ) {
      
      clearTimeout( currentusertimeout );
      currentusertimeout = null;
      
    }
    
  };
  
  $j('#currentusername, #currentusercontent').on('mouseenter', clearCurrentUser );
  
  $j('#currentusercontent').on('mouseleave', function() {
    
    clearCurrentUser();
    currentusertimeout = setTimeout( function() {
      $j('#currentuser').toggleClass('active', false );
    }, 1750 );
    
  });
  
}

function setupRateWidget() {
  
  $j('.ratewidget').each( function() {
    
    $j(this).find('li a').click( function(e) {
      e.preventDefault();
    });
    
    if ( $j(this).attr('nojs') == '1' )
      return;
    
    $j(this).find('li').each( function() {
      $j(this).data('hasfull', $j(this).hasClass('full') );
    });
    
    var resettimer,
        savedthis   = $j(this),
        resetwidget = function() {
      
      savedthis.find('li').each( function() {
        
        if ( $j(this).data('hasfull') )
          $j(this).addClass('full');
        else
          $j(this).removeClass('full');
        
      });
      
      resettimer = false;
      
    };
    
    $j(this).find('li').mouseenter( function() {
      
      $j(this).prevAll().addClass('full');
      $j(this).addClass('full');
      $j(this).nextAll().removeClass('full');
      
    });
    
    $j(this).find('ul').mouseleave( function() {
      
      if ( resettimer )
        clearTimeout( resettimer );
      
      resettimer = setTimeout( resetwidget, 1500 );
      
    });
    
    $j(this).find('li a').click( function(e) {
      
      e.preventDefault();
      
      $j.ajax({
        url: $j(this).attr('href'),
        method: 'GET',
        dataType: 'json',
        beforeSend: function() {
          savedthis.find('.spinner').show();
          savedthis.find('ul').fadeTo( 200, 0.3 );
        },
        success: function( data ) {
          
          if ( !data || typeof data != 'object' )
            return;
          
          if ( data.notloggedin )
            return wantAjaxLogin( true );
          
          var index = Math.round( parseFloat( data.rating, 10 ) ) - 1;
          
          if ( index < 0 )
            index = 0;
          
          savedthis.find('li').eq( index ).data('hasfull', true ).prevAll().data('hasfull', true );
          savedthis.find('.count').text( data.numberofratings );
          
          savedthis.find('li a').removeAttr('href').unbind('click');
          savedthis.find('li').unbind('mouseenter mouseleave');
          resetwidget();
          
        },
        complete: function() {
          savedthis.find('.spinner').hide();
          savedthis.find('ul').fadeTo( 200, 1 );
        }
      });
      
      return false;
      
    });
    
  });
  
}

function setupVideoUpload() {
  
  if ( !swfupload )
    return;
  
  $j('#upload, #uploadcontent').submit( function( e ) {
    
    e.preventDefault();
    
    if ( $j('#tos').length && !$j('#tos:checked').val() )
      return false;
    
    if ( !swfupload.getFile(0) ) { // no files in the queue
      
      alert( messages.filenotfound );
      return false;
      
    }
    
    $j('.submitbutton').get(0).disabled = true;
    swfupload.addPostParam('swfupload', '1');
    
    var form = $j(this).serializeArray();
    for ( var i = 0, j = form.length; i < j; i++ )
      swfupload.addPostParam( form[ i ].name, form[ i ].value );
    
    swfupload.startUpload();
    
    return false;
    
  });
  
}

function swfuploadFallback() {
  
  $j('.submitbutton').hide();
  try {
    swfupload.destroy();
  } catch( e ) {}

}

function onUploadStart( file ) {

  $j('.progresswrap').removeClass('red blue').addClass('green');
  $j('.progressstatus').text( messages.uploading ); // global object for localized strings
  try {
    $j( document ).trigger('uploadstart.swfupload', [ file ] );
  } catch ( e ) {
    return false;
  }
  
  return true; // returning false will cancel the upload
}

function onUploadProgress( file, uploaded, total ) {
  
  try {
    
    trackSpeed( file, uploaded );
    var percent = Math.ceil( ( uploaded / total ) * 100);
    $j('.progressbar').width( percent + '%');
    
  } catch ( e ) {}
  
  var stats = speedhistory[ file.id ];
  $j('.progressspeed').text( formatBPS( stats.averagespeed || 0 ) );
  $j('.progresstime').text( formatTime( stats.timeremaining ) || '' );
  
  if ( uploaded === total ) {
    
    $j('.progresstime').text('');
    $j('.progresswrap').removeClass('red green').addClass('blue');
    $j('.progressstatus').html( messages.uploaded + ' <img src="' + STATIC_URI + 'images/spinner.gif"/>' );
    
  }
  
}

function onUploadError( file, code, message ) {
  
  $j('.progressbar').width('0px');
  $j('.progresswrap').removeClass('blue green').addClass('red');
  $j('.progressstatus').text( messages.uploaderror );
  
  swfupload.setButtonDisabled( false );
  
  /*
  switch (errorCode) {
    case SWFUpload.UPLOAD_ERROR.MISSING_UPLOAD_URL:
      alert("There was a configuration error.  You will not be able to upload a resume at this time.");
      this.debug("Error Code: No backend file, File name: " + file.name + ", Message: " + message);
      return;
    case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
      alert("You may only upload 1 file.");
      this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
      return;
    case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
    case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
      break;
    default:
      alert("An error occurred in the upload. Try again later.");
      this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
      return;
  }

  switch (errorCode) {
    case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
      progress.setStatus("Upload Error");
      this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
      break;
    case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
      progress.setStatus("Upload Failed.");
      this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
      break;
    case SWFUpload.UPLOAD_ERROR.IO_ERROR:
      progress.setStatus("Server (IO) Error");
      this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
      break;
    case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
      progress.setStatus("Security Error");
      this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
      break;
    case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
      progress.setStatus("Upload Cancelled");
      this.debug("Error Code: Upload Cancelled, File name: " + file.name + ", Message: " + message);
      break;
    case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
      progress.setStatus("Upload Stopped");
      this.debug("Error Code: Upload Stopped, File name: " + file.name + ", Message: " + message);
      break;
  }
  } catch (ex) {}
  */

}

function onUploadSuccess( file, data ) {
  
  try {
    if ( typeof JSON === "object" && JSON.parse )
      var data = JSON.parse( data );
    else
      eval('var data = ' + data );
  } catch( e ) {}
  
  if ( !data || typeof( data ) !== 'object' )
    data = {};
  
  $j( document ).trigger('uploadsuccess.swfupload', [ file, data ] );
  if ( !data.error && data.url )
    return location.href = data.url;
  
  if ( messages[ data.error ] )
    var message = messages[ data.error ];
  else
    var message = messages.unknownerror + ' \n' + dump( data );
  
  $j('.progresswrap').removeClass('blue green').addClass('red');
  $j('.progressstatus').text( message );
  alert( message );
  
  if ( data.url )
    return location.href = data.url;
  
}

function dump( arr ) {
  
  var dumped_text = '';
  
  if( typeof( arr ) == 'object' ) {
    
    for( var item in arr ) {
      
      var value = arr[ item ];

      if( typeof( value ) == 'object' ) {
        
        dumped_text += ' "' + item + '" => {';
        dumped_text += dump( value );
        dumped_text += '}';
        
      } else {
        
        dumped_text += ' "' + item + '" => \"' + value + '\",';
        
      }
      
    }
    
  } else {
    
    dumped_text = arr + ' ('+ typeof( arr ) + ')';
    
  }
  
  return dumped_text;
  
}

function onUploadComplete( file ) {
  
  $j( document ).trigger('uploadcomplete.swfupload', [ file ] );
  // need to pass the user to the next screen where he/she can edit the info about the video
}

function onFileQueueError( file, error, message ) {
  var errors = SWFUpload.QUEUE_ERROR;
  
  switch ( error ) {
    case errors.QUEUE_LIMIT_EXCEEDED:
      break;
    case errors.FILE_EXCEEDS_SIZE_LIMIT:
      alert( messages.filetoobig );
      break;
    case errors.ZERO_BYTE_FILE:
      alert( messages.zerobytefile );
      break;
    case errors.INVALID_FILETYPE: // users can circumvent the filetype restriction
      alert( messages.invalidfiletype );
      break;
  }
  
  swfupload.setButtonDisabled( false );
  $j( document ).trigger('filequeueerror.swfupload', [ file, error, message ] );
  
}

function onFileQueueSuccess( file ) {

  $j('#videouploadprogress').show();
  $j('.progresswrap').removeClass('red blue').addClass('green');
  $j('.progressname').text( file.name );
  
  try {
    $j( document ).trigger('filequeuesuccess.swfupload', [ file ] );
  } 
  catch ( e ) {
    return false;
  }

  //$j('#videobrowsecontainer').hide(); // hide flash container, if hidden swfupload fails, so dont hide it
  swfupload.setButtonDisabled( true ); // disable the flash button and ignore clicks

  return true;
}

function onFileDialogStart() {
  $j( document ).trigger('filedialogstart.swfupload');
}

function onFileDialogComplete() {
  $j( document ).trigger('filedialogcomplete.swfupload');
}

// "copied" from swfupload.speed plugin
var speedhistory = {};
function trackSpeed( file, uploaded ) {
  var history = speedhistory[ file.id ],
      time = (new Date()).getTime();
  
  if ( !history )
    speedhistory[ file.id ] = {};
  
  
  if ( !history.starttime ) {
    
    history.starttime     = time;
    history.lasttime      = time;
    history.currentspeed  = 0;
    history.averagespeed  = 0;
    history.timeremaining = 0;
    history.percent       = uploaded / file.size * 100;
    history.uploaded      = uploaded;
    
  }
  
  var deltatime = time - history.lasttime;
  var deltabytes = uploaded - history.uploaded;
  
  if ( deltabytes === 0 || deltatime === 0 )
    return;
  
  history.lasttime = time;
  history.uploaded = uploaded;
  
  history.currentspeed = ( deltabytes * 8 ) / ( deltatime / 1000 );
  history.averagespeed = ( uploaded * 8 ) / ( ( time - history.starttime ) / 1000 );
  
  history.timeremaining = ( file.size - uploaded ) * 8 / history.averagespeed;
  history.percent = uploaded / file.size * 100;
  
}

function formatUnits( baseNumber, unitDivisors, unitLabels, singleFractional ) {
  var i, j, unit, unitDivisor, unitLabel;

  if ( baseNumber === 0 ) {
    return "0 " + unitLabels[ unitLabels.length - 1 ];
  }
  
  if ( singleFractional ) {
    unit = baseNumber;
    unitLabel = unitLabels.length >= unitDivisors.length ? unitLabels[ unitDivisors.length - 1 ] : "";
    
    for (i = 0, j = unitDivisors.length; i < j; i++) {
      
      if ( baseNumber >= unitDivisors[ i ] ) {
        
        unit = ( baseNumber / unitDivisors[ i ] ).toFixed(2);
        unitLabel = unitLabels.length >= i ? " " + unitLabels[i] : "";
        break;
        
      }
      
    }
    
    return unit + unitLabel;
    
  } else {
    var formattedStrings = [];
    var remainder = baseNumber;
    
    for (i = 0, j = unitDivisors.length; i < j; i++) {
      
      unitDivisor = unitDivisors[ i ];
      unitLabel = unitLabels.length > i ? " " + unitLabels[ i ] : "";
      
      unit = remainder / unitDivisor;
      if ( i < unitDivisors.length -1 )
        unit = Math.floor( unit );
      else
        unit = unit.toFixed(2);
      
      if (unit > 0) {
        
        remainder = remainder % unitDivisor;
        
        formattedStrings.push( unit + unitLabel );
        
      }
      
    }
    
    return formattedStrings.join(" ");
  }
  
}

function formatBPS( baseNumber ) {
  var bpsUnits = [1073741824, 1048576, 1024, 1], bpsUnitLabels = ["Gbps", "Mbps", "Kbps", "bps"];
  
  return formatUnits( baseNumber, bpsUnits, bpsUnitLabels, true);
  
}

function formatTime( baseNumber ) {
  var timeUnits = [86400, 3600, 60, 1], timeUnitLabels = ["d", "h", "m", "s"];
  
  return formatUnits( baseNumber, timeUnits, timeUnitLabels, false);
  
}

function formatBytes( baseNumber ) {
  var sizeUnits = [1073741824, 1048576, 1024, 1], sizeUnitLabels = ["GB", "MB", "KB", "bytes"];
  
  return formatUnits( baseNumber, sizeUnits, sizeUnitLabels, true);
  
}

function formatPercent( baseNumber ) {
  return baseNumber.toFixed(2) + " %";
}

// FLASHDEFAULTS
var flashdefaults = {
  params: {
    quality: "high",
    bgcolor: "#000",
    allowscriptaccess: "sameDomain",
    allowfullscreen: "true"
  }
}