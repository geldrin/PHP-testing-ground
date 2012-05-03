var $j = jQuery.noConflict();
$j(document).ready(function() {
  
  $j('#systemmessageclose a').click( function() {
    
    $j('#systemmessage').slideUp(150);
    return false;
    
  });
  
  runIfExists('#headerlogin', setupHeaderLogin );
  runIfExists('#headersearch', setupHeaderSearch );
  runIfExists('.ratewidget', setupRateWidget );
  runIfExists('#uploadrow', setupUpload );
  
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
  
  var fixCurrentUserMenu = function() {
    $j('#currentusermenu').css({ height: $j('#currentusermenu').height() + 'px' });
  };
  
  runIfExists('#currentusermenu', fixCurrentUserMenu );
  
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
    
    if ( $j(this).attr('data-nojs') == '1' )
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

function setupUpload() {
  
  $j('#upload, #uploadcontent').each( function() {
    
    $j(this).attr('onsubmit', null );
    
  });
  
  $j('#upload, #uploadcontent').submit( function( e ) {
    
    if ( $j(this).attr('id') == 'upload' && !check_upload() )
      return false;
    else if ( $j(this).attr('id') == 'uploadcontent' && !check_uploadcontent() )
      return false;
    
    var filename = $j('#file').val().match(/.*[\\/](.+)$/);
    if ( filename )
      filename = filename[1];
    
    $j('#uploadrow').show();
    $j('.progressname').text( filename );
    $j('tr.buttonrow').hide();
    
    setTimeout( function() {
      $j('#uploadframe').attr('src', BASE_URI + language + '/recordings/progress' );
    }, 1000 );
    
  });
  
}

function setupUploadIframe() {
  getProgress();
}

function getProgress() {
  
  var jq = $j;
  
  if ( window.parent )
    jq = window.parent['$j'];
  
  $j.ajax({
    url: BASE_URI + language + '/recordings/getprogress',
    type: 'GET',
    data: { uploadid: jq('#uploadid').val() },
    dataType: 'json',
    timeout: 2000,
    cache: false,
    success: function( data ) {
      
      if ( data.status == 'OK' ) {
        
        if ( data.data ) {
          
          trackSpeed( data.data.current, data.data.total );
          var percent = Math.ceil( ( data.data.current / data.data.total ) * 100);
          jq('.progressbar').width( percent + '%');
          jq('.progressspeed').text( formatBPS( history.averagespeed || 0 ) );
          jq('.progresstime').text( formatTime( history.timeremaining ) || '' );
          
        }
        
        setTimeout( getProgress, 1000 );
        
      } else
        alert( data.message );
      
    }
  });
  
}

// "copied" from swfupload.speed plugin
var history = {};
function trackSpeed( uploaded, total ) {
  
  if ( uploaded == 0 )
    return;
  
  var time = (new Date()).getTime();
  
  if ( !history.starttime ) {
    
    history.starttime     = time;
    history.lasttime      = time;
    history.currentspeed  = 0;
    history.averagespeed  = 0;
    history.timeremaining = 0;
    history.percent       = uploaded / total * 100;
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
  
  history.timeremaining = ( total - uploaded ) * 8 / history.averagespeed;
  history.percent = uploaded / total * 100;
  
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
    allowfullscreen: "true",
    wmode: 'opaque'
  }
}