/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Pinger extends BasePlugin {
  protected pluginName = "Pinger";
  protected timer: number;

  constructor(vsq: VSQ) {
    super(vsq);
  }

  private async ping() {
    // TODO extra parameters?
    let data = await VSQAPI.GET("users", "ping");
    // TODO data
    this.timer = setTimeout(() => {
      this.ping()
    }, this.cfg.pingSeconds * 1000);
  }

  public load(): void {
  }

  public destroy(): void {
  }
}
