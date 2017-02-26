/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import {Modal, LoginHandler} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Login extends BasePlugin implements LoginHandler {
  protected pluginName = "Login";
  private shown = false;

  constructor(vsq: VSQ) {
    super(vsq);

    Modal.installLoginHandler(this);
    // mivel csak akkor letezik ez a class ha kell a login kepernyo
    // ezert kerdes nelkul megjelenitjuk
    Modal.showLogin("");
  }

  private async login(params: Object) {
    try {
      let data = await VSQAPI.POST("users", "authenticate", params);
      switch(data.result) {
        case "OK":
          if (data.data)
            Modal.hideLogin();
          else
            Modal.showLogin(this.l.get('loginfailed'));
          break;
        default:
          let errMessage = data.data as string;

          Modal.showLogin(errMessage);
          break;
      }

    } catch(err) {
      Modal.showError(this.l.get('networkerror'));
    }
  }

  public onSubmit(email: string, password: string): void {
    let params = jQuery.extend({
      email: email,
      password: password
    }, this.cfg.parameters);

    this.login(params);
  }

  public load(): void {
  }

  public destroy(): void {
  }
}
