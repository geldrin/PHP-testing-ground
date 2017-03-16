/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQConfigPosition, VSQType} from "../VSQ";
import VSQAPI from "../VSQAPI";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";

class Report {
  public action: string;
  public positionfrom: number | null = null;
  public positionuntil: number | null = null;

  constructor(action: string) {
    this.action = action;
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

  // ha az enqueueReport egy quality valtas miatt hivodik meg,
  // a this.vsq.getHLSEngines()[VSQType.MASTER].currentLevel
  // az elozo quality verziot fogja mutatni, ezert inkabb az indirekcio
  // igy megoldjuk hogy kontrollalhato pontosan melyik qualityt akarjuk
  // jelenteni eppen
  private currentLevel: number;
  private prevLevel: number;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.flow.live)
      this.apiModule = "live";
    else
      this.apiModule = "recordings";
  }

  private enqueueReport(report: Report): void {
    // nem tortenhetne meg hogy nincs quality level
    // mivel a ready-t csak akkor jelezzuk a flow playernek ha a hls.js
    // MANIFEST_PARSED allapotba kerult es onnantol mar tudjuk a qualityt
    // csak utana johetne elviekben PLAY event
    if (this.currentLevel == null)
      throw new Error("Quality level not yet set (cant know params), lost report: " + JSON.stringify(report));

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
        this.log("logging report", report);
        let data = await VSQAPI.POST(this.apiModule, "logview", report, true);
        this.log("logging result", data);

        // a backend nem kuld vissza mast mint OK-t
        if (data.result !== "OK")
          throw new Error("Unexpected result from api call");

      } catch(err) {
        this.log("logging error, retrying", err);
        // ujra a sor elejere rakjuk a reportot mert muszaj ujraprobalnunk
        // itt csak akkor hivodunk meg ha a request abszolut nem sikerult
        // network error vagy non-2xx status
        this.reports.unshift(report);
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
        report.positionfrom = this.fromPosition;
        report.positionuntil = this.toPosition;
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
        report.positionfrom = this.fromPosition;
        report.positionuntil = this.toPosition;
        break;
      case "STOP":
        report.positionuntil = this.toPosition;
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

    this.flow.on("ready.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      this.currentLevel = this.vsq.getHLSEngines()[VSQType.MASTER].currentLevel;
      this.prevLevel = this.currentLevel;
    });

    this.flow.on("progress.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      this.onProgress(time);
    });

    this.flow.on("resume.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      this.onPlay(time);
    });

    this.flow.on("pause.vsq-sts stop.vsq-sts finish.vsq-sts", (e: Event, flow: Flowplayer) => {
      this.onPause();
    });

    this.flow.on("quality.vsq-sts", (e: Event, flow: Flowplayer, level: number) => {
      this.onQualityChange(level);
    });

    this.flow.on("seek.vsq-sts", (e: Event, flow: Flowplayer, time: number) => {
      this.onSeek(time);
    });
  }

  public destroy(): void {
    this.flow.off(".vsq-sts");
  }

  private onPlay(time: number): void {
    if (this.flow.video.time == null)
      throw new Error("flow.video.time was null");

    if (this.prevAction !== "STOP" && this.prevAction !== "") {
      this.log("previous action was not STOP/nothing, ignoring (level switch)");
      return;
    }

    this.action = "PLAY";
    this.fromPosition = this.flow.video.time;
    this.toPosition = this.fromPosition;
    this.reportIfNeeded();
  }

  private onPause(): void {
    if (this.flow.video.time == null)
      throw new Error("STOP - flow.video.time was null");

    if (this.switchingLevels()) {
      this.log("STOP - switching levels, ignoring");
      return;
    }

    if (this.prevAction !== "PLAY" && this.prevAction !== "PLAYING") {
      this.log("STOP - previous action was not PLAY/PLAYING, ignoring");
      return;
    }

    this.action = "STOP";
    this.toPosition = this.flow.video.time;
    this.reportIfNeeded();
  }

  private onProgress(time: number): void {
    // hogy meddig haladtunk azt mindig elrakjuk
    this.toPosition = time;

    // mivel a hls.js auto-level funkcioja nem dob eventeket,
    // igy vagyunk kenytelenek detektalni, az egesz elejen
    let currentLevel = this.vsq.getHLSEngines()[VSQType.MASTER].currentLevel;
    if (this.prevAction !== "" && currentLevel != -1 && currentLevel != this.prevLevel)
      this.onQualityChange(currentLevel);

    // ha -1 akkor auto-level, es automatan az elso qualityt valasztjuk eloszor
    this.prevLevel = currentLevel < 0? 0: currentLevel;
    if (this.prevAction === "") {
      this.log("progress update before playing, ignoring");
      return;
    }

    if (this.flow.seeking) {
      this.log("PROGRESS - currently seeking, ignoring");
      return;
    }

    if (this.prevAction !== "PLAY" && this.prevAction !== "PLAYING") {
      this.log("PROGRESS - previous action was not PLAY/PLAYING, ignoring");
      return;
    }

    this.action = "PLAYING";
    this.reportIfNeeded();
  }

  private onSeek(time: number): void {
    if (this.prevAction === "") {
      this.log("seek before playing, ignoring");
      return;
    }

    if (this.switchingLevels()) {
      this.log("SEEK - switching levels, ignoring");
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
  }

  private onQualityChange(level: number): void {
    if (this.flow.video.time == null)
      throw new Error("QUALITY - flow.video.time was null");

    if (this.prevAction === "") {
      this.log("quality switch before playing, ignoring", level);

      // valamire muszaj allitani, mert ha elindul a lejatszas akkor
      // a megfelelo parameterekkel szeretnenk jelenteni
      this.currentLevel = level;
      return;
    }

    if (level == this.prevLevel) {
      this.log("quality switch to the same level, ignoring", level);
      return;
    }

    let from = parseInt("" + this.fromPosition, 10);
    let to = parseInt("" + this.flow.video.time, 10);
    if (from === to) {
      this.log("quality switch in the same second, ignoring");
      return;
    }

    this.log("Reporting quality switch (STOP+START)", level);
    // a regi quality szintet allitjuk be, mert arrol valtunk le
    this.currentLevel = this.prevLevel;
    this.action = "STOP";
    this.toPosition = this.flow.video.time;
    this.reportIfNeeded();

    // az uj quality szintet allitjuk be
    this.currentLevel = level;
    this.action = "PLAY";
    this.fromPosition = this.flow.video.time;
    this.toPosition = this.fromPosition;
    this.reportIfNeeded();
  }
}
