/// <reference path="../defs/jquery/jquery.d.ts" />
import Locale from "../Locale";
import Config from "./Config";
import Player from "./Player";

declare var playerconfig: Object;
declare var l: Object;
(function($) {
  // deep copy external dependencies to protect against modification until dom load
  let pcCopy = $.extend(true, {}, playerconfig);
  let lCopy = $.extend(true, {}, l);

  $(function() {
    let cfg = new Config(pcCopy);
    let loc = new Locale(lCopy);
    let player = new Player(cfg, loc);
    player.init();
  });
})(jQuery);
