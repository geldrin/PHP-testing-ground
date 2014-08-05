var $j = jQuery.noConflict();
$j(document).ready(function() {
  
  $j('#systemmessageclose a').click( function() {
    
    $j('#systemmessage').slideUp(150);
    return false;
    
  });
  
  $j.datepicker.setDefaults($j.datepicker.regional[ language ]);
  $j.timepicker.setDefaults($j.timepicker.regional[ language ]);

  runIfExists('#headerlogin', setupHeaderLogin );
  runIfExists('#headersearch', setupHeaderSearch );
  runIfExists('.ratewidget', setupRateWidget );
  runIfExists('#uploadrow', setupVideoUpload );
  runIfExists('.categoryiconitem', setupCategoryIconSelector );
  runIfExists('#infotoggle', setupInfoToggle );
  runIfExists('#player', setupPlayer );
  runIfExists('.sort', setupSort );
  runIfExists('#embedlink', setupEmbed );
  runIfExists('.confirm', setupConfirm );
  runIfExists('input[name=accesstype]', setupAccesstype );
  runIfExists('#chat', setupLiveChat );
  runIfExists('input[name=feedtype]', setupFeed );
  runIfExists('#channels', setupChannels );
  runIfExists('.liveembed', setupLiveEmbed );
  runIfExists('.livecompatibility', setupLiveCompatibility );
  runIfExists('.streambroadcastlink', setupBroadcastLink );
  runIfExists('#feeds .needpoll', setupStreamPoll );
  runIfExists('#currentuser', setupCurrentUser );
  runIfExists('#search_advanced', setupSearch );
  runIfExists('#recordings_modifycontributors', setupContributors );
  runIfExists('#contributors_create, #contributors_modify', setupContributorEdit );
  runIfExists('#live_modify, #live_create', setupLiveAccessCheck );
  runIfExists('#live_modifyfeed, #live_createfeed', setupLiveFeed );
  runIfExists('#recordings_modifysharing', setupSharing );
  runIfExists('#recordings_modifydescription', setupDescription );
  runIfExists('#live_create, #live_modify', setupLiveCreate );
  runIfExists('#organizations_createnews, #organizations_modifynews', setupLiveCreate );
  runIfExists('#groups_invite', setupGroupInvitation );
  runIfExists('#timestampdisabledafter', setupDisabledAfter );
  runIfExists('#recordingssearch', setupRecordingsSearch );
  runIfExists('#users_invite, #users_editinvite', setupUserInvitation );
  runIfExists('#orderrecordings', setupOrderRecordings );
  runIfExists('.togglesmallrecordings', setupToggleRecordings );
  runIfExists('#comments', setupComments );
  runIfExists('#livestatistics', setupLivestatistics );
  runIfExists('#recordingdownloads', setupRecordingDownloads );

  if ( needping )
    setTimeout( setupPing, 1000 * pingsecs );
  
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
  
  var elems = $j( selector );
  if ( elems.length > 0 )
    func( elems );
  
}

function setupConfirm( elems ) {
  
  elems.click( function(e) {
    
    var confirmquestion = $j(this).attr('data-confirm');
    if ( !confirmquestion )
      confirmquestion = l.areyousure;
    
    if ( !confirm( confirmquestion ) )
      e.preventDefault();
    
  });
  
}

function handleFlashLoad(e) {
  if (e.success)
    return;

  alert( l.flashloaderror );

}

function setupSearch() {
  
  $j('.datepicker').datepicker({
    dateFormat: 'yy-mm-dd',
    changeMonth: true,
    changeYear: true,
    maxDate: new Date()
  });
  
}

function setupCurrentUser( elem ) {
  
  if ( BROWSER.mobile && !BROWSER.tablet )
    return;
  
  elem.show();
  
  $j('#currentusername').on('click', function( e ) {
    e.preventDefault();
    e.stopImmediatePropagation();
    
    if ( $j('#currentuser').hasClass('active') ) {
      
      var menuevent = $j.Event('menuclose.dam');
      $j('#currentusername').trigger( menuevent );
      
      if ( !menuevent.isDefaultPrevented() )
        $j('#currentuser').removeClass('active');
      
    } else {
      
      var menuevent = $j.Event('menuopen.dam');
      $j('#currentusername').trigger( menuevent );
      
      if ( !menuevent.isDefaultPrevented() )
        $j('#currentuser').addClass('active');
      
    }
    
  });
  
  $j('body').click( function(e) {
    
    if ( $j('#currentuser').find( e.target ).length == 0 ) {
      
      var menuevent = $j.Event('menuclose.dam');
      $j('#currentusername').trigger( menuevent );
      
      if ( !menuevent.isDefaultPrevented() )
        $j('#currentuser').removeClass('active');
      
    }
    
  });
  
  var fixCurrentUserMenu = function( elem ) {
    elem.css({ height: elem.height() + 'px' });
  };
  
  runIfExists('#currentusermenu', fixCurrentUserMenu );
  
}

function setupStreamPoll( elems ) {
  
  var polldata = {id: []};
  var pollurl  = language + '/live/getstreamstatus';
  elems.each(function() {
    
    var id = $j(this).attr('data-streamid');
    if ( id )
      polldata.id.push( id );
    
  });
  
  var updateStatuses = function( data ) {
    
    if ( !data || data.status != 'success' )
      return;
    
    for (var i = data.data.length - 1; i >= 0; i--) {
      
      var stream = data.data[i];
      var elem   = $j('#stream' + stream.id );
      
      if ( elem.attr('data-streamstatus') == stream.status )
        continue;
      
      elem.attr('data-streamstatus', stream.status );
      elem.html( stream.html );
      
    };
    
    setTimeout( poll, data.polltimems || 5000 );
    
  };
  
  var poll = function() {
    
    $j.ajax({
      cache   : false,
      data    : polldata,
      dataType: 'json',
      success : updateStatuses,
      type    : 'GET',
      url     : pollurl
    });
    
  };
  
  poll();
  
}

function setupBroadcastLink( elems ) {
  
  elems.click( function( e ) {
    e.preventDefault();
    
    var wrap = $j(this).parents('tr').next('.streambroadcastwrap');
    $j('.streambroadcastwrap').not( wrap ).hide();
    wrap.toggle();
    
  });
  
}

function setupLiveCompatibility( elems ) {
  
  elems.change(function() {
    
    var checkedelems = elems.filter(':checked');
    var mobilefound  = false;
    var desktopfound = false;
    
    $j.each(checkedelems, function() {
      
      if ( $j(this).val() == 'isdesktopcompatible' )
        desktopfound = true;
      else
        mobilefound = true;
      
    })
    
    if ( desktopfound && !mobilefound )
      $j('.smallinfo.desktop').show();
    else
      $j('.smallinfo.desktop').hide();
    
    if ( mobilefound )
      $j('.smallinfo.mobile').show();
    else
      $j('.smallinfo.mobile').hide();
    
  }).change();
  
}

function setupChannels() {
  
  var fixmargins = function() {
    
    $j('#channels li li .channelname').each(function() {
      
      var level = $j(this).attr('class').match(/level(\d+)/)[1];
      var margin  = 30 + ( 15 * ( level - 1 ) );
      $j(this).css('paddingLeft', margin + 'px' );
      
    });
    
  };
  
  $j('#channellink').click(function(e) {
    e.preventDefault();
    
    $j(this).toggleClass('active');
    $j('#channels').toggle();
  });
  
  fixmargins();
  
  $j('#channels .actions a').live('click', function(e) {
    
    e.preventDefault();
    
    if ( $j(this).hasClass('loading') )
      return;
    
    $j.ajax({
      type: 'GET',
      url: $j(this).attr('href'),
      dataType: 'json',
      success: function(data) {
        
        if ( typeof( data ) != 'object' || data.status != 'success' || !data.html )
          return;
        
        $j('#channelslist').html( data.html );
        fixmargins();
        
      },
      complete: function() {
        $j(this).removeClass('loading');
      }
    })
    
    $j(this).addClass('loading');
    
  });
  
}

function setupLiveEmbed( elems ) {
  
  elems.click(function( e ) {
    
    e.preventDefault();
    
    var row = $j(this).parents('tr').next('.liveembedrow');
    $j('.liveembedrow').not( row ).hide();
    row.toggle();
    
  });
  
  var updateIframeSrc = function( elem, needchat, needfullplayer ) {
    
    var root = elem.parents('.liveembedwrap');
    var url  = root.attr('data-embedurl');
    var txt  = root.find('textarea').val();
    var addextraheight = true;
    var width  = 950;
    var height = 980;
    
    if ( needchat == '0' ) {
      
      url += '&chat=false';
      addextraheight = false;
      
    }
    
    if ( needfullplayer == '0' ) {
      
      url   += '&fullplayer=false';
      width  = 480;
      height = 860;
      
    }
    
    if ( !addextraheight )
      height -= 330;
    
    txt = txt.replace(/src="(.*?)"/, 'src="' + url + '"');
    txt = txt.replace(/width=".*?"/, 'width="' + width + '"');
    txt = txt.replace(/height=".*?"/, 'height="' + height + '"' );
    
    root.find('textarea').val( txt );
    
  };
  
  $j('.chat, .fullplayer').change(function() {
    
    var root       = $j(this).parents('.liveembedwrap');
    var chat       = root.find('.chat:checked').val();
    var fullplayer = root.find('.fullplayer:checked').val();
    
    if ( !root.find('.chat').length )
      chat = 0;
    
    updateIframeSrc( $j(this), chat, fullplayer );
    
  }).change();
  
}

function setupFeed( elems ) {
  
  elems.change(function() {
    
    if ( elems.filter(':checked').val() == 'vcr' ) {
      
      $j('#recordinglinkid, input[name=needrecording]').parents('tr').show();
      
    } else {
      
      $j('#recordinglinkid, input[name=needrecording]').parents('tr').hide();
      
    }
    
  }).change();
  
}

function setupSort() {
  
  var sorttimeout;
  var removeHover = function() {
    $j('.sort .item').removeClass('hover');
    sorttimeout = null;
  };
  var fixupList = function( self ) {
    
    if ( self.css('display') == 'block' ) {
      
      self.children('ul').width( self.children('.title').outerWidth() );
      
    }
    
  };
  
  $j('.sort .item').on('mouseleave',  removeHover );
  $j('.sort .item').on('mouseenter',  function() { fixupList( $j(this) ); });
  $j('.sort .item').on('click', function( e ) {
    if ( !$j(e.target).hasClass('title') )
      return;
    
    e.preventDefault();
    var abort = false;
    
    if ( $j(this).hasClass('hover') )
      abort = true;
    
    $j('.sort .item').removeClass('hover');
    if ( abort )
      return;
    
    $j(this).addClass('hover');
    
    fixupList( $j(this) );
    
    if ( sorttimeout )
      clearTimeout( sorttimeout );
    
    sorttimeout = setTimeout( removeHover, 30 * 1000 );
    
  });
  
}

function setupCategoryIconSelector() {

  $j('.categoryiconitem input[type=radio]:checked')
      .parents('.categoryiconitem')
      .find('label img')
      .addClass('selected')
  ;

  $j('.categoryiconitem img').click( function(e) {
    e.preventDefault();
    $j('.categoryiconitem img.selected').removeClass('selected');
    $j( this ).addClass('selected')
      .parents('.categoryiconitem')
      .children('input[type=radio]')
      .attr('checked','checked')
    ;
  });

}

function setupPlayer() {
  
  if ( $j('#pagebg').length == 0 )
    return; // embedded
  
  var adjustPageBGHeight = function() {
    
    var playerbgheight =
      ( $j('#player').offset().top - $j('#pagebg').offset().top ) +
      $j('#player').outerHeight(true) + 10
    ;
    
    $j('#pagebg').css('height', playerbgheight + 'px');
    
  };
  
  adjustPageBGHeight();
  
  $j('#currentusername').bind('menuopen.dam menuclose.dam', function(e) {
    
    var animationduration = 200;
    var menu              = $j('#currentusermenu');
    
    if ( e.type == 'menuopen' ) {
      
      var playertop  = $j('#player').offset().top;
      var menubottom = menu.offset().top + menu.outerHeight(true);
      var margintop  = menubottom - playertop + 30; // a +20 a #header margin-bottomja amivel coalescelunk
      
      if ( margintop <= 20 )
        margintop = 0;
      
      menu.css('opacity', 0);
      menu.animate({ opacity: 1 }, animationduration );
      $j('.title.recording').animate({ marginTop: margintop }, animationduration, adjustPageBGHeight);
      
    } else {
      
      e.preventDefault();
      menu.animate({ opacity: 0 }, animationduration );
      $j('.title.recording').animate({ marginTop: '0' }, animationduration, function() {
        
        adjustPageBGHeight();
        $j('#currentuser').removeClass('active');
        
      });
      
    }
    
    
  });
  
}

function setupInfoToggle() {
  
  $j('#detaillink').click( function(e) {
    e.preventDefault();
    if ( $j('#metadatatable').is(':visible') ) {
      
      $j('#metadatatable').hide();
      $j(this).text( $j(this).attr('data-show') );
      
    } else {
      
      $j('#metadatatable').show();
      $j(this).text( $j(this).attr('data-hide') );
      
    }
    
  });
  
}

function setupHeaderSearch() {
  
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
  
  if ( BROWSER.mobile && !BROWSER.tablet )
    return;
  
  $j('#headerloginactionlink').on('click', function( e ) {
    e.preventDefault();
    $j('#headerloginform, #headerloginactions').toggle();
  });
  
}

function setupRateWidget() {
  
  $j('.ratewidget').each( function() {
    
    $j(this).find('li a').click( function(e) {
      e.preventDefault();
    });
    
    if ( $j(this).attr('data-canrate') != '1' )
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
      
      if ( resettimer ) {
        
        clearTimeout( resettimer );
        resettimer = null;
        
      }
      
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

function setupEmbed() {
  
  var embedcode = $j('#embedcode').val();
  var url       = embedcode.match(/src="(.*?)"/)[1];
  
  $j('#embedlink').click(function(e) {
    e.preventDefault();
    
    $j(this).toggleClass('active');
    $j('#embed').toggle();
  });
  
  $j('#embed input').bind('change keyup blur', function( e ) {
    
    var id   = $j(this).attr('id');
    var code = $j('#embedcode').val();
    
    if ( id.match('^embedstart_([hms])$') ) {
      
      var value = $j(this).val().replace( /[^\d]/g, '' );
      if ( value.length > 2 )
        value = value.substr( 1, 2 );
      if ( id.match('^embed_start_([ms])$') && value > 59 )
        value = 59;
      
      $j(this).val( value );
      
    }
    
    var params = [];
    var start  =
      $j('#embedstart_h').val() + 'h' +
      $j('#embedstart_m').val() + 'm' +
      $j('#embedstart_s').val() + 's'
    ;
    
    if ( start != '00h00m00s' )
      params.push('start=' + start );
    
    if ( $j('#embedautoplay_yes:checked').length != 0 )
      params.push('autoplay=yes');
    
    if ( $j('#embedfullscale_yes:checked').length != 0 ) {
      
      params.push('fullscale=yes');
      code = code.replace(/width=".*?"/, 'width="950"');
      code = code.replace(/height=".*?"/, 'height="' + $j('#embedcode').attr('data-fullscaleheight') + '"' );
      
    } else {
      
      code = code.replace(/width=".*?"/, 'width="480"');
      code = code.replace(/height=".*?"/, 'height="' + $j('#embedcode').attr('data-normalheight') + '"' );
      
    }
    
    if ( params.length == 0 ) {
      
      $j('#embedcode').val( code.replace(/src="(.*?)"/, 'src="' + url + '"') )
      return;
      
    }
    
    var newurl = url + '?' + params.join('&');
    $j('#embedcode').val( code.replace(/src="(.*?)"/, 'src="' + newurl + '"') )
    
  });
  
}

function setupAccesstype( elem ) {
  
  elem.change(function() {
    
    var elemvalue = elem.filter(':checked').val();
    switch( elemvalue ) {
      case 'public':
      case 'registrations':
        $j('#departmentscontainer, #groupscontainer').parents('tr').hide();
        break;
      
      case 'departments':
        $j('#departmentscontainer').parents('tr').show();
        $j('#groupscontainer').parents('tr').hide();
        break;
      
      case 'groups':
        $j('#departmentscontainer').parents('tr').hide();
        $j('#groupscontainer').parents('tr').show();
        break;
      
      case 'departmentsorgroups':
        $j('#departmentscontainer, #groupscontainer').parents('tr').show();
        break;
      
    }
    
  }).change();
  
}

function recordingUpload( options ) {
  
  var mimetypes = [];
  var filetypes = allowedfiletypes.split(',');
  for (var i = filetypes.length - 1; i >= 0; i--) {
    mimetypes.push( '.' + filetypes[i] );
  };
  
  if ( $j.browser.opera ) {
    mimetypes.unshift('audio/*');
    mimetypes.unshift('video/*');
  }
  
  options = $j.extend({
    runtimes: 'html5,flash',
    flash_swf_url: 'swf/plupload.flash.swf',
    container: 'uploadrow',
    browse_button: 'uploadbrowse',
    max_file_size: '10gb',
    url: uploadchunkurl,
    chunk_size: '10mb',
    multipart_params: {},
    headers: {'X-Requested-With': 'XMLHttpRequest'},
    filters: [
      {
        title: "Media files",
        extensions: allowedfiletypes
      }
    ],
    multiplefilesallowed: true,
    mimetypes: mimetypes
  }, options || {});
  
  this.speeddata = {};
  this.uploader  = new plupload.Uploader( options );
  this.uploader.bind('Init', this.init );
  this.uploader.bind('QueueChanged', this.onQueueChanged );
  this.uploader.bind('FilesAdded', this.onFilesAdded );
  this.uploader.bind('FilesRemoved', this.onFilesRemoved );
  this.uploader.bind('BeforeUpload', this.beforeUpload );
  this.uploader.bind('UploadProgress', this.onProgress );
  this.uploader.bind('Error', this.onError );
  this.uploader.bind('UploadComplete', this.onComplete );
  this.uploader.bind('StateChanged', this.onStateChange );
  this.uploader.init();
  this.uploader.privdata = {
    'progresshtml': '<div class="progresswrap green hover">' + $j.trim( $j('.progresswrap').html() ) + '</div>',
    'base': this
  };
  this.track = $j.proxy( this.trackSpeed, this );
  
  $j('.progresswrap').remove();
  var self = this;
  
  $j('#uploadbrowse').click(function(e) {
    e.preventDefault();
    self.uploader.trigger('SelectFiles');
  });
  
  $j('.uploadremove').live('click', function(e) {
    e.preventDefault();
    
    if ( self.uploader.state != plupload.STOPPED )
      return;
    
    var fileid = $j(this).parents('.progresswrap').attr('data-id');
    var file   = self.uploader.getFile( fileid );
    
    self.uploader.removeFile( file );
  });
  
  $j('#uploadtoggle').attr('data-startupload', $j('#uploadtoggle').text() );
  $j('#uploadtoggle').click(function(e) {
    e.preventDefault();
    
    if ( self.uploader.state == plupload.STARTED )
      self.uploader.stop();
    else if ( self.uploader.state == plupload.STOPPED )
      $j('#uploadrow').parents('form').submit();
    
  });
}
recordingUpload.prototype.init = function( uploader, params ) {
  
  if ( params.runtime != 'html5' ) {
    $j('#bigfilewarning').show();
  }
  
};
recordingUpload.prototype.onStateChange = function( uploader ) {
  uploader.privdata.base.speeddata = {};
  
  if ( uploader.state == plupload.STOPPED ) {
    
    $j('.progresswrap').addClass('hover');
    $j('#uploadtoggle').removeClass('start').addClass('stop');
    $j('#uploadtoggle').text( $j('#uploadtoggle').attr('data-startupload') );
    $j('.progresstime, .progressspeed').hide();
    $j('.progressspeed').text('');
    $j('.progresstime').text('');
    $j('.progresswrap.green .progressstatus').hide();
    
  } else if ( uploader.state == plupload.STARTED ) {
    
    $j('.progresswrap').removeClass('hover');
    $j('#uploadtoggle').removeClass('stop').addClass('start');
    $j('#uploadtoggle').text( $j('#uploadtoggle').attr('data-stopupload') );
    $j('.progressstatus, .progresstime, .progressspeed').show();
    
    var serializedform = $j('#uploadrow').parents('form').serializeArray();
    var params         = { swfupload: 1 };
    
    for ( var i = 0, j = serializedform.length; i < j; i++ ) {
      
      var option = serializedform[i];
      if ( option.name == 'action' && uploader.settings.chunk_size )
        continue;
      
      params[ option.name ] = option.value;
      
    }
    
    uploader.settings.multipart_params = params;
    
  }
  
};
recordingUpload.prototype.onQueueChanged = function( uploader ) {
  
  if ( !uploader.settings.multiplefilesallowed && uploader.files.length > 1 ) {
    
    uploader.removeFile( uploader.files[ uploader.files.length - 1 ] );
    
  }
  
};
recordingUpload.prototype.onFilesAdded = function( uploader, files ) {
  
  for( var i = 0, j = files.length; i < j; i++ ) {
    
    var file = files[i];
    var id   = 'progress_' + file.id;
    
    $j( uploader.privdata.progresshtml ).attr('id', id ).attr('data-id', file.id ).appendTo('#uploadprogress');
    $j('#' + id + ' .progressname').text( file.name );
    
  }
  
};
recordingUpload.prototype.onFilesRemoved = function( uploader, files ) {
  
  // cant stop an upload mid-upload (on some backends) without stopping the whole queue, and there is a separate button for that
  if ( uploader.state != plupload.STOPPED )
    return;
  
  for( var i = 0, j = files.length; i < j; i++ ) {
    
    $j('#progress_' + files[i].id ).remove();
    
  }
  
};
recordingUpload.prototype.beforeUpload = function( uploader, file ) {
  
  if ( !uploader.settings.chunk_size )
    return;
  
  $j.ajax({
    url: checkresumeurl,
    type: 'POST',
    dataType: 'json',
    async: false,
    cache: false,
    data: {
      name     : file.name,
      size     : file.size,
      iscontent: $j('#iscontent').val()
    },
    success: function(data) {
      
      if ( typeof( data ) != 'object' || data.status != 'success' )
        return;
      
      file.startFromChunk = data.startfromchunk;
      
    }
  });
  
};
recordingUpload.prototype.onProgress = function( uploader, file, response ) {
  
  var id          = 'progress_' + file.id;
  var base        = uploader.privdata.base;
  var speed       = base.track( file );
  var uploadspeed = plupload.formatSize( speed.averagespeed || 0 );
  
  if ( uploadspeed != 'N/A' )
    uploadspeed += '/s';
  
  $j('#' + id + ' .progressbar').css('width', ( speed.percent.toFixed(2) ) + '%');
  $j('#' + id + ' .progressspeed').text( uploadspeed );
  $j('#' + id + ' .progresstime').text( base.formatTime( speed.timeremaining || 0 ) );
  
  if ( !response ) // if true, the file has uploaded
    return;
  
  try {
    var data = $j.parseJSON( response.response ) || {};
  } catch(e) {
    var data = {};
  }
  
  if ( data.status == 'success' ) {
    
    $j('#' + id).removeClass('green').addClass('blue');
    $j('#' + id + ' .progressstatus').text( l['upload_uploaded'] );
    uploader.privdata.gotourl = data.url;
    
  } else {
    
    $j('#' + id).removeClass('green').addClass('red');
    if ( data.error )
      $j('#' + id + ' .progressstatus').text( l[ data.error ] );
    
  }
  
};
recordingUpload.prototype.onError = function( uploader, error ) {
  
  switch ( error.code ) {
    
    case plupload.FILE_EXTENSION_ERROR:
      $j('#progress_' + error.file.id ).remove();
      alert( l.upload_invalidfiletype + '\n' + error.file.name );
      break;
    
    case plupload.INIT_ERROR:
      alert( l.upload_flasherror );
      break;
    
    case plupload.HTTP_ERROR:
      alert( l.upload_serverioerror );
      break;
    
    default:
      alert( l.upload_unknownerror + ': ' + error.code );
      break;
    
  }
  
};
recordingUpload.prototype.onComplete = function( uploader, files ) {
  
  if ( uploader.privdata.gotourl )
    location.href = uploader.privdata.gotourl;
  
};
recordingUpload.prototype.trackSpeed = function( file ) {
  var id           = file.id,
      uploaded     = file.loaded,
      total        = file.size,
      time         = (new Date()).getTime(),
      speedhistory = this.speeddata[id] || {};
  
  if ( !speedhistory.starttime ) {
    
    speedhistory.starttime     = time;
    speedhistory.lasttime      = time;
    speedhistory.currentspeed  = 0;
    speedhistory.averagespeed  = 0;
    speedhistory.timeremaining = 0;
    speedhistory.uploaded      = uploaded;
    speedhistory.percent       = uploaded / total * 100;
    this.speeddata[id]         = speedhistory;
    
  }
  
  var deltatime  = time - speedhistory.lasttime,
      deltabytes = uploaded - speedhistory.uploaded;
  
  if ( deltabytes === 0 || deltatime === 0 )
    return speedhistory;
  
  speedhistory.lasttime      = time;
  speedhistory.uploaded      = uploaded;
  speedhistory.currentspeed  = Math.round( deltabytes / ( deltatime / 1000 ) );
  speedhistory.averagespeed  = Math.round( uploaded / ( ( time - speedhistory.starttime ) / 1000 ) );
  speedhistory.timeremaining = Math.round( ( total - uploaded ) / speedhistory.averagespeed );
  speedhistory.percent       = uploaded / total * 100;
  return speedhistory;
  
};
recordingUpload.prototype.formatTime = function( seconds ) {
  
  var ret  = [];
  var days = Math.floor( seconds / 86400 );
  if ( days > 0 ) {
    
    ret.push( days + 'd');
    seconds -= days * 86400;
    
  }
  
  var hours = Math.floor( seconds / 3600 );
  if ( hours > 0 ) {
    
    ret.push( hours + 'h');
    seconds -= hours * 3600;
    
  }
  
  var minutes = Math.floor( seconds / 60 );
  if ( minutes > 0 ) {
    
    ret.push( minutes + 'm');
    seconds -= minutes * 60;
    
  }
  
  if ( ret.length == 0 )
    ret.push( seconds + 's');
  
  return ret.join(" ");
  
};

function setupVideoUpload() {
  
  $j('#file').parents('tr').hide();
  var uploader = new recordingUpload({
    multiplefilesallowed: $j('#uploadrow').attr('data-multiplefiles') != '0',
    drop_element: 'pagecontainer'
  });
  
  if ( uploader.uploader.features.dragdrop ) {
    
    $j('#draganddropavailable').show();
    
  }
  
  $j('#uploadrow').parents('form').submit( function( e ) {
    
    e.preventDefault();
    
    if ( $j('#tos').length && !$j('#tos:checked').val() ) {
      
      alert( l.tosaccept );
      return false;
      
    }
    
    if ( uploader.uploader.files.length == 0 ) { // no files in the queue
      
      alert( l.upload_nofilesfound );
      return false;
      
    }
    
    uploader.uploader.start();
    return false;
    
  });
  
}

// FLASHDEFAULTS
var flashdefaults = {
  params: {
    quality: "high",
    bgcolor: "#050505",
    allowscriptaccess: "sameDomain",
    allowfullscreen: "true",
    wmode: 'direct'
  }
}

function onLiveFlashLogin() {
  
  $j.ajax({
    cache   : false,
    dataType: 'json',
    success : function( data ) {
      
      if ( !data || data.status != 'success' )
        return;
      
      $j('#chatinputcontainer').html( data.html );
      
      
    },
    type    : 'GET',
    url     : chatloginurl
  });
  
}

function setupLiveChat() {

  chat = new livechat('#chatcontainer', chatpollurl, chatpolltime );
  
}

var livechat = function( container, pollurl, polltime ) {
  
  var self       = this;
  self.container = $j( container );
  self.pollurl   = pollurl;
  self.polltime  = polltime;
  self.topposition = null;
  self.needRecaptcha = $j('#live_createchat').attr('data-ishuman') == 'false';

  if ( self.container.find('#chatlist').length == 0 )
    self.container.hide();
  
  self.container.scrollTop( self.container.get(0).scrollHeight );
  $j('a.moderate').live('click', function(e) {
    e.preventDefault();
    self.onModerate( $j(this) );
  });
  $j('#live_createchat').live('submit', function(e) {
    e.preventDefault();
    self.onSubmit();
  });
  
  self.beforeSend = $j.proxy( self.beforeSend, self );
  self.onComplete = $j.proxy( self.onComplete, self );
  self.onPoll     = $j.proxy( self.onPoll, self );
  self.poll();
  
};
livechat.prototype.beforeSend = function() {
  $j('#spinner').show();
};
livechat.prototype.onComplete = function() {
  $j('#spinner').hide();
};
livechat.prototype.poll = function() {
  
  if ( !this.pollOptions ) {
    
    this.pollOptions = {
      success   : $j.proxy( function( data ) {
        this.onPoll( data );
      }, this ),
      complete  : $j.proxy( function( data ) {
        this.poll();
      }, this ),
      dataType  : 'json',
      type      : 'GET',
      url       : this.pollurl
    };
    
    this.timeout = $j.proxy( function() {
      this.pollOptions.data = 'lastmodified=' + this.container.attr('data-lastmodified');
      $j.ajax( this.pollOptions );
    }, this );
    
  }
  
  setTimeout( this.timeout, this.polltime );
  
};
livechat.prototype.onPoll = function( data ) {
  
  if ( data === null || typeof( data ) != 'object' )
    return;

  if ( data.status == 'error' && data.error ) {
    var msg = l[data.error] || data.error;
    alert( msg );
  }
  
  if ( data.status != 'success' || this.container.attr('data-lastmodified') == data.lastmodified )
    return;
  
  this.polltime = data.polltime;
  this.container.attr('data-lastmodified', data.lastmodified );
  this.container.html( data.html );
  
  if ( this.topposition !== null ) {
    this.container.scrollTop( this.topposition );
    this.topposition = null;
  } else
    this.container.scrollTop( this.container.get(0).scrollHeight );
  
  if ( this.container.find('#chatlist').length == 0 )
    this.container.hide();
  else
    this.container.show();
  
};
livechat.prototype.onModerate = function( elem ) {
  
  if ( !this.moderateOptions )
    this.moderateOptions = {
      beforeSend: this.beforeSend,
      complete  : this.onComplete,
      success   : this.onPoll,
      dataType  : 'json',
      type      : 'GET'
    };
  
  this.topposition = this.container.get(0).scrollTop;
  this.moderateOptions.url = elem.attr('href');
  $j.ajax( this.moderateOptions );
  
};
livechat.prototype.onSubmit = function() {

  if (this.needRecaptcha && !this.recaptchaShown) {

    var options = RecaptchaOptions || {};
    options.tabindex = 1;
    options.callback = Recaptcha.focus_response_field;
    options.theme    = 'clean';

    Recaptcha.create(
      $j('#recaptchacontainer').attr('data-recaptchapubkey'),
      'recaptchacontainer',
      options
    );
    $j('#recaptchacontainer').show()
    this.recaptchaShown = true;
    return;

  }

  if ( !this.submitOptions )
    this.submitOptions = {
      beforeSend: this.beforeSend,
      complete  : this.onComplete,
      success   : $j.proxy( function( data ) {
        
        this.onPoll( data );
        
        if ( typeof( data ) == 'object' && data.status == 'success' ) {

          if (this.needRecaptcha)
            this.needRecaptcha = false;

          $j('#text').val('');
        }

      }, this ),
      dataType  : 'json',
      type      : 'POST',
      url       : $j('#live_createchat').attr('action')
    };
  
  if ( !this.messageValid() )
    return;
  
  this.submitOptions.data = $j('#live_createchat').serializeArray();
  $j.ajax( this.submitOptions );

  if (this.recaptchaShown) {
    Recaptcha.destroy();
    $j('#recaptchacontainer').hide();
    this.recaptchaShown = false;
    $j('#text').focus();
  }

};
livechat.prototype.messageValid = function() {
  
  var text = $j('#chat #text').val();
  
  if ( text.length < 2 || text.length > 512 ) {
    alert( l.livechat_text_help );
    return false;
  }
  
  return true;
  
};

function setupContributors() {
  var html = $j.parseJSON( '"' + $j('#contributors').attr('data-listitemhtml') + '"' );
  var resetcontributor = function() {
    $j('#searchterm, #contributorid').val('');
    $j('#contributorrolerow, #addcontributor').hide();
  };
  
  $j('#searchterm').autocomplete({
    minLength: 2,
    source: BASE_URI + language + '/contributors/search',
    select: function( event, ui ) {
      $j('#searchterm').val( ui.item.label );
      $j('#contributorid').val( ui.item.value );
      $j('#contributorname').text( ui.item.label );
      $j('#contributorrolerow, #addcontributor').show();
      return false;
    }
  }).data( "autocomplete" )._renderItem = function( ul, item ) {
    
    var itemhtml = html.replace('__IMGSRC__', item.img ).replace('__NAME__', item.label );
    return $j("<li>")
      .data( "item.autocomplete", item )
      .append( "<a>" + itemhtml + "</a>" )
      .appendTo( ul )
    ;
    
  };
  $j('#searchterm').bind('autocompletesearch', function() {
    $j('#createcontributorrow').show();
  });
  
  $j('#cancelcontributor').click( function(e) {
    
    e.preventDefault();
    resetcontributor();
    
  });
  
  $j('#addcontributor').click( function( e ) {
    
    e.preventDefault();
    var data = $j(this).parents('form').serializeArray();
    data.shift(); // remove first element, the 'action'
    
    $j.ajax({
      //beforeSend:
      //complete:
      cache: false,
      success: function( data ) {
        
        updateContributorsAndCloseFancybox( data );
        resetcontributor();
        
      },
      data: data,
      dataType: 'json',
      type: 'POST',
      url: BASE_URI + language + '/recordings/linkcontributor'
    });
    
  });
  
  $j('#contributors .delete, #contributors .move').live('click', function(e) {
    
    e.preventDefault();
    $j.ajax({
      //beforeSend:
      //complete:
      cache: false,
      success: updateContributorsAndCloseFancybox,
      dataType: 'json',
      type: 'GET',
      url: $j(this).attr('href')
    });
    
  });
  
  $j('#createcontributor, #contributors .edit').fancybox({
    width: 555,
    height: 560,
    titleShow: false,
    type: 'iframe'
  });
  
  window.updateContributorsAndCloseFancybox = function( data ) {
    
    $j.fancybox.close();
    if ( !data || data.status != 'OK' )
      return;
    
    $j('#contributors ul').html( data.html );
    $j('#contributors').show();
    
    $j('#createcontributor, #contributors .edit').fancybox({
      width: 555,
      height: 560,
      titleShow: false,
      type: 'iframe'
    });
    
  };
  
}

function setupContributorEdit( elements ) {
  
  if ( !parent )
    return;
  /*
  if ( $j('#orgid').val() )
    $j('#selectedorganizationrow').show();
  
  $j('#organization').autocomplete({
    minLength: 2,
    source: BASE_URI + language + '/contributors/searchorganization',
    select: function( event, ui ) {
      $j('#organization').val( ui.item.label );
      $j('#orgid').val( ui.item.value );
      $j('#selectedorganization').text( ui.item.label );
      $j('#selectedorganizationrow').show();
      return false;
    }
  });
  
  $j('#clearorganization').click( function( e ) {
    
    e.preventDefault();
    $j('#organization, #orgid').val('');
    $j('#selectedorganizationrow').hide();
    
  });
  */
  $j('#showphotos').click( function(e) {
    
    e.preventDefault();
    $j('#contributorimages').toggle();
    
  });
  
  $j( elements ).submit( function( e ) {
    
    e.preventDefault();
    var data = $j(this).serializeArray();
    $j.ajax({
      //beforeSend:
      //complete:
      cache: false,
      success: parent.updateContributorsAndCloseFancybox,
      dataType: 'json',
      data: data,
      type: 'POST',
      url: $j(this).attr('action')
    });
    
  });
  
}

function setupPing() {
  
  $j.ajax({
    url     : language + '/jsonapi',
    cache   : false,
    type    : 'POST',
    dataType: 'json',
    data    : {
      parameters: '{"format":"json","layer":"controller","module":"users","method":"ping"}'
    },
    success : function( data, status, xhr ) {
      
      if ( typeof( data ) == 'object' && data.result == 'OK' && data.data ) {
        
        setTimeout( setupPing, 1000 * pingsecs );
        return;
        
      } else
        location.href = language + '/users/login';
      
    }
  });
  
}

function setupLiveAccessCheck() {
  
  var origaccesstype = $j('input[name=accesstype]:checked').val();
  if ( origaccesstype == 'departments' )
    var root = $j('#departmentscontainer');
  else if ( origaccesstype == 'groups' )
    var root = $j('#groupscontainer');
  
  var origaccess = [];
  if ( root ) {
    root.find('input[type=checkbox]:checked').each(function() {
      
      origaccess.push( $j(this).val() );
      
    });
  }
  
  checkAccessChanged = function() {
    
    var newaccesstype = $j('input[name=accesstype]:checked').val();
    if ( origaccesstype != newaccesstype )
      return true;
    
    var newaccess = [];
    root.find('input[type=checkbox]:checked').each(function() {
      newaccess.push( $j(this).val() );
    });
    
    if ( newaccess.length != origaccess.length )
      return true;
    
    origaccess.sort(function(a,b){return a-b});
    newaccess.sort(function(a,b){return a-b});
    
    for (var i = newaccess.length - 1; i >= 0; i--) {
      
      if ( origaccess[i] != newaccess[i] )
        return true;
      
    };
    
    return false;
    
  };
  
}

function setupDescription() {
  
  $j('#recordedtimestamp').datetimepicker({
    dateFormat : 'yy-mm-dd',
    changeMonth: true,
    changeYear : true,
    timeFormat : 'HH:mm',
    maxDateTime: new Date()
  });
  
}

function setupSharing() {
  
  $j('input[name=wanttimelimit]').change(function(e) {
    
    var wanttimelimit = $j('input[name=wanttimelimit]:checked').val() == '1';
    var parent        = $j('.datepicker').parents('tr');
    parent.toggle( wanttimelimit )
    
  }).change();
  
  setupDefaultDatePicker('.datepicker');
  
}

function setupDefaultDatePicker( elem ) {
  
  var options = getDefaultDateConfig( elem );
  $j(elem).datepicker( options );
  
}
function setupDefaultDateTimePicker( elem ) {
  
  var options = getDefaultDateConfig( elem );
  $j(elem).datetimepicker( options );
  
}
function getDefaultDateConfig( elem ) {
  
  var elem    = $j(elem);
  var options = {
    dateFormat : 'yy-mm-dd',
    changeMonth: true,
    changeYear : true,
    timeFormat : 'HH:mm'
  };
  
  if ( elem.attr('data-datefrom') )
    options.minDate = elem.attr('data-datefrom');
  
  if ( elem.attr('data-dateuntil') )
    options.maxDate = elem.attr('data-dateuntil');
  
  if ( elem.attr('data-dateyearrange') )
    options.yearRange = elem.attr('data-dateyearrange');
  
  var parseToDate = function( str ) {
    var matches = str.match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})$/);
    if ( !matches )
      return;
    
    matches[1] = parseInt( matches[1], 10 );
    matches[2] = parseInt( matches[2], 10 ) - 1; // YEAY JAVASCRIPT
    matches[3] = parseInt( matches[3], 10 );
    matches[4] = parseInt( matches[4], 10 );
    matches[5] = parseInt( matches[5], 10 );
    
    return new Date( matches[1], matches[2], matches[3], matches[4], matches[5] );
    
  };
  
  if ( elem.attr('data-datetimefrom') )
    options.minDateTime = parseToDate( elem.attr('data-datetimefrom') );
  
  if ( elem.attr('data-datetimeuntil') )
    options.maxDateTime = parseToDate( elem.attr('data-datetimeuntil') );
  
  return options;
  
}

function setupLiveCreate() {
  
  setupDefaultDateTimePicker('.datetimepicker');
  
}

function setupGroupInvitation() {
  
  var html = $j('#autocomplete-listitem').html();
  $j('#email').autocomplete({
    minLength: 2,
    source: BASE_URI + language + '/groups/searchuser',
    select: function( event, ui ) {
      $j('#email').val( ui.item.label );
      $j('#userid').val( ui.item.value );
      $j('#email').attr('disabled', true );
      $j('.delete').show();
      return false;
    }
  }).data( "autocomplete" )._renderItem = function( ul, item ) {
    
    var itemhtml = html.replace('__IMGSRC__', item.img ).replace('__NAME__', item.label );
    return $j("<li>")
      .data( "item.autocomplete", item )
      .append( "<a>" + itemhtml + "</a>" )
      .appendTo( ul )
    ;
    
  };
  
  $j('.delete').click( function( e ) {
    e.preventDefault();
    $j('#userid').val('');
    $j('#email').attr('disabled', false );
    $j('.delete').hide();
  });
  
}

function setupDisabledAfter() {
  
  $j('input[name=needtimestampdisabledafter]').change(function(e) {
    
    var wanttimestamp = $j('input[name=needtimestampdisabledafter]:checked').val() == '1';
    var parent        = $j('#timestampdisabledafter').parents('tr');
    parent.toggle( wanttimestamp )
    
  }).change();
  
  setupDefaultDateTimePicker('#timestampdisabledafter');
  $j('.timestampdisabledafter .presettime').click(function(e) {
    e.preventDefault();
    $j('#timestampdisabledafter').datetimepicker('setDate', $j(this).attr('data-date') );
  })
  
}

function checkAdvancedSearchInputs() {
  
  if (
    (
      $j('#q').val() &&
      $j('#q').val() != $j('#q').attr('data-origval')
    ) ||
    (
      $j('#contributorjob').val() &&
      $j('#contributorjob').val() != $j('#contributorjob').attr('data-origval')
    ) ||
    (
      $j('#contributororganization').val() &&
      $j('#contributororganization').val() != $j('#contributororganization').attr('data-origval')
    ) ||
    (
      $j('#contributorname').val() &&
      $j('#contributorname').val() != $j('#contributorname').attr('data-origval')
    )
  )
    return true;
  
  return false;
  
}


function setupRecordingsSearch() {
  
  var html = $j.parseJSON( '"' + $j('#recordingssearch').attr('data-listitemhtml') + '"' );
  $j('#term').autocomplete({
    minLength: 2,
    source: BASE_URI + language + '/recordings/search',
    select: function( event, ui ) {
      $j('#term').val( ui.item.label );
      $j('#recordingid').val( ui.item.value );
      //if (!confirm(l.areyousure)) return;

      var data = $j('#recordingssearchform').serializeArray();
      $j.ajax({
        //beforeSend:
        //complete:
        cache: false,
        success: function( data ) {
          location.href = location.href;
        },
        data: data,
        dataType: 'json',
        type: 'POST',
        url: BASE_URI + language + '/recordings/togglefeatured'
      });
      return false;
    }
  }).data( "autocomplete" )._renderItem = function( ul, item ) {
    
    var itemhtml = html.replace('__IMGSRC__', item.img ).replace('__NAME__', item.label );
    return $j("<li>")
      .data( "item.autocomplete", item )
      .append( "<a>" + itemhtml + "</a>" )
      .appendTo( ul )
    ;
    
  };
}

function setupLiveFeed() {

  $j('#moderationtype').change(function() {
    var modtype = $j(this).val();
    var elem    = $j('input[name=anonymousallowed]').parents('tr');
    elem.toggle( modtype != 'nochat' );
  }).change();

}

function setupUserInvitation() {

  $j('input[name=usertype]').change(function() {
    var type  = $j('input[name=usertype]:checked').val();
    var singleelems = $j('#email').parents('tr');
    var multielems  = $j('#invitefile, #encoding, #delimeter').parents('tr');
    singleelems.toggle( type == 'single' );
    multielems.toggle( type == 'multiple' );
  }).change();

  $j('input[name=contenttype]').change(function() {
    var type  = $j('input[name=contenttype]:checked').val();
    var elems = $j('#recordingid, #livefeedid, #channelid').not('#' + type ).parents('tr').hide();
    $j('#' + type ).parents('tr').show();
  }).change();

  $j('.cancel').click( function( e ) {
    e.preventDefault();
    var root = $j(this).parents('tr');
    root.find('input').val('');
    root.find('.foundwrap').hide();
  });
  
  var html = $j.parseJSON( '"' + $j('#usersinvitewrap').attr('data-listitemhtml') + '"' );
  var setupSearch = function( elem, callback ) {
    var target = elem.replace('_search', '');
    var elem   = $j(elem);
    $j(elem).autocomplete({
      minLength: 2,
      source   : $j(elem).attr('data-searchurl'),
      select   : function( event, ui ) {
        var label   = ui.item.label.replace('<br/>', ' - ');

        $j(target + '_title').text( label ).attr('title', label );
        $j(target).val( ui.item.value );
        $j(target).parents('tr').find('.foundwrap').show();
        return false;
      }
    }).data( "autocomplete" )._renderItem = function( ul, item ) {
      
      var itemhtml = html.replace('__IMGSRC__', item.img ).replace('__NAME__', item.label );
      return $j("<li>")
        .data( "item.autocomplete", item )
        .append( "<a>" + itemhtml + "</a>" )
        .appendTo( ul )
      ;
      
    };
  };

  setupSearch('#recordingid_search');
  setupSearch('#livefeedid_search');
  setupSearch('#channelid_search');

  var tinymceDefaults = {};
  tinyMCEInstanceInit = function(instance) { // global so that tinyMCE can actually see it
    tinymceDefaults[ instance.editorId ] = $j.parseJSON(
      '"' + $j('#templateid').attr('data-default' + instance.editorId ) + '"'
    );
  };

  $j('#templateid').change(function() {
    var val = $j(this).val();
    if (!val) {
      tinyMCE.get('templateprefix').setContent( tinymceDefaults.templateprefix );
      tinyMCE.get('templatepostfix').setContent( tinymceDefaults.templatepostfix );
      return;
    }

    var data = {templateid: val};
    $j.ajax({
      cache  : false,
      success: function( data ) {
        if ( typeof( data ) != 'object' || data.status != 'success')
          return;

        // default values
        if ( !data.prefix )
          data.prefix = $j.parseJSON(
            '"' + $j('#templateid').attr('data-defaulttemplateprefix') + '"'
          );

        if ( !data.postfix )
          data.postfix = $j.parseJSON(
            '"' + $j('#templateid').attr('data-defaulttemplatepostfix') + '"'
          );

        tinyMCE.get('templateprefix').setContent( data.prefix );
        tinyMCE.get('templatepostfix').setContent( data.postfix );

      },
      beforeSend: function() {
        $j('#usersinvitewrap .loading').show();
      },
      complete: function() {
        $j('#usersinvitewrap .loading').hide();
      },
      data    : data,
      dataType: 'json',
      type    : 'GET',
      url     : BASE_URI + language + '/users/getinvitationtemplate'
    });

  });

  setupDefaultDateTimePicker('#invitationvaliduntil');
  $j('.invitationvaliduntil .presettime').click(function(e) {
    e.preventDefault();
    $j('#invitationvaliduntil').datetimepicker('setDate', $j(this).attr('data-date') );
  });
  
}


function setupOrderRecordings() {
  
  var channelid = $j('#orderrecordings').attr('data-channelid');
  var needorderupdate = false;
  $j('#orderrecordings #orderlist').sortable({
    placeholder: 'placeholder',
    forcePlaceholderSize: true,
    containment: '#pagecontainer',
    tolerance: 'pointer',
    scroll: true,
    update: function( event, ui ) {
      needorderupdate = true;
    }
  });
  

  var OrderRecordingsMoveUp = function( e ) {
    e.preventDefault();
    
    var currelem  = $j(this).parents('li'),
        currindex = $j('#orderrecordings #orderlist li').index( currelem );
    
    if ( currindex == 0 )
      return;
    
    needorderupdate = true;
    $j('#orderrecordings #orderlist li').eq( currindex - 1 ).before( currelem );
    $j('#orderrecordings #orderlist').sortable('refresh');
    
  }

  var OrderRecordingsMoveDown = function( e ) {
    e.preventDefault();
    
    var currelem  = $j(this).parents('li'),
        currindex = $j('#orderrecordings #orderlist li').index( currelem );
    
    if ( currindex == ( $j('#orderrecordings #orderlist li').length - 1 ) )
      return;
    
    needorderupdate = true;
    $j('#orderrecordings #orderlist li').eq( currindex + 1 ).after( currelem );
    $j('#orderrecordings #orderlist').sortable('refresh');
    
  }

  $j('#orderrecordings #orderlist .orderarrows .recordingmoveup a').live('click', OrderRecordingsMoveUp );
  $j('#orderrecordings #orderlist .orderarrows .recordingmovedown a').live('click', OrderRecordingsMoveDown );
  $j('#orderrecordings .submitbutton').click( function( e ) {

    if ( !needorderupdate || !channelid )
      return;
    else
      e.preventDefault();
    
    var origurl = $j(this).attr('href');
    
    $j.ajax({
      url: BASE_URI + language + '/channels/setorder/' + channelid,
      type: 'POST',
      data: $j('#orderrecordings #orderlist').sortable('serialize'),
      dataType: 'json',
      success: function() {
        location.href = origurl;
      }
    });
    
  });
  
  $j("#orderrecordings #orderlist").disableSelection();
  
}

function setupToggleRecordings( elems ) {

  elems.click(function(e) {
    e.preventDefault();
    $j(this).parents('li').find('.smallrecordings').toggle();
  });
}

function setupComments() {

  var baseurl       = window.location.href.replace(/[\?&]commentspage=\d+/, '')
  var getcommenturl = $j('#comments .widepager').attr('data-getcommenturl');
  var commentregex  = /[\?&]focus=(\d+)/;
  var loadedpage    = null;
  var tryFocus      = function(id, dontjump) {

    if ( !dontjump )
      $j('body').scrollTop( $j('#comments').position().top + 10 );

    if ( !id ) {
      var currenthref = window.location.href;

      var match = currenthref.match(commentregex)
      if ( !match )
        return;

      // must jump, need to focus
      $j('body').scrollTop( $j('#comments').position().top + 10 );
      var id = match[1];
    }

    $j('.commentlistitem').removeClass('highlight');
    $j( '#comment-' + id ).addClass('highlight');

  };

  tryFocus(null, true);

  var loadComments = function (data) {
    var page = data.page;

    if ( loadedpage == page ) {
      tryFocus();
      return;
    }

    $j.ajax({
      url: getcommenturl,
      data: data,
      type: 'GET',
      cache: false,
      dataType: 'json',
      success: function( data ) {

        if ( typeof( data ) != 'object' || !data.html )
          return;

        $j('#commentwrap').html( data.html );

        tryFocus();
        loadedpage = page;
      },
      beforeSend: function() {
        $j('#comments .loading').show();
      },
      complete: function() {
        $j('#comments .loading').hide();
      }
    });
  };

  $j(window).bind('statechange', function() {
    var state = History.getState();
    if ( !state.data )
      return

    loadComments(state.data);
  });

  $j('#comments .widepager a, #comments a.replylink').live('click', function(e) {
    e.preventDefault();

    var data = {
      page: $j(this).attr('data-pageid')
    };

    History.pushState(data, null, $j(this).attr('href') );
  });

  if ( $j('#recordings_newcomment').length == 0 )
    return; // not logged in

  $j('#comments .reply').live('click', function(e) {
    e.preventDefault();

    var text      = $j('#text').val();
    var replyto   = $j(this).attr('data-commentid');
    var nick      = $j(this).attr('data-nick');
    var replytext = '@' + nick + ': ';

    var text = text.replace(/@[^:]+: /g, '');
    $j('#text').val( replytext + text );
    $j('#replyto').val( replyto );

  });

  $j('#recordings_newcomment').submit(function(e) {

    if ( !check_recordings_newcomment() )
      return false;

    e.preventDefault();

    var data = $j(this).serializeArray();
    $j.ajax({
      url: $j(this).attr('action'),
      type: 'POST',
      data: data,
      dataType: 'json',
      success: function( data ) {

        if ( typeof( data ) != 'object' || !data.html )
          return;

        History.pushState({
            data: {
              page: -1
            }
          }, null, baseurl
        );

        $j('#commentwrap').html( data.html );
        tryFocus( data.focus );
        $j('#text').val('');

      },
      beforeSend: function() {
        $j('#comments .loading').show();
      },
      complete: function() {
        $j('#comments .loading').hide();
      }
    });
  });

}

function setupLivestatistics( elem ) {
  setupDefaultDateTimePicker('#starttimestamp');
  setupDefaultDateTimePicker('#endtimestamp');

  var prepareData = function( data ) {

    var graphdata = [];
    var tickcount = Math.round(
      ( data.endts - data.startts ) / data.stepinterval
    );

    var nullrow = [];
    for (var i = 0, j = data.labels.length - 1; i <= j; i++)
      nullrow.push( 0 );

    var timestamptoindex = {};
    for (var i = data.data.length - 1; i >= 0; i--) {
      timestamptoindex[ data.data[i][0] ] = i;
      data.data[i][0] = new Date( data.data[i][0] );
    }

    for(var i = 0; i < tickcount; i++) {

      var ts  = ( i * data.stepinterval ) + data.startts;
      var ind = timestamptoindex[ ts ];
      if ( typeof( ind ) != 'undefined' ) { // data exists
        graphdata.push( data.data[ ind ] );
        continue;
      }

      // data does not exist, copy the nullrow, update the timestamp
      var row = nullrow.slice();
      row[0] = new Date( ts );

      graphdata.push( row );

    }

    return graphdata

  };

  // analyticsdata global
  var graphdata = prepareData( analyticsdata );

  var visibility = [];
  $j('input[name^="datapoints"]').each(function() {
    visibility.push( $j(this).is(':checked') );
  });

  delete( timestamptoindex );
  var refreshtimer = null;
  var lastclick = null;
  var isdoubleclick = null;
  var graph = new Dygraph( elem.get(0), graphdata, {
    visibility       : visibility,
    labels           : analyticsdata.labels,
    showRangeSelector: false,
    stackedGraph     : false,
    stepPlot         : true,
    fillGraph        : true,
    strokeWidth      : 2,
    colors           : [
      '#00cc00', '#0066b3', '#ff8000', '#ffcc00', '#330099', '#990099',
      '#ccff00', '#ff0000', '#808080', '#008f00', '#00487d', '#b35a00',
      '#b38f00', '#6b006b', '#8fb300', '#b30000', '#bebebe', '#80ff80',
      '#80c9ff', '#ffc080', '#ffe680', '#aa80ff', '#ee00cc', '#ff8080',
      '#666600', '#ffbfff', '#00ffcc', '#cc6699', '#999900'
    ],
    clickCallback: function() {
      var now = new Date().getTime();
      if ( !lastclick ) {
        lastclick = now;
        return;
      } else if ( now - lastclick <= 500 ) {
        // double click, reset the graph
        if ( refreshtimer ) {
          clearTimeout( refreshtimer );
          refreshtimer = null;
        }
        isdoubleclick = true;
        lastclick = null;
        refreshData(analyticsdata.origstartts, analyticsdata.origendts)();
      } else
        lastclick = null;
    },
    zoomCallback: function(min, max) {
      if ( isdoubleclick )
        return;

      if ( refreshtimer )
        clearTimeout( refreshtimer );

      setTimeout( refreshData(min, max), 500 );
    },
    axes: {
      x: {
        valueFormatter: function(ms) {
          return moment(ms).format('LLLL');
        }
      },
      y: {
        axisLabelFormatter: function(v) {
          if ( v % 1 === 0 )
            return v + '';
          else
            return '';
        }
      }
    }
  });

  var refreshData = function(min, max) {
    return function() {
      var url = $j('#live_analytics').attr('action');
      var params = $j('#live_analytics').serializeArray();
      var starttimestamp = moment(min).format('YYYY-MM-DD HH:mm');
      var endtimestamp = moment(max).format('YYYY-MM-DD HH:mm');

      for (var i = params.length - 1; i >= 0; i--) {
        switch( params[i].name ) {
          case 'action':
          case 'resolution':
            params.splice(i, 1);
            break;
          case 'starttimestamp':
            params[i].value = starttimestamp;
            break;
          case 'endtimestamp':
            params[i].value = endtimestamp;
            break;
        };
      };

      $j('#live_analytics').deserializeArray( params );

      $j.ajax({
        url     : url,
        type    : 'POST',
        data    : params,
        dataType: 'json',
        success : function(data) {
          if ( !data || typeof( data ) != 'object' || data.status != 'OK' )
            return;

          graphdata = prepareData(data.data);
          graph.updateOptions({
            file: graphdata
          });
        },
        complete: function() {
          refreshtimer = null;
          isdoubleclick = false;
        }
      })
      
    };
  };

  $j('input[name^="datapoints"]').change(function(e) {
    var index = parseInt( $j(this).val(), 10 );
    graph.setVisibility( index, $j(this).is(':checked') );
  });

  $j('#live_analytics .reset').click( function(e) {
    e.preventDefault();
    if ( refreshtimer ) {
      clearTimeout( refreshtimer );
      refreshtimer = null;
    }
    isdoubleclick = null;
    lastclick = null;
    refreshData(analyticsdata.origstartts, analyticsdata.origendts)();
  });

}

function setupRecordingDownloads() {
  $j('#recordingdownloads .submitbutton').click(function(e) {
    e.preventDefault();
    $j('#recordingdownloads ul').toggle();
  });
}
