/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import Config from "./Config";
import Flash from "./Flash";
import Flow from "./Flow";
import Locale from "../Locale";

/** A Player class az UI-ert felel */
export default class Player {
  private cfg: Config;
  private l: Locale;
  private container: JQuery;
  private flowInstance: Flowplayer;

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
    if (this.container.length == 0) {
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

    this.initFlowPlugin();
    this.initFlow();
  }


  private initFlowPlugin() {
    let flow = new Flow();
    flow.init();
  }

  private initFlow(): void {
    this.flowInstance = flowplayer(
      this.container.get(0),
      this.cfg.get('flowplayer')
    );

    this.flowInstance.on('load', (e, api, video): void => {
      this.log('ready', e, api, video)
    });
  }
}
