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
    if (Modal.instance != null)
      throw new Error("Modal.instance already present");

    Modal.instance = this;
  }

  public load(): void {
  }

  public destroy(): void {
    this.root.find(".vsq-modal").remove();
  }

  private setupHTML(): void {
    let html = `
      <div class="vsq-modal">
        <form class="vsq-login">
          <input name="email" type="text"/>
          <input name="password" type="password"/>
          <input type="submit" value="${Escape.HTML(this.l.get('submitlogin'))}/>
        </form>
      </div>
    `;
    this.root.find(".fp-ui").append(html);
  }

  public static showError(html: string): void {
    let msg = this.root.find(".fp-message");
    msg.find("h2").text('');
    msg.find("p").html(html);

    this.player.pause();
    this.root.addClass("is-error");
  }

  public static showLogin(messageHTML: string): void {
    // TODO
  }
}
