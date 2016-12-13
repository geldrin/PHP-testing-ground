/// <reference path="../defs/jquery/jquery.d.ts" />
"use strict";
import Config from "./Config";
import Locale from "../Locale";

declare var handleFlashLoad: any;
declare var swfobject: any;

export default class Flash {
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

  private getFileName(): string {
    let subtype = this.cfg.get('flashplayer.subtype');
    let ver = this.cfg.get('version');
    return `flash/VSQ${subtype}Player.swf?v=${ver}`;
  }

  private getParamRef(container: any, keys: string[]): Object {
    let key = keys.shift();
    if (key == null)
      throw new Error("Invalid key");

    let ret = container[key];
    if (ret && keys.length > 0)
      return this.getParamRef(ret, keys);

    return ret;
  }

  public embed(): void {
    let fileName = this.getFileName();
    let paramStr = String(this.cfg.get('flashplayer.params', 'flashdefaults.params'));
    let param = this.getParamRef(window, paramStr.split('.'));
    let config = JSON.parse(String(this.cfg.getFlashConfig()));

    swfobject.embedSWF(
      fileName,
      this.cfg.get('containerid'),
      this.cfg.get('width'),
      this.cfg.get('height'),
      '11.1.0',
      'flash/swfobject/expressInstall.swf',
      config,
      param,
      null,
      handleFlashLoad
    );
  }
}
