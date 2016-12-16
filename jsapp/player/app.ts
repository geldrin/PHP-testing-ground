/// <reference path="../defs/jquery/jquery.d.ts" />
import Locale from "../Locale";
import Config from "./Config";
import Player from "./Player";

interface FlashConfig {
  parameters: string;
  hash: string;
}

declare var playerconfig: Object;
declare var flashconfig: FlashConfig;
declare var l: Object;
(function($) {
  // deep copy external dependencies to protect against modification until dom load
  let pcCopy = $.extend(true, {}, playerconfig);
  let fcCopy = $.extend(true, {}, flashconfig);
  let lCopy = $.extend(true, {}, l);

  $(function() {
    let cfg = new Config(pcCopy, fcCopy);
    let loc = new Locale(lCopy);
    let player = new Player(cfg, loc);
    player.init();
  });
})(jQuery);
