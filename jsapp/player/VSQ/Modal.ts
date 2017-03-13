/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
// TODO mar nem plugin, at kene helyezni
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import Locale from "../../Locale";
import Tools from "../../Tools";
import Escape from "../../Escape";

export class Modal {
  private pluginName = "Modal";
  private vsq: VSQ;
  private root: JQuery;
  private flowroot: JQuery;
  private cfg: VSQConfig;
  private flow: Flowplayer;
  private l: Locale;
  private showingModal = false;

  private static instance: Modal;
  public static QUESTION_TRUE_FIRST = true;
  public static QUESTION_FALSE_FIRST = false;

  private constructor(cfg: VSQConfig, root: JQuery) {
    if (Modal.instance != null)
      throw new Error("Modal.instance already present");

    this.root = root;
    this.cfg = cfg;
    this.l = cfg.locale;

    this.setupHTML();
  }

  public static init(cfg: VSQConfig, root: JQuery): void {
    Modal.instance = new Modal(cfg, root);
  }
  public static setVSQ(vsq: VSQ): void {
    Modal.instance.vsq = vsq;
    Modal.instance.flow = vsq.getPlayer();
    Modal.instance.flowroot = vsq.getFlowRoot();
  }

  private log(...params: any[]): void {
    if (!VSQ.debug)
      return;

    params.unshift(`[${this.pluginName}]`);
    console.log.apply(console, params);
  }

