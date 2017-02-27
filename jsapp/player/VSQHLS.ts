/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
import {VSQ, VSQConfig, VSQType} from "./VSQ";
import Tools from "../Tools";
import Escape from "../Escape";
import RateLimiter from "../RateLimiter";

declare var Hls: any;
export default class VSQHLS {
  private vsq: VSQ;
  private flowroot: JQuery;
  private cfg: VSQConfig;
  private flow: Flowplayer;

  /* a video amit jatszani akarunk hls-el */
  private video: FlowVideo;
  /* a Hls instance */
  private hls: any;
  private limiter: RateLimiter;
  private type: VSQType;

  constructor(vsq: VSQ, type: VSQType) {
    this.vsq = vsq;
    this.flowroot = vsq.getFlowRoot();
    this.cfg = vsq.getConfig();
    this.flow = vsq.getPlayer();
    this.video = jQuery.extend(true, {}, vsq.getVideoInfo(type));
    this.type = type;

    this.initLimiter();
    this.initHls(type);
  }

  private initHls(type: VSQType): void {
    this.hls = new Hls({
      /*
        autoStartLoad: true,
        startPosition : -1,
        capLevelToPlayerSize: false,
        debug: false,
        defaultAudioCodec: undefined,
        initialLiveManifestSize: 1,
        maxBufferLength: 30,
        maxMaxBufferLength: 600,
        maxBufferSize: 60*1000*1000,
        maxBufferHole: 0.5,
        maxSeekHole: 2,
        seekHoleNudgeDuration: 0.01,
        maxFragLookUpTolerance: 0.2,
        liveSyncDurationCount: 3,
        liveMaxLatencyDurationCount: 10,
        enableWorker: true,
        enableSoftwareAES: true,
        manifestLoadingTimeOut: 10000,
        manifestLoadingMaxRetry: 6,
        manifestLoadingRetryDelay: 500,
        manifestLoadingMaxRetryTimeout : 64000,
        startLevel: undefined,
        levelLoadingTimeOut: 10000,
        levelLoadingMaxRetry: 6,
        levelLoadingRetryDelay: 500,
        levelLoadingMaxRetryTimeout: 64000,
        fragLoadingTimeOut: 20000,
        fragLoadingMaxRetry: 6,
        fragLoadingRetryDelay: 500,
        fragLoadingMaxRetryTimeout: 64000,
        startFragPrefech: false,
        appendErrorMaxRetry: 3,
        loader: customLoader,
        fLoader: customFragmentLoader,
        pLoader: customPlaylistLoader,
        xhrSetup: XMLHttpRequestSetupCallback,
        fetchSetup: FetchSetupCallback,
        abrController: customAbrController,
        timelineController: TimelineController,
        enableCEA708Captions: true,
        stretchShortVideoTrack: false,
        forceKeyFrameOnDiscontinuity: true,
        abrEwmaFastLive: 5.0,
        abrEwmaSlowLive: 9.0,
        abrEwmaFastVoD: 4.0,
        abrEwmaSlowVoD: 15.0,
        abrEwmaDefaultEstimate: 500000,
        abrBandWidthFactor: 0.8,
        abrBandWidthUpFactor: 0.7,
        minAutoBitrate: 0
      */
      fragLoadingMaxRetry: 0,
      manifestLoadingMaxRetry: 0,
      levelLoadingMaxRetry: 0,
      initialLiveManifestSize: 2 // min 2 fragment mert sokat akad kulonben
    });
    this.hls.on(Hls.Events.MEDIA_ATTACHED, (evt: string, data: any): void => {
      this.onMediaAttached(evt, data);
    });

    this.hls.on(Hls.Events.MANIFEST_PARSED, (evt: string, data: any): void => {
      this.onManifestParsed(evt, data);
      this.vsq.showTag(this.type);
    });
    this.hls.on(Hls.Events.LEVEL_LOADED, (evt: string, data: any): void => {
      this.log("level loaded, canceling ratelimits");
      this.limiter.cancel();
      this.vsq.showTag(this.type);
    });
    this.hls.on(Hls.Events.ERROR, (evt: string, data: any): void => {
      this.onError(evt, data);
    });

    this.hls.attachMedia(this.vsq.getVideoTags()[ type ]);
  }

