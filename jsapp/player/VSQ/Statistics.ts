/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQConfigPosition, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";

interface Report {
  action: string;
  fromposition: number;
  toposition: number;
}

export default class Statistics extends BasePlugin {
  protected pluginName = "Statistics";
  private reportSeconds = 60;
  // mivel a report-ot bovitjuk meg "ismeretlen" parameterekkel,
  // igy nem Report tipusu
  private reports: Object[] = [];
  private action: string;
  private fromPosition: number;
  private toPosition: number | null;
  private apiModule: string;
  private consuming = false;
  private lastPlayingReport: number | null;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.flow.live)
      this.apiModule = "live";
    else
      this.apiModule = "recordings";
  }

  private enqueueReport(report: Report): void {
    // TODO deepcopy parametereket az adott rekording verzio parametereibol +
    // az alap parameterekbol
    // jQuery.extend(true, {}, report, ...)
    /*
    "recordingid":log.recordingID,
    "recordingversionid":log.recordingVersionID,
    "viewsessionid":log.viewSessionID,
    "action":log.type,
    "useragent":userAgent,
    "streamurl":log.streamURL
    */
    this.reports.push(report);
  }

  private async consumeReports() {
    if (this.consuming)
      return;

    this.consuming = true;
    while(this.reports.length !== 0) {

      let report = this.reports.shift();
      if (report == null)
        throw new Error("managed to dequeue nothing, cannot happen");

      try {
        this.log("logging", report);
        let data = await VSQAPI.POST(this.apiModule, "logview", report);
        this.log("logging result", data);
        if (data.result !== "OK")
          throw new Error("Unexpected result from api call");

        // TODO
      } catch(err) {
        this.log("logging error", err);
        // TODO?
      }

    }
    this.consuming = false;
  }

  private reportIfNeeded(): void {
    let now = Tools.now();
    switch(this.action) {
      case "PLAY":
        this.lastPlayingReport = now;
        // TODO enqueue report
        break;

      case "PLAYING":
        // nem szabadna null-nak lennie mert PLAY-nek meg kell eloznie a
        // a PLAYING-et es a PLAY-ben beallitjuk non-nullra
        if (this.lastPlayingReport === null)
          throw new Error("lastPlayingReport was null");
        if (now - this.lastPlayingReport >= this.reportSeconds * 1000) {
          // TODO enqueue report
          this.lastPlayingReport = now;
        }
        break;
      case "STOP":
        // TODO enqueue report
        break;
    }

    this.consumeReports();
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not reporting progress");
      return;
    }

    this.flow.on("progress.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      this.action = "PLAYING";
      this.toPosition = time;
      this.reportIfNeeded();
    });

    this.flow.on("resume.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      this.action = "PLAY";
      this.fromPosition = this.flow.video.time;
      this.toPosition = null;
      this.reportIfNeeded();
    });

    this.flow.on("pause.vsq-sts stop.vsq-sts finish.vsq-sts", (e: Event, flow: Flowplayer) => {
      this.action = "STOP";
      this.toPosition = this.flow.video.time;
      this.reportIfNeeded();
    });

    this.flow.on("quality.vsq-sts", (e: Event, flow: Flowplayer) => {
      // TODO elozo quality versiont lekezelni
      this.action = "STOP";
      this.toPosition = this.flow.video.time;
      this.reportIfNeeded();

      this.action = "PLAY";
      this.fromPosition = this.flow.video.time;
      this.toPosition = null;
      this.reportIfNeeded();
    });
  }

  public destroy(): void {
    this.flow.off(".vsq-sts");
  }
}
