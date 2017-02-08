/// <reference path="../defs/jquery/jquery.d.ts" />
import Locale from "../Locale";
import Config from "./Config";
import PlayerSetup from "./PlayerSetup";

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
    let loc = new Locale(lCopy);
    if (pcCopy.flowplayer)
      pcCopy.flowplayer.vsq.locale = loc;

    let cfg = new Config(pcCopy, fcCopy);

    let player = new PlayerSetup(cfg, loc);
    player.init();
  });
})(jQuery);
