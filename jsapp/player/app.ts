/// <reference path="../jquery/jquery.d.ts" />
import Locale from "../Locale";
import Config from "./Config";
import Player from "./Player";

declare var playerconfig: Object;
declare var l: Object;

$(function() {
  let cfg = new Config(playerconfig);
  let loc = new Locale(l);
  let player = new Player(cfg, loc);
});
