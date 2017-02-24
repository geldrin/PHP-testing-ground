/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export interface LoginHandler {
  onSubmit(email: string, password: string): void;
}

export class Modal extends BasePlugin {
  protected pluginName = "Modal";
  private static instance: Modal;

  constructor(vsq: VSQ) {
    super(vsq);
    if (Modal.instance != null)
      throw new Error("Modal.instance already present");

    Modal.instance = this;
    this.setupHTML();
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
          <div class="row vsq-message">
          </div>
          <div class="row vsq-email">
            <div class="label">
              <label for="email">${Escape.HTML(this.l.get('playeremail'))}</label>
            </div>
            <div class="elem">
              <input name="email" id="email" type="text"/>
            </div>
          </div>
          <div class="row vsq-password">
            <div class="label">
              <label for="password">${Escape.HTML(this.l.get('playerpassword'))}</label>
            </div>
            <div class="elem">
              <input name="password" id="password" type="password"/>
            </div>
          </div>
          <div class="row submit">
            <div class="elem">
              <input type="submit" value="${Escape.HTML(this.l.get('submitlogin'))}"/>
            </div>
          </div>
        </form>
      </div>
    `;
    this.root.append(html);
  }

  public static installLoginHandler(plugin: LoginHandler): void {
    Modal.instance.installLoginHandler(plugin);
  }
  private installLoginHandler(plugin: LoginHandler): void {
    let form = this.root.find(".vsq-modal .vsq-login");
    form.submit(function(e) {
      e.preventDefault();

      let email = form.find('input[name=email]').val();
      let password = form.find('input[name=password]').val();

      plugin.onSubmit(email, password);
    });
  }

  public static showError(html: string): void {
    Modal.instance.showError(html);
  }
  private showError(html: string): void {
    // ha egyszer hibat mutatunk, nem varjuk hogy bezarhato/eltuntetheto legyen
    let msg = this.root.find(".fp-message");
    msg.find("h2").text('');
    msg.find("p").html(html);

    this.vsq.pause();
    this.hideLogin();
    this.root.addClass("is-error");
  }

  public static showLogin(messageHTML: string): void {
    Modal.instance.showLogin(messageHTML);
  }
  private showLogin(messageHTML: string): void {
    // TODO messageHTML biztos html-kent akarjuk insertelni? security risk
    this.root.find(".vsq-modal .vsq-message").html(messageHTML);
    this.root.addClass("vsq-is-login");
  }

  public static hideLogin(): void {
    Modal.instance.hideLogin();
  }
  private hideLogin(): void {
    this.root.removeClass("vsq-is-login");
  }
}
