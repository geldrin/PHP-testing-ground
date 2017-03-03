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
  public static QUESTION_TRUE_FIRST = true;
  public static QUESTION_FALSE_FIRST = false;

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
    // nem akarjuk az insertalt html-t feltakaritani mert a mi html-unk
    // nem kotott a video eletehez
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

  public static installLoginHandler(plugin: LoginHandler): void {
    Modal.instance.installLoginHandler(plugin);
  }
  private installLoginHandler(plugin: LoginHandler): void {
    this.root.on("submit", ".vsq-modal .vsq-login", (e) => {
      e.preventDefault();

      let form = this.root.find(".vsq-modal .vsq-login");
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

  public static showTransientMessage(html: string): void {
    Modal.instance.showTransientMessage(html);
  }
  private showTransientMessage(msg: string): void {
    // ha egyszer hibat mutatunk, nem varjuk hogy bezarhato/eltuntetheto legyen
    this.root.find(".vsq-modal .vsq-transient").text(msg);
    this.root.addClass("vsq-transient-error");
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
      // show question
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
}
