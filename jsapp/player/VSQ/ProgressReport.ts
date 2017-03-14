/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQConfigPosition, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";

interface Result {
  success: boolean;
  position?: number;
  watched?: boolean;
  watchedpercent?: number;
  needpercent?: number;
}

export default class ProgressReport extends BasePlugin {
  protected pluginName = "ProgressReport";
  private interval: number;
  private lastReportTime: number | null;
  private lastPosition: number = 0;
  private watched = false;
  private playing = false;

  constructor(vsq: VSQ) {
    super(vsq);

    if (!this.cfg.position.report)
      throw new Error("Reporting disabled yet reporting requested");

    this.interval = this.cfg.position.intervalSeconds * 1000;
  }

  private async report() {
    this.lastReportTime = Tools.now();

    try {
      let params = jQuery.extend({}, this.cfg.parameters);
      params['lastposition'] = this.lastPosition;
      this.log("reporting", params);
      let data = await VSQAPI.POST("recordings", "updateposition", params);
      this.log("report result", data);
      if (data.result !== "OK" || data.data == null)
        throw new Error("Unexpected result from api call");

      let result = data.data as Result;

      if (result.success === false) {
        this.log("Progress report too old, resetting");
        this.vsq.pause();
        await Modal.showTransientMessage(this.l.get("player_progress_reset"));
        this.vsq.resume();
        this.vsq.seek(0);
        return;
      }

      if (!this.watched && result.watched) {
        this.watched = true;
        Modal.showToast(this.l.get("player_progress_watched"));
      }
    } catch(err) {
      this.log("error", err);
      Modal.showError(this.l.get('networkerror'));
    }
  }

  private reportIfNeeded(force?: boolean): void {
    if (force || this.lastReportTime == null)
      this.report();

    let now = Tools.now();
    if (now - this.lastReportTime > this.interval)
      this.report();
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not reporting progress");
      return;
    }

    this.flow.on("resume.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      this.playing = true;
    });
    // mivel jon egy progress event meg azelott hogy elkezdene a video jatszani
    // igy csak akkor jelentunk ha mar elindult a video
    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      if (this.lastPosition < time)
        this.lastPosition = time;

      if (this.playing)
        this.reportIfNeeded();
    });
    this.flow.on("finish.vsq-pgr", (e: Event, flow: Flowplayer) => {
      this.reportIfNeeded(true);
    });
  }

  public destroy(): void {
    this.flow.off(".vsq-pgr");
  }
}
