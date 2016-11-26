/// <reference path="../jquery/jquery.d.ts" />
"use strict";
import Config from "./Config";
import Flash from "./Flash";
import Locale from "../Locale";

export default class Player {
  private cfg: Config;
  private l: Locale;

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
    let elem = jQuery('#' + this.cfg.get('containerid'));
    if (elem.length == 0) {
      this.log("container not found");
      return;
    }

    if (!this.supportsVideo()) {
      this.log("falling back to flash");
      this.initFlash();
      return;
    }
  }
}
