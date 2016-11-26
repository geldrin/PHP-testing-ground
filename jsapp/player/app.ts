/// <reference path="../jquery/jquery.d.ts" />
import Locale from "../Locale";
import Config from "./Config";
import Player from "./Player";

declare var playerconfig: Object;
declare var l: Object;
(function($) {
  // deep copy external dependencies to protect against modification until dom load
  let playerconfig = $.extend(true, {}, playerconfig);
  let l = $.extend(true, {}, l);

  $(function() {
    let cfg = new Config(playerconfig);
    let loc = new Locale(l);
    let player = new Player(cfg, loc);
    player.init();
  });
})(jQuery);
