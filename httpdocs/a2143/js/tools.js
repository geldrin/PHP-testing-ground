function setupFancybox() {

  jQuery(document).ready(function() {

    jQuery(".fancybox").each( function() {

      jQuery(this).fancybox({
       'hideOnContentClick': true,
       'type': 'iframe',
       'height': parseInt( jQuery(this).attr('boxheight') || 500, 10 ),
       'width': parseInt( jQuery(this).attr('boxwidth') || 900, 10 ),
       'overlayOpacity': 0.8
      });

    });

  });

}

Event.observe( document,"dom:loaded", function() {
  CMSsetupDeleteMultiple();
  CMSrunClock();
  CMSsetupSessionKeepAlive();
  CMSsetupClipboard();

  $$("a").each( function(link) {
    if ( link.title.length > 0 )
      new Tooltip( link, { mouseFollow: false, backgroundColor: '#333', textColor: '#fff', opacity: .9 });
  });
} );

// ----------------------------------------------------------------------------
function CMSrunClock() {

  var today = new Date();
  var h = today.getHours();
  var m = today.getMinutes();
  var s = today.getSeconds();

  m = m < 10 ? "0" + m : m;
  s = s < 10 ? "0" + s : s;

  if ( $('CMSclock') ) {
    // may not be present when we're in a popup
    $('CMSclock').update( h + ":" + m + ":" + s );
    t = setTimeout('CMSrunClock()', 500 );

  }

}

// ----------------------------------------------------------------------------
function CMSsetupSessionKeepAlive() {

  new Ajax.Request( 'index/ping', { 'method': 'get' } );
  setTimeout('CMSsetupSessionKeepAlive()', 120 * 1000 );

}

// ----------------------------------------------------------------------------
function CMSsetupClipboard() {

  if ( $('copytoclipboard') )
    Event.observe( $('copytoclipboard'),    'click', CMScopyToClipboard );
  if ( $('pastefromclipboard') )
    Event.observe( $('pastefromclipboard'), 'click', CMSpasteFromClipboard );

}

values = '';

// ----------------------------------------------------------------------------
function CMScopyToClipboard( thisEvent ) {

  thisEvent.stop(); // avoid including "#" href in browser history

  if ( typeof( document.forms['input'] ) != 'undefined' ) {

    if ( typeof( FCKeditorAPI ) != 'undefined' ) {

      for ( name in FCKeditorAPI.__Instances ) {
        oEditor = FCKeditorAPI.GetInstance( name );
        oEditor.UpdateLinkedField();
      }

    }

    values = $( document.forms['input'] ).serialize( true );
    values.target = 'storeClipboard';

    new Ajax.Request(
      'index.php?' + Object.toQueryString( values ),
      { 'method': 'get' }
    );
  }

}

// ----------------------------------------------------------------------------
function CMSpasteFromClipboard( thisEvent ) {

  thisEvent.stop(); // avoid including "#" href in browser history

  if ( typeof( document.forms['input'] ) != 'undefined' ) {

    new Ajax.Request('index.php?target=retrieveClipboard', {

      onComplete: function( transport ) {

        if ( 200 == transport.status ) {

          inputvalues = transport.responseText.toQueryParams();

          $( document.forms.input ).getElements().each( function( e ) {

            if ( typeof( inputvalues[ e.name ] ) != 'undefined' ) {

              switch ( e.type.toLowerCase() ) {

                case 'hidden':
                  if ( typeof( FCKeditorAPI ) != 'undefined' ) {
                    FCKElement = FCKeditorAPI.GetInstance( e.name );
                    if ( typeof( FCKElement ) != 'undefined' )
                      FCKElement.SetHTML( inputvalues[ e.name ] );
                  }
                  break;

                case 'text':
                case 'textarea':
                  current = e.value;
                  e.value = inputvalues[ e.name ];
                  //if ( current != e.value )
                    //new Effect.Highlight( e.up() );
                  break;
                case 'checkbox':
                  current = e.checked;
                  e.checked = typeof( inputvalues[ e.name ] ) != 'undefined';
//                  if ( current != e.checked )
  //                  new Effect.Highlight( e.up() );
                  break;
                case 'radio':
                  if ( typeof( inputvalues[ e.name ] ) != 'undefined' )
                    if ( e.value == inputvalues[ e.name ] ) {
                      current   = e.checked;
                      e.checked = true;
    //                  if ( e.checked != current )
      //                  new Effect.Highlight( e.up() );
                    }
                  break;
                case 'select-one':
                  if ( typeof( inputvalues[ e.name ] ) != 'undefined' ) {
                    current = e.selectedIndex;
                    for ( i = 0; i < e.length; i++ ) {
                      if ( e[i].value == inputvalues[ e.name ] ) {
                        e.selectedIndex = i;
//                        if ( current != i )
  //                        new Effect.Highlight( e.up() );
                      }
                    }
                  }
                  break;

              }
            }

          });

        }

      }

    });

  }

}

