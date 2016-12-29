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
    if (this.cfg.labels.master.length === 0)
      return;

    // copy quality array, assemble HTML
    let levels = this.cfg.labels.master.slice(0);
    levels.unshift("Auto");

    let html = `<ul class="vsq-quality-selector">`;
    for (var i = 0; i < levels.length; ++i) {
      let label = levels[i];
      let active = "";
      if (
           (i === 0 && this.selectedQuality === "auto") ||
           label === this.selectedQuality
         )
        active = ' class="active"';

      html += `<li${active} data-quality="${label.toLowerCase()}">${Escape.HTML(label)}</li>`;
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

  public setupHLS(hls: any): void {
    hls.on(Hls.Events.MANIFEST_PARSED, (event: string, data: any): void => {
      hls.startLoad(hls.config.startPosition);

      // azt varja hogy a contentnek is ugyanazok a qualityjai lesznek,
      // nem biztos hogy igaz, TODO
      let startLevel = this.getQualityIndex(this.selectedQuality);
      hls.startLevel = startLevel;
      hls.loadLevel = startLevel;
    });
  }

  private getQualityIndex(quality: string): number {
    // az alap otlet hogy a playernek a konfiguracioban atadott sorrend
    // korrelal a quality verziok sorrendjevel, igy kozvetlenul beallithato
    // ez az index a hls-nek
    for (var i = this.cfg.labels.master.length - 1; i >= 0; i--) {
      let label = this.cfg.labels.master[i];
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
