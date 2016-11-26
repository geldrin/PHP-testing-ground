/// <reference path="../jquery/jquery.d.ts" />
"use strict";
import Config from "./Config";

export default class Flash {
  private cfg: Config;
  constructor(cfg: Config) {
    this.cfg = cfg;
  }

  private getFileName(): string {
    return `flash/VSQ${this.cfg.get('')}Player.swf?v=${this.cfg.get('version')}`;
  }

  public embed() {
    let fileName = this.getFileName();
    swfobject.embedSWF()
  }
}
