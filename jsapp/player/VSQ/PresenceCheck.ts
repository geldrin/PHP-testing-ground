/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";
import RateLimiter from "../../RateLimiter";

// https://dam.codebasehq.com/projects/teleconnect/tickets/2114
export default class PresenceCheck extends BasePlugin {
  protected pluginName = "PresenceCheck";
  private interval: number; // setInterval
  private playing = false;
  private checking = false;
  private checkEvery: number;
  private timeout: number;

  private notCheckedFor: number;
  private lastCheckTime: number;
  private lastPosition: number | undefined;

  constructor(vsq: VSQ) {
    super(vsq);

    if (!this.cfg.presenceCheck.enabled)
      throw new Error("PresenceCheck disabled in config yet enabling requested");

    this.checkEvery = this.cfg.presenceCheck.checkSeconds * 1000;
    this.timeout    = this.cfg.presenceCheck.timeoutSeconds * 1000;
    this.interval   = setInterval(() => this.handleCheckTime(), 500);
  }

  private updateUncheckedTime(): void {
    let now = Tools.now();
    this.notCheckedFor += now - this.lastCheckTime;
    this.lastCheckTime = now;
  }

  private resetInactivity(): void {
    this.log("resetting");
    this.notCheckedFor = 0;
    this.lastCheckTime = Tools.now();
  }

  private handleCheckTime(): void {
    // ha epp pausolva van a felvetel vagy epp a usert kerdezzuk akkor
    // nem akarunk mukodni kozben
    if (!this.playing)
      return;

    this.updateUncheckedTime();
    if (this.notCheckedFor < this.checkEvery)
      return;

    this.log("triggering check");
    this.handlePresenceCheck();
  }

  private async handlePresenceCheck() {
    this.resetInactivity();

    this.checking = true;
    this.lastPosition = this.flow.video.time;

    // nem kell pause, menjen csak tovább a videó szépen,
    // ha nyugtázási timeout letelik, akkor bontsuk.
    let action = await Modal.presenceCheck(
      this.cfg.presenceCheck.timeoutSeconds
    );
    this.checking = false;

    switch(action) {
      case "ok":
        this.log("check ok");
        break;
      case "continue":
        this.log("check failed");
        if (this.lastPosition != null)
          this.vsq.seek(this.lastPosition);
        break;
    }
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not handling presenceCheck");
      return;
    }

    this.notCheckedFor = 0;
    let reset = () => this.resetInactivity();

    this.flow.on("resume.vsq-pc", () => {
      this.playing = true;
      reset();
    });
    this.flow.on("pause.vsq-pc error.vsq-pc finish.vsq-pc", () => {
      this.playing = false;
      reset();
    });
    this.flow.on("seek.vsq-pc volume.vsq-pc mute.vsq-pc quality.vsq-pc speed.vsq-pc fullscreen.vsq-pc fullscreen-exit.vsq-pc", reset);
  }

  public destroy(): void {
    this.flow.off(".vsq-pc");
    clearInterval(this.interval);
  }
}
