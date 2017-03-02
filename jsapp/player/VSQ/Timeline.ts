/// <reference path="../../defs/jquery/jquery.d.ts" />
/// <reference path="../../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "../VSQ";
import {BasePlugin} from "./BasePlugin";
import {Modal} from "./Modal";
import Tools from "../../Tools";
import Escape from "../../Escape";
import RateLimiter from "../../RateLimiter";

export default class Timeline extends BasePlugin {
  protected pluginName = "Timeline";
  private watched: number;
  private offset: JQueryCoordinates;
  private size: number;
  private slider: FlowTimeline;
  private progress: JQuery;
  private limiter: RateLimiter;

  constructor(vsq: VSQ) {
    super(vsq);

    if (this.cfg.position.seek)
      throw new Error("Timeline enabled yet disabling requested");

    this.flowroot.addClass("vsq-limitedseek");
    let html = `
      <div class="vsq-progress"></div>
    `;
    this.progress = jQuery(html);
    this.flowroot.find('.fp-timeline .fp-buffer').after(this.progress);

    // engedjuk rogton addig beletekerni ameddig megnezte
    this.watched = this.cfg.position.lastposition || 0;
    this.markProgress();

    this.slider = this.flow.sliders.timeline;
    this.flow.on("ready", () => {
      // manualisan lekezeljuk hogy hova mehet a timeline
      this.slider.disable(true);
      this.slider.disableAnimation(true);
    });

    this.limiter = new RateLimiter();
    this.limiter.add("handle", (e: Event) => {
      this.handleEvent(e);
    }, 100, true);
  }

  private markProgress(): void {
    let percentage = this.getWatchedPercent() * 100;
    this.progress.css('width', percentage + '%');
  }

  private getWatchedPercent(): number {
    let maxDur = this.flow.video.duration;
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
      this.log("seeking to: ", percentage);
      this.slider.slide(percentage, 1, true);
    } else {
      this.log("out of bounds: ", percentage);
    }
  }

  private async handleResume() {
    if (this.watched < 10)
      return;

    let from = Tools.formatDuration(this.watched);
    let question = this.l.get("player_shouldresume");
    question = question.replace(/%from%/gi, from);

    let shouldResume = await Modal.askQuestion(
      question,
      this.l.get('yes'),
      this.l.get('no'),
      Modal.QUESTION_TRUE_FIRST
    );

    if (shouldResume)
      this.vsq.triggerFlow("seek", this.watched);
  }

  public load(): void {
    if (!this.vsq.isMainMasterVideo()) {
      this.log("Intro our outro playing, not handling timeline");
      return;
    }

    this.flow.on("progress.vsq-pgr", (e: Event, flow: Flowplayer, time: number) => {
      if (this.watched > time)
        return;

      this.watched = time;
      this.markProgress();
    });

    let timeline = this.flowroot.find('.fp-timeline');
    this.flowroot.on("mousedown.vsq-tl touchstart.vsq-tl", ".fp-timeline", (e) => {
      // fullscreen miatt mindig ujra szamolni
      this.offset = timeline.offset();
      this.size = timeline.width();
      this.limiter.trigger("handle", e);

      jQuery(document).on("mousemove.vsq-tl touchmove.vsq-tl", (e) => {
        this.limiter.trigger("handle", e);
      });
    });

    jQuery(document).on("mouseup.vsq-tl touchend.vsq-tl", (e) => {
      jQuery(document).off("mousemove.vsq-tl touchmove.vsq-tl");
    });

    this.handleResume()
  }

  public destroy(): void {
    this.flowroot.off(".vsq-tl");
    this.slider.disable(false);
  }
}
