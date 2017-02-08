/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

declare var Hls: any;

export default class QualityChooser extends BasePlugin {
  protected pluginName = "QualityChooser";
  // a kivalasztott quality label, default 'auto';
  private selectedQuality: string;

  constructor(vsq: VSQ) {
    super(vsq);

    this.selectedQuality = this.getDefaultQuality();
    this.root.on(this.eventName("click"), ".vsq-quality-selector li", (e: Event): void => {
      this.onClick(e);
    });
  }

  private shouldLookAtSecondary(): boolean {
    let shouldLookAtSecondary = false;
    if (!this.vsq.introOrOutro && this.vsq.hasMultipleVideos())
      shouldLookAtSecondary = true;

    return shouldLookAtSecondary;
  }

  // a megjelenitendo minosegi szintek
  private getLevels(): string[] {
    if (!this.shouldLookAtSecondary())
      return this.vsq.getVideoInfo(VSQType.MASTER)['vsq-labels'].slice(0);

    if (this.vsq.longerType === VSQType.CONTENT)
      return this.vsq.getVideoInfo(VSQType.CONTENT)['vsq-labels'].slice(0);

    // mindig master
    return this.vsq.getVideoInfo(VSQType.MASTER)['vsq-labels'].slice(0);
  }

  private onClick(e: Event): void {
    e.preventDefault();

    let choice = jQuery(e.currentTarget);
    if (choice.hasClass("active"))
      return;

    this.root.find('.vsq-quality-selector li').removeClass("active");
    choice.addClass("active");

    let quality = choice.attr('data-quality');
    Tools.setToStorage(this.configKey("quality"), quality);

    let masterLevel = this.getQualityIndex(VSQType.MASTER, quality);

    let smooth = this.flow.conf.smoothSwitching;
    let tags = this.vsq.getVideoTags();
    let paused = tags[VSQType.MASTER].paused;

    if (!paused && !smooth)
      jQuery(tags[VSQType.MASTER]).one(this.eventName("pause"), () => {
        this.root.removeClass("is-paused");
      });

    let hlsMethod = 'currentLevel';
    if (smooth && !this.flow.poster)
      hlsMethod = 'nextLevel';

    this.setLevelsForQuality(quality, hlsMethod);

    if (paused)
      this.vsq.tagCall('play');
  }

  public load(): void {
    // copy quality array, assemble HTML
    let levels = this.getLevels();
    this.log('qualities: ', levels);
    levels.unshift("Auto");

    // ha masik videohoz lett betoltve elotte
    this.root.find('.vsq-quality-selector').remove();

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
  }

  public destroy(): void {
    this.root.find(".vsq-quality-selector").remove();
  }

  public setupHLS(hls: any, type: number): void {
    hls.on(Hls.Events.MANIFEST_PARSED, (event: string, data: any): void => {
      let startLevel = this.getQualityIndex(type, this.selectedQuality);
      this.log('manifest parsed for type: ', type, ' startLevel: ', startLevel);
      hls.startLevel = startLevel;
      hls.loadLevel = startLevel;

      hls.startLoad(hls.config.startPosition);
    });

    if (type !== VSQType.MASTER)
      return;

    hls.on(Hls.Events.LEVEL_SWITCH, (event: string, data: any): void => {
      this.root.find('.vsq-quality-selector li').removeClass("current");
      let elem = this.findQualityElem(data.level);
      elem.addClass("current");
    });
  }

  private findQualityElem(level: number): JQuery {
    let ret = this.root.find('.vsq-quality-selector li[data-level="' + level + '"]');
    if (ret.length === 0)
      throw new Error("No element found with the given level: " + level);

    return ret;
  }

  private setLevelsForQuality(quality: string, prop: string): void {
    let engines = this.vsq.getHLSEngines();
    let masterLevel = this.getQualityIndex(VSQType.MASTER, quality);
    this.log('setting master video level to', masterLevel, quality);
    engines[VSQType.MASTER][prop] = masterLevel;

    if (!this.shouldLookAtSecondary())
      return;

    let secondaryLevel = this.getQualityIndex(VSQType.CONTENT, quality);
    this.log('setting content video level to', secondaryLevel, quality);
    engines[VSQType.CONTENT][prop] = secondaryLevel;
  }

  private getQualityIndex(type: number, quality: string): number {
    if (type === VSQType.MASTER)
      return this.getMasterQualityIndex(quality);

    let masterLevel = this.getMasterQualityIndex(quality);
    return this.getLevelForSecondary(masterLevel);
  }

  // csak a getQualityIndexnek kellene hasznalnia mert annak csak egy
  // olvashatosag miatt kiemelt functionje
  private getMasterQualityIndex(quality: string): number {
    let labels = this.vsq.getVideoInfo(VSQType.MASTER)['vsq-labels'];

    // az alap otlet hogy a playernek a konfiguracioban atadott sorrend
    // korrelal a quality verziok sorrendjevel, igy kozvetlenul beallithato
    // ez az index a hls-nek
    for (var i = labels.length - 1; i >= 0; i--) {
      let label = labels[i];
      if (label === quality)
        return i;
    }

    // default auto, -1 for automatic level selection
    return -1;
  }

  // csak a getQualityIndexnek kellene hasznalnia mert annak csak egy
  // olvashatosag miatt kiemelt functionje
  private getLevelForSecondary(masterLevel: number): number {
    let labels = this.vsq.getVideoInfo(VSQType.CONTENT)['vsq-labels'];
    if (labels.length <= masterLevel)
      return labels.length - 1;

    return masterLevel;
  }

  private getDefaultQuality(): string {
    return Tools.getFromStorage(this.configKey("quality"), "auto");
  }

  protected configKey(key: string): string {
    return 'vsq-player-qualitychooser-' + key;
  }
}
