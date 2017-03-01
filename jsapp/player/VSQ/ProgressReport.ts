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
      if (data.result == null)
        throw new Error("Unexpected result from api call");

      let result = data.result as Result;

      if (result.success === false) {
        if (result.position === 0) {
          // TODO tul sok kimaradas volt, kezdje elorol a nezest
          Modal.showError("progressreport-resetposition");
          return;
        }

        Modal.showError("progressreport-failed");
        return;
      }

      // TODO kezdeni valamit a result.watched-al?
    } catch(err) {
      this.log("report error", err);
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

    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      if (this.lastPosition < time)
        this.lastPosition = time;

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
