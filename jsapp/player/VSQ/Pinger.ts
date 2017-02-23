/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import Modal from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Pinger extends BasePlugin {
  protected pluginName = "Pinger";
  protected timer: number | null;

  constructor(vsq: VSQ) {
    super(vsq);
    this.log("scheduling request");
    this.schedule();
    this.ping();
  }

  private schedule(): void {
    if (this.timer !== null)
      clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.ping();
      this.timer = null;
      this.schedule();
    }, this.cfg.pingSeconds * 1000);
  }

  private handleError(message: string, errData: Object) {
    if ( errData['invalidtoken'] || errData['sessionexpired'] ) {
      Modal.showError(message);
      return;
    }

    if ( !errData['loggedin'] ) {
      Modal.showLogin(message);
      return;
    }
  }

  private async ping() {
    try {
      let data = await VSQAPI.POST("users", "ping", this.cfg.parameters);
      switch(data.result) {
        case "OK":
          if (data.data !== true)
            throw new Error("unexpected");

          break;
        default:
          let errMessage = data.data as string;
          let errData = data.extradata as Object;
          console.log(errMessage, errData);
          this.handleError(errMessage, errData);
          break;
      }

    } catch(err) {
      // TODO hiba, de mit csinaljunk?
    }
  }

  public load(): void {
  }

  public destroy(): void {
  }
}