// ----------------------------------------------------------------------------
function CMSsetupDeleteMultiple( thisEvent ) {

  if ( !$('listcontainer') || !$('CMSdeletemultiple') )
    return;

  $$('#listcontainer .deletecheckbox').each(function(e){
    e.hide();
  });

  Event.observe( 'CMSdeletemultiple',       'click', CMSshowMultiDeleteControls );
  Event.observe( 'CMSdeletemultiplecancel', 'click', CMShideMultiDeleteControls );
  Event.observe( 'CMSdeletemultipleAll',    'click', function(){
    $$('#listcontainer .deletecheckbox').each(function(e){
      e.checked = true;
    });
    CMSdeletemultipleAll.checked = true;
  });
  Event.observe( 'CMSdeletemultipleNone', 'click', function(){
    $$('#listcontainer .deletecheckbox').each(function(e){
      e.checked = false;
    });
    CMSdeletemultipleNone.checked = false;
  });

}

// ----------------------------------------------------------------------------
function CMSshowMultiDeleteControls( thisEvent ) {

  thisEvent.stop();

  $$('#listcontainer .deletecheckbox').each(function(e){
    e.show();
  });
  $('CMSdeletemultiple').hide();
  $('CMSdeletemultiplecontrols').show();

}

// ----------------------------------------------------------------------------
function CMShideMultiDeleteControls() {

  $$('#listcontainer .deletecheckbox').each(function(e){
    e.hide();
  });
  $('CMSdeletemultiple').show();
  $('CMSdeletemultiplecontrols').hide();

}

function setupAjaxFileManager( options ) {

  if ( !tinyMCE )
    return;

  options.file_browser_callback = onFileBrowse;
  tinyMCE.init( options );

}

function onFileBrowse( id, url, type, window ) {

  tinyMCE.activeEditor.windowManager.open({
    url: "../../../../js/tiny_mce/plugins/ajaxfilemanager/ajaxfilemanager.php",
    width: 782,
    height: 440,
    inline : "yes",
    close_previous : "no"
  },{
    window : window,
    input : id
  });

}

jQuery(document).ready(function() {

  // a disabled mezoket is submitoljuk
  jQuery('#input').submit( function() {
    jQuery('#input input, #input textarea, #input select').removeAttr('disabled');
  });

  runIfExists('#organizations_modify, #organizations_new', setupOrganization );
  runIfExists('#toggleprivileges', setupPrivileges );
});

function applyVisibilityToForm( data ) {

  for ( var key in data ) {

    if ( !data.hasOwnProperty( key ) )
      continue;

    $j( key ).parents('tr').toggle( data[ key ] );

  }

}

function runIfExists( selector, func ) {

  var elem = $j( selector );
  if ( elem.length > 0 )
    func( elem );

}

function setupOrganization() {

  var nicknamehidden = {
    '0': {
      'input[name=isorganizationaffiliationrequired]': false,
    },
    '1': {
      'input[name=isorganizationaffiliationrequired]': true,
    },
  }

  $j('input[name=isnicknamehidden]').change(function() {
    var value = $j('input[name=isnicknamehidden]:checked').val();
    applyVisibilityToForm( nicknamehidden[ value ] );
  }).change();

}

function setupPrivileges() {
  $j('#toggleprivileges').click(function(e) {
    e.preventDefault();

    var elems = $j('input[name^="privileges"');
    if ( elems.is(':checked') )
      elems.attr('checked', false );
    else
      elems.attr('checked', 'checked');
  })
}