  private initLimiter(): void {
    this.limiter = new RateLimiter();
    this.limiter.add("onNetworkError", 3*RateLimiter.SECOND, () => {
      this.hls.startLoad();
    });
    this.limiter.add("onSwapAudioCodec", 3*RateLimiter.SECOND, () => {
      this.hls.swapAudioCodec();
    });
    this.limiter.add("onRecoverMedia", 3*RateLimiter.SECOND, () => {
      this.hls.recoverMediaError();
    });
  }

  private log(...params: Object[]): void {
    if (!VSQ.debug)
      return;

    params.unshift(`[VSQHLS-${this.type}]`);
    console.log.apply(console, params);
  }

  public startLoad(at: number): void {
    this.hls.startLoad(at);
  }
  public stopLoad(): void {
    this.hls.stopLoad();
    this.flushBuffer();
  }
  public destroy(): void {
    this.hls.destroy();
  }
  public flushBuffer(): void {
    this.hls.trigger(Hls.Events.BUFFER_FLUSHING, {
        startOffset: 0,
        endOffset: Number.POSITIVE_INFINITY
      }
    );
  }
  public on(evt: string, cb: any): void {
    this.hls.on(evt, cb);
  }

  get startLevel(): number {
    return this.hls.startLevel;
  }
  set startLevel(level: number) {
    this.hls.startLevel = level;
  }
  get currentLevel(): number {
    return this.hls.currentLevel;
  }
  set currentLevel(level: number) {
    this.hls.currentLevel = level;
  }

  private onMediaAttached(evt: string, data: any): void {
    this.hls.loadSource(this.video.src);
  }

  private onManifestParsed(evt: string, data: any): void {
    this.log("canceling ratelimits");
    this.limiter.cancel();
  }

  private showSeeking() {
    this.flowroot.removeClass('is-paused');
    this.flowroot.addClass('is-seeking');
  }

  private onError(evt: string, data: any): void {
    this.log("error", evt, data);
    switch(data.type) {
      case Hls.ErrorTypes.NETWORK_ERROR:
        switch(data.details) {
          case Hls.ErrorDetails.MANIFEST_LOAD_ERROR:
            if (data.response && data.response.code === 403) {
              this.onAccessError(evt, data);
              return;
            }
            break;

          case Hls.ErrorDetails.LEVEL_LOAD_ERROR:
            if (data.response && data.response.code === 404) {
              this.vsq.hideTag(this.type);
              this.onLevelLoadError(evt, data);
              return;
            }
            break;
        }

        this.vsq.hideTag(this.type);
        // a default hogy ujraprobalkozunk
        this.limiter.trigger("onNetworkError");
        break;
      case Hls.ErrorTypes.MEDIA_ERROR:
        this.onMediaError(evt, data);
        return;
        break;
    }

    this.onUnhandledError(evt, data);
  }

  private onAccessError(evt: string, data: any): void {
    this.flow.trigger(
      "error",
      [this.flow, {code: VSQ.accessDeniedError}]
    );
  }

  private onLevelLoadError(evt: string, data: any): void {
    this.flushBuffer();
    let level = data.context.level;

    // vissza lepunk egy minosegi szintet es imadkozunk hogy az mukodni fog
    if (level != 0 && level <= this.video['vsq-labels'].length - 1)
      this.hls.currentLevel = level - 1;
    else // nincs mire vissza lepni, ujra probalkozni vegtelensegig
      this.limiter.trigger("onNetworkError");
  }

  private onMediaError(evt: string, data: any): void {
    if (!data.fatal)
      return;

    this.flushBuffer();
    this.limiter.trigger("onSwapAudioCodec");
    this.limiter.trigger("onRecoverMedia");
  }

  private onUnhandledError(evt: string, data: any): void {
    if (!data.fatal)
      return;

    this.flushBuffer();
    this.showSeeking();
    // nem tudtuk lekezelni a hibat, mutassunk valamit, 2 = NETWORK_ERROR
    this.flow.trigger("error", [this.flow, {code: 2}]);
  }
}
