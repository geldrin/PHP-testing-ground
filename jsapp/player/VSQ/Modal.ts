/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Modal extends BasePlugin {
  protected pluginName = "Modal";
  private static instance: Modal;

  constructor(vsq: VSQ) {
    super(vsq);
    Modal.instance = this;
  }

  public load(): void {
  }

  public destroy(): void {
    this.root.find(".vsq-layoutchooser").remove();
  }

  private setupHTML(): void {
    let html = `
      <div class="vsq-modal">
        <div class="vsq-error"></div>
        <form class="vsq-login">
          <input name="email" type="text"/>
          <input name="password" type="password"/>
          <input type="submit" value="${Escape.HTML(this.l.get('submit'))}/>
        </form>
      </div>
    `;
    this.root.find(".fp-ui").append(html);
  }

  public static showError(html: string): void {
    // TODO
  }

  public static showLogin(messageHTML: string): void {
    // TODO
  }
}
