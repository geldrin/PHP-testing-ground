/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import Tools from "../../Tools";
import Escape from "../../Escape";

export default class Timeline extends BasePlugin {
  protected pluginName = "Timeline";
  private watched: number;
  private offset: JQueryCoordinates;
  private size: number;
  private slider: any;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.cfg.position.seek)
      throw new Error("Timeline enabled yet disabling requested");

    // engedjuk rogton addig beletekerni ameddig megnezte
    this.watched = this.cfg.position.lastposition || 0;
    this.markProgress(this.watched);

    this.slider = this.flow.sliders.timeline;
    this.flow.on("ready", () => {
      // manualisan lekezeljuk hogy hova mehet a timeline
      this.slider.disable(true);
      this.slider.disableAnimation(true);
    })
  }

  private markProgress(watched: number): void {
    // TODO ui-ban fp-timeline ala beszurni es hasonloan az fp-bufferhez?
  }

  private getWatchedPercent(): number {
    let maxDur = this.vsq.getVideoTags()[VSQType.MASTER].duration;
    return this.watched / maxDur;
  }

  private handleEvent(e: any): void {
    let pageX = e.pageX || e.clientX;
    if (!pageX && e.originalEvent && e.originalEvent.touches && e.originalEvent.touches.length) {
      pageX = e.originalEvent.touches[0].pageX;
    }
    let delta = pageX - this.offset.left;
    delta = Math.max(0, Math.min(this.size, delta));

    let percentage = delta / this.size;
    if (percentage <= this.getWatchedPercent()) {
      this.log("sliding", percentage)
      this.slider.slide(percentage, 1, true);
    }
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not handling timeline");
      return;
    }

    // TODO Modal.askQuestion akarja e fojtatni
    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      if (this.watched < time)
        this.watched = time;
    });

    let timeline = this.flowroot.find('.fp-timeline');
    this.flowroot.on("mousedown.vsq-tl touchstart.vsq-tl", ".fp-timeline", (e) => {
      // fullscreen miatt mindig ujra szamolni
      this.offset = timeline.offset();
      this.size = timeline.width();
      this.handleEvent(e);

      jQuery(document).on("mousemove.vsq-tl touchmove.vsq-tl", (e) => {
        this.handleEvent(e);
      });

      // TODO css cursor?
    });
    jQuery(document).on("mouseup.vsq-tl touchend.vsq-tl", (e) => {
      jQuery(document).off("mousemove.vsq-tl touchmove.vsq-tl");
    });
  }

  public destroy(): void {
    this.flowroot.off(".vsq-tl");
    this.slider.disable(false);
  }
}
