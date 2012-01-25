var isExtended = false, slideEffect = false, retractEffect = false;

function retractSideBar( e ) {
  if ( !isExtended || retractEffect )
    return;
  
  retractEffect = new Effect.Move('sideBar', {
    x: -200,
    afterFinish: function() {
      isExtended = false;
      retractEffect = false;
      
      $('sideBarTab').childNodes[0].src = $('sideBarTab').childNodes[0].src.replace(/-active(\.[^.]+)$/, '$1');
    }
  });
  
}

function slideSideBar( e ){
  if ( isExtended || slideEffect )
    return;
  
  slideEffect = new Effect.Move('sideBar', {
    x: 200,
    afterFinish: function() {
      isExtended = true;
      slideEffect = false;
      
      $('sideBarTab').childNodes[0].src = $('sideBarTab').childNodes[0].src.replace(/(\.[^.]+)$/, '-active$1');
    }
  });
  
}

function init(){
  
  if ( $('sideBarTab') ) {
  
    Event.observe( 'sideBarTab', 'click', retractSideBar, true );
    Event.observe( 'sideBar', 'mouseenter', slideSideBar, true );
    Event.observe( 'sideBar', 'mouseleave',  retractSideBar, true );
    
    $$('#sideBar ul a').each( function( e ) {
      Event.observe( e, 'click', retractSideBar );
    });

  }

}

Event.observe( document, 'dom:loaded', init, true);