  private setupHTML(): void {
    let html = `
      <div class="vsq-modal">
        <div class="vsq-presence">
          <div class="row vsq-message"> value="${Escape.HTML(this.l.get('player_presencecheck'))}"</div>
          <div class="row vsq-remainingtime"></div>
          <div class="row vsq-buttons">
            <input type="button" class="vsq-button-present" value="${Escape.HTML(this.l.get('player_presencecheck_confirm'))}"/>
            <input type="button" class="vsq-button-continue" value="${Escape.HTML(this.l.get('player_presencecheck_continue'))}"/>
          </div>
        </div>
        <div class="vsq-question">
          <div class="row vsq-message"></div>
          <div class="row vsq-buttons">
            <input type="button" class="vsq-button-first"/>
            <input type="button" class="vsq-button-second"/>
          </div>
        </div>
        <div class="vsq-transient">
          <div class="row vsq-message"></div>
          <input type="button" class="vsq-button-dismiss" value="${Escape.HTML(this.l.get('player_ok'))}"/>
        </div>
        <div class="vsq-toast">
          <div class="row vsq-message"></div>
        </div>
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

  public static showError(html: string): void {
    Modal.instance.showError(html);
  }
  private showError(html: string): void {
    // ha egyszer hibat mutatunk, nem varjuk hogy bezarhato/eltuntetheto legyen
    let msg = this.root.find(".fp-message");
    msg.find("h2").text('');
    msg.find("p").html(html);

    if (this.vsq != null)
      this.vsq.pause();

    this.hideLogin();
    this.root.addClass("is-error");
    this.showingModal = true;
  }

  private static showLogin(messageHTML: string): void {
    Modal.instance.showLogin(messageHTML);
  }
  private showLogin(messageHTML: string): void {
    if (this.showingModal)
      return;

    // TODO messageHTML biztos html-kent akarjuk insertelni? security risk
    this.root.find(".vsq-modal .vsq-message").html(messageHTML);
    this.root.addClass("vsq-is-login");
    this.showingModal = true;
  }

  public static hideLogin(): void {
    Modal.instance.hideLogin();
  }
  private hideLogin(): void {
    this.showingModal = false;
    this.root.removeClass("vsq-is-login");
  }

  private async login(params: Object, resolve: any, reject: any) {
    try {
      let data = await VSQAPI.POST("users", "authenticate", params);
      switch(data.result) {
        case "OK":
          if (data.data) {
            Modal.hideLogin();
            resolve(true);
          } else
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

  public static tryLogin(messageHTML?: string): Promise<boolean> {
    return Modal.instance.tryLogin(messageHTML);
  }
  private tryLogin(messageHTML?: string): Promise<boolean> {
    if (messageHTML == null)
      messageHTML = "";

    Modal.showLogin(messageHTML);
    return new Promise((resolve, reject) => {

      this.root.one("submit", ".vsq-modal .vsq-login", (e: Event) => {
        e.preventDefault();

        let form = this.root.find(".vsq-modal .vsq-login");
        let email = form.find('input[name=email]').val();
        let password = form.find('input[name=password]').val();

        let params = jQuery.extend({
          email: email,
          password: password
        }, this.cfg.parameters);

        this.login(params, resolve, reject);
      });
    });
  }

  public static showTransientMessage(html: string): Promise<boolean> {
    return Modal.instance.showTransientMessage(html);
  }
  private showTransientMessage(msg: string): Promise<boolean> {
    // ez eltuntetheto versus a hiba jelzessel
    this.root.find(".vsq-modal .vsq-transient .vsq-message").text(msg);
    this.root.addClass("vsq-transient-error");

    return new Promise((resolve, reject) => {
      // NOTE: jQuery.one
      this.root.one("click", ".vsq-modal .vsq-transient .vsq-button-dismiss", (e: Event) => {
        e.preventDefault();

        resolve(true);
        this.root.removeClass("vsq-transient-error");
      });
    });
  }

  public static hideTransientMessage(): void {
    Modal.instance.hideLogin();
  }
  private hideTransientMessage(): void {
    this.root.removeClass("vsq-transient-error");
  }

  public static askQuestion(msg: string, yes: string, no: string, yesfirst: boolean): Promise<boolean> {
    return Modal.instance.askQuestion(msg, yes, no, yesfirst);
  }
  private askQuestion(msg: string, yes: string, no: string, yesfirst: boolean): Promise<boolean> {
    return new Promise((resolve, reject) => {
      if (this.showingModal) {
        reject("Already showing a modal");
        return;
      }

      // show question
      this.showingModal = true;
      let q = this.root.find('.vsq-modal .vsq-question');
      q.find('.vsq-message').text(msg);

      let first: string;
      let second: string;
      if (yesfirst) {
        first = yes;
        second = no;
      } else {
        first = no;
        second = yes;
      }

      q.find('.vsq-button-first').val(first);
      q.find('.vsq-button-second').val(second);

      // register click handler
      let buttons = q.find('input');

      let onClick = (e: Event) => {
        // hide
        this.root.removeClass("vsq-is-question");
        this.showingModal = false;
        // cleanup
        buttons.off("click", onClick);

        e.preventDefault();
        let elem = jQuery(e.target);

        let ret: boolean;
        if (
             (yesfirst && elem.hasClass('vsq-button-first')) ||
             (!yesfirst && elem.hasClass('vsq-button-second'))
           )
          ret = true;
        else
          ret = false;


        resolve(ret);
      };
      buttons.on("click", onClick);

      // show
      this.root.addClass("vsq-is-question");
    });
  }

  public static presenceCheck(timeoutSeconds: number): Promise<string> {
    return Modal.instance.presenceCheck(timeoutSeconds);
  }
  private presenceCheck(timeoutSeconds: number): Promise<string> {
    let elem = this.root.find('.vsq-modal .vsq-presence');
    let countdown = elem.find('.vsq-remainingtime');
    countdown.text(Tools.formatDuration(timeoutSeconds));

    return new Promise((resolve, reject) => {
      let failed = false; // csak egy sentinel hogy tudjuk jol mukodik minden
      let remaining = timeoutSeconds * 1000;
      let lastInterval = Tools.now();
      let interval: number | null = setInterval(() => {
        let now = Tools.now();
        let diff = now - lastInterval;
        lastInterval = now;
        remaining -= diff;

        if (remaining <= 0) {
          if (interval !== null) {
            clearInterval(interval);
            interval = null;
          }

          elem.addClass('vsq-checkfailed');
          failed = true;
          return;
        }

        let seconds = Math.floor(remaining / 1000);
        countdown.text(Tools.formatDuration(seconds));
      }, 500);

      let cleanup = (skipClear?: boolean) => {
        elem.removeClass('vsq-checkfailed');
        this.root.removeClass("vsq-presencecheck");
        elem.off('.vsq-pc');

        if (!skipClear && interval !== null) {
          clearInterval(interval);
          interval = null;
        }
      };

      // ismert allapotba az UI-t
      cleanup(true);

      elem.on('click.vsq-pc', 'input.vsq-button-present', (e) => {
        e.preventDefault();
        if (failed)
          throw new Error("present button clicked after timeout");

        cleanup();
        resolve('ok');
      });

      elem.on('click.vsq-pc', 'input.vsq-button-continue', (e) => {
        e.preventDefault();
        if (!failed)
          throw new Error("continue button clicked before timeout");

        cleanup();
        resolve('continue');
      });

      // show
      this.root.addClass("vsq-presencecheck");
    });
  }

  public static showToast(msg: string, timeoutSeconds: number): Promise<boolean> {
    return Modal.instance.showToast(msg, timeoutSeconds);
  }
  private showToast(msg: string, timeoutSeconds?: number): Promise<boolean> {
    if (timeoutSeconds == null)
      timeoutSeconds = 5;

    let elem = this.root.find('.vsq-modal .vsq-toast');
    elem.find('.vsq-message').text(msg);

    this.root.addClass("vsq-is-toast");
    let cancelled = false;

    return new Promise((resolve, reject) => {
      let done = () => {
        this.root.removeClass("vsq-is-toast");
        resolve(true);
      };

      elem.one("click", (e) => {
        // lehetseges hogy lejar az ido kattintas nelkul, ne jelezzunk ujra
        if (cancelled)
          return;

        done();
      });

      setTimeout(() => {
        if (cancelled)
          return;

        done();
      }, timeoutSeconds * 1000);
    });
  }
}
