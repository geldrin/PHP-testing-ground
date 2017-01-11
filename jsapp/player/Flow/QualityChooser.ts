/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {Flow, VSQConfig} from "../Flow";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

declare var Hls: any;

export default class QualityChooser extends BasePlugin {
  // a kivalasztott quality label, default 'auto';
  private selectedQuality: string;

  constructor(flow: Flow) {
    super(flow);

    this.selectedQuality = this.getDefaultQuality();
  }

  public init(): void {
    if (this.cfg.labels.length === 0)
      return;

    // copy quality array, assemble HTML
    let levels = this.cfg.labels.slice(0);
    levels.unshift("Auto");

    let html = `<ul class="vsq-quality-selector">`;
    for (let i = 0; i < levels.length; ++i) {
      let label = levels[i];
      let active = "";
      if (
           (i === 0 && this.selectedQuality === "auto") ||
           label === this.selectedQuality
         )
        active = ' class="active"';

      html += `<li${active} data-level="${i - 1}" data-quality="${label.toLowerCase()}">${Escape.HTML(label)}</li>`;
    }
    html += `</ul>`;
    this.root.find(".fp-ui").append(html);

    this.root.on(this.eventName("click"), ".vsq-quality-selector li", (e: Event): void => {
      e.preventDefault();

      let choice = jQuery(e.currentTarget);
      if (choice.hasClass("active"))
        return;

      this.root.find('.vsq-quality-selector li').removeClass("active");
      choice.addClass("active");

      let quality = choice.attr('data-quality');
      Tools.setToStorage(this.configKey("quality"), quality);

      let level  = this.getQualityIndex(quality);
      let smooth = this.player.conf.smoothSwitching;
      let paused = this.videoTags[Flow.MASTER].paused;

      if (!paused && !smooth)
        jQuery(this.videoTags[Flow.MASTER]).one(this.eventName("pause"), () => {
          this.root.removeClass("is-paused");
        });

      if (smooth && !this.player.poster)
        this.flow.hlsSet('nextLevel', level);
      else
        this.flow.hlsSet('currentLevel', level);

      if (paused)
        this.flow.tagCall('play');
    });
  }

  public destroy(): void {
    this.root.find(".vsq-quality-selector").remove();
  }

  public setupHLS(hls: any, type: number): void {
    hls.on(Hls.Events.MANIFEST_PARSED, (event: string, data: any): void => {
      hls.startLoad(hls.config.startPosition);

      // azt varja hogy a contentnek is ugyanazok a qualityjai lesznek,
      // nem biztos hogy igaz, TODO
      let startLevel = this.getQualityIndex(this.selectedQuality);
      hls.startLevel = startLevel;
      hls.loadLevel = startLevel;
    });

    if (type !== Flow.MASTER)
      return;

    hls.on(Hls.Events.LEVEL_SWITCH, (event: string, data: any): void => {
      this.root.find('.vsq-quality-selector li').removeClass("current");
      let elem = this.findQualityElem(data.level);
      elem.addClass("current");
    });
  }

  private findQualityElem(level: number): JQuery {
    if (this.cfg.labels[level] == null)
      throw new Error("The video just switched to an unexpected quality level: " + level);

    let ret = this.root.find('.vsq-quality-selector li[data-level="' + level + '"]');
    if (ret.length === 0)
      throw new Error("No element found with the given level: " + level);

    return ret;
  }

  private getQualityIndex(quality: string): number {
    // az alap otlet hogy a playernek a konfiguracioban atadott sorrend
    // korrelal a quality verziok sorrendjevel, igy kozvetlenul beallithato
    // ez az index a hls-nek
    for (var i = this.cfg.labels.length - 1; i >= 0; i--) {
      let label = this.cfg.labels[i];
      if (label === quality)
        return i;
    }

    // default auto, -1 for automatic level selection
    return -1;
  }

  private getDefaultQuality(): string {
    return Tools.getFromStorage(this.configKey("quality"), "auto");
  }

  protected configKey(key: string): string {
    return 'vsq-player-qualitychooser-' + key;
  }
}
