/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import Config from "./Config";
import Flash from "./Flash";
import {VSQ, VSQConfig} from "./VSQ";
import {Modal} from "./VSQ/Modal";
import VSQAPI from "./VSQAPI";
import Locale from "../Locale";

export default class PlayerSetup {
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
    if (!this.cfg.get("flowplayer.vsq.debug"))
      return;

    params.unshift("[PlayerSetup]");
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

    this.initVSQ();
  }

  private initVSQ(): void {
    // fontos a sorrend, a modal init vegzi a logint
    this.initVSQAPI();
    this.initModal();

    this.initVSQPlugin();

    this.flowInstance = flowplayer(
      this.container.get(0),
      this.cfg.get('flowplayer')
    );

    this.flowInstance.on('load', (e, api, video): void => {
      this.log('ready', e, api, video)
    });
  }

  private initVSQPlugin(): void {
    VSQ.setup();
  }

  private initVSQAPI(): void {
    let cfg = this.cfg.get("flowplayer.vsq") as VSQConfig;
    VSQAPI.init(cfg);
  }

  private async initModal() {
    let cfg = this.cfg.get("flowplayer.vsq") as VSQConfig;
    Modal.init(cfg, this.container);

    if (cfg.needLogin)
      await Modal.tryLogin();
  }
}
