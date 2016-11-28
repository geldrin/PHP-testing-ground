/// <reference path="../jquery/jquery.d.ts" />
"use strict";
import Config from "./Config";
import Flash from "./Flash";
import Locale from "../Locale";

declare var flowplayer: Object;

export default class Player {
  private cfg: Config;
  private l: Locale;
  private container: jQuery;
  private flowInstance: Object;

  constructor(cfg: Config, l: Locale) {
    if (!cfg)
      throw "Invalid config passed";
    if (!l)
      throw "Invalid locale passed";

    this.cfg = cfg;
    this.l = l;
  }

  public log(...params: Object[]): void {
    params.unshift("[Player]");
    console.log.apply(console, params);
  }

  private supportsVideo(): boolean {
    let elem = document.createElement('video');
    return !!elem.canPlayType;
  }

  private initFlash(): void {
    let flash = new Flash(this.cfg, this.l);
    flash.embed();
  }

  public init(): void {
    this.container = jQuery('#' + this.cfg.get('containerid'));
    if (this.container == 0) {
      this.log("container not found");
      return;
    }

    if (!this.cfg.get('flowplayer')) {
      this.initFlash();
      return;
    }

    if (!this.supportsVideo()) {
      this.log("falling back to flash");
      this.initFlash();
      return;
    }

    this.initFlow();
  }

  private initFlow(): void {
    this.flowInstance = flowplayer(this.container, {
      'splash': true,
      'ratio': 9/16,
      'clip': {
        'title': "This is my title",
        'hlsjs': {
          smoothSwitching: false,
          strict: true,
          recoverMediaError: true,
          recoverNetworkError: true
        },
        sources: [
          {
            type: "application/x-mpegurl",
            src:  "https://stream.videosquare.eu/devvsq/_definst_/smil:253/253/253.smil/playlist.m3u8"
          }
        ]
      },
      embed: false
    }).on("ready", (e, api, video) => {
      this.log(e, api, video)
    });
    this.log(this.flowInstance);
  }
}
