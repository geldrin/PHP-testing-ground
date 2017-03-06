/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQConfigPosition, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";

interface IReport {
  action: string;
  fromposition: number | null;
  toposition: number | null;
}
class Report implements IReport {
  public action: string;
  public fromposition: number | null;
  public toposition: number | null;

  constructor(action: string, fromPosition: number | null = null, toPosition: number | null = null) {
    this.action = action;
    this.fromposition = fromPosition;
    this.toposition = toPosition;
  }
}

export default class Statistics extends BasePlugin {
  protected pluginName = "Statistics";
  private reportSeconds = 60;
  // mivel a report-ot bovitjuk meg "ismeretlen" parameterekkel,
  // igy nem Report tipusu
  private reports: Object[] = [];
  private action: string = "";
  private prevAction: string = "";
  private fromPosition: number;
  private toPosition: number | null;
  private apiModule: string;
  private consuming = false;
  private lastPlayingReport: number | null;
  private prevLevel: number;

  // ha az enqueueReport egy quality valtas miatt hivodik meg,
  // a this.vsq.getHLSEngines()[VSQType.MASTER].currentLevel
  // az elozo quality verziot fogja mutatni, ezert inkabb az indirekcio
  // igy megoldjuk hogy kontrollalhato pontosan melyik qualityt akarjuk
  // jelenteni eppen
  private currentLevel: number;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.flow.live)
      this.apiModule = "live";
    else
      this.apiModule = "recordings";
  }

  private enqueueReport(report: Report): void {
    if (this.currentLevel == null)
      throw new Error("Quality level not yet set, cannot ascertain parameters");

    let info = this.vsq.getVideoInfo(VSQType.MASTER);
    let quality = this.currentLevel;
    if (quality < 0)
      quality = 0;

    if (!info["vsq-parameters"] || info["vsq-parameters"].length < quality)
      throw new Error("no parameters found for quality " + quality);

    let params = info["vsq-parameters"][quality];
    let rep = jQuery.extend(
      true, // deepcopy
      {
        streamurl: info.src,
        useragent: navigator.userAgent
      },
      report, this.cfg.parameters, params
    );

    this.log("queuing report", rep);
    this.reports.push(rep);
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
    let report = new Report(this.action);

    switch(this.action) {
      case "PLAY":
        this.lastPlayingReport = now;
        report.fromposition = this.fromPosition;
        break;

      case "PLAYING":
        // nem szabadna null-nak lennie mert PLAY-nek meg kell eloznie a
        // a PLAYING-et es a PLAY-ben beallitjuk non-nullra
        if (this.lastPlayingReport === null)
          throw new Error("lastPlayingReport was null");

        // meg nem jelentunk mert nem telt el eleg ido
        if (now - this.lastPlayingReport < this.reportSeconds * 1000)
          return;

        this.lastPlayingReport = now;
        report.fromposition = this.fromPosition;
        report.toposition = this.toPosition;
        break;
      case "STOP":
        report.toposition = this.toPosition;
        break;
    }

    this.enqueueReport(report);
    this.prevAction = this.action;
    this.consumeReports();
  }

  private switchingLevels(): boolean {
    // TODO nem eleg jo, nem ved automata level valtas ellen
    return this.vsq.getHLSEngines()[VSQType.MASTER].switchingLevels;
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not reporting progress");
      return;
    }

    this.flow.on("progress.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      if (this.prevAction === "") {
        this.log("progress update before playing, ignoring");
        return;
      }

      this.action = "PLAYING";
      this.toPosition = time;
      this.reportIfNeeded();
    });

    this.flow.on("resume.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      if (this.flow.video.time == null)
        throw new Error("flow.video.time was null");

      if (this.switchingLevels()) {
        this.log("switching levels, ignoring PLAY");
        return;
      }

      this.log("Reporting PLAY");
      this.action = "PLAY";
      this.fromPosition = this.flow.video.time;
      this.toPosition = null;
      this.reportIfNeeded();
    });

    this.flow.on("pause.vsq-sts stop.vsq-sts finish.vsq-sts", (e: Event, flow: Flowplayer) => {
      if (this.flow.video.time == null)
        throw new Error("flow.video.time was null");

      if (this.switchingLevels()) {
        this.log("switching levels, ignoring STOP");
        return;
      }

      this.log("Reporting STOP");
      this.action = "STOP";
      this.toPosition = this.flow.video.time;
      this.reportIfNeeded();
    });

    this.flow.on("quality.vsq-sts", (e: Event, flow: Flowplayer, level: number) => {
      if (this.flow.video.time == null)
        throw new Error("flow.video.time was null");

      if (this.prevAction === "") {
        this.log("quality switch before playing, ignoring", level);

        // valamire muszaj allitani, mert ha elindul a lejatszas akkor
        // a megfelelo parameterekkel szeretnenk jelenteni
        this.currentLevel = level;
        return;
      }

      this.log("Reporting quality switch (STOP+START)", level);

      // a regi quality szintet allitjuk be, mert arrol valtunk le
      // TODO
      this.currentLevel = this.vsq.getHLSEngines()[VSQType.MASTER].prevLevel;
      this.action = "STOP";
      this.toPosition = this.flow.video.time;
      this.reportIfNeeded();

      // az uj quality szintet allitjuk be
      this.currentLevel = level;
      this.action = "PLAY";
      this.fromPosition = this.flow.video.time;
      this.toPosition = null;
      this.reportIfNeeded();
    });

    this.flow.on("seek.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      if (this.prevAction === "") {
        this.log("seek before playing, ignoring");
        return;
      }

      if (this.switchingLevels()) {
        this.log("switching levels, ignoring SEEK");
        return;
      }

      this.log("reporting seek, stop to: ", this.toPosition, "play from", time);
      this.action = "STOP";
      // a this.toPosition az marad amit jelentett a progress elozoleg
      if (this.toPosition == null)
        throw new Error("toPosition was null-like");
      this.reportIfNeeded();

      this.action = "PLAY";
      this.fromPosition = time;
      this.reportIfNeeded();
    });
  }

  public destroy(): void {
    this.flow.off(".vsq-sts");
  }
}
