/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
//import "es6-promise"; // majd ha kell
import {BasePlugin} from "./Flow/BasePlugin";
import LayoutChooser from "./Flow/LayoutChooser";
import QualityChooser from "./Flow/QualityChooser";
import Tools from "../Tools";
import Escape from "../Escape";
import Locale from "../Locale";

declare var Hls: any;
declare var WebKitMediaSource: any;

/**
 * A flowplayer plugin implementacioert felel (dual-stream, reconnect stb)
 * Typescript rewrite of:
 * https://github.com/flowplayer/flowplayer-hlsjs/tree/06687f55ea4ad83a83515a9d9daf591def4377df
 */
export class Flow {
  public static engineName = "vsq";
  public static debug = false;
  private static initDone = false;
  public static readonly MASTER = 0;
  public static readonly CONTENT = 1;

  private l: Locale;
  private id: string;
  private loadedCount = 0;
  public longerType: 0 | 1 = 0; // alapbol Flow.MASTER
  private videoTags: HTMLVideoElement[] = [];
  private videoInfo: FlowVideo[] = [];
  private hlsEngines: any[] = [];
  private hlsConf: any;

  private player: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;
  private eventsInitialized = false;
  public introOrOutro = false;

  private accessDeniedError: number;
  private swapAudioCodecDate: number;
  private recoverMediaDate: number;

  private plugins: BasePlugin[] = [];

  constructor(player: Flowplayer, root: Element) {
    Flow.log("constructor", arguments);

    this.player = player;
    this.cfg = player.conf.vsq as VSQConfig || {};
    this.l = this.cfg.locale;

    this.player.conf.errors.push(this.l.get('access_denied'));
    this.accessDeniedError = player.conf.errors.length - 1;

    this.hlsConf = jQuery.extend({
        bufferWhilePaused: true,
        smoothSwitching: true,
        recoverMediaError: true
      },
      flowplayer.conf['hlsjs'],
      this.player.conf['hlsjs'],
      this.player.conf['clip']['hlsjs'],
    );
    Flow.debug = !!this.cfg.debug;

    this.root = jQuery(root);
    this.id = this.root.attr('data-flowplayer-instance-id');

    if (!this.cfg.contentOnRight)
      this.root.addClass('vsq-contentleft');

    this.plugins.push(new LayoutChooser(this));
    this.plugins.push(new QualityChooser(this));
  }

  public getRoot(): JQuery {
    return this.root;
  }
  public getConfig(): VSQConfig {
    return this.cfg;
  }
  public getPlayer(): Flowplayer {
    return this.player;
  }
  public getVideoTags(): HTMLVideoElement[] {
    return this.videoTags;
  }
  public getVideoInfo(type: number): FlowVideo {
    return this.videoInfo[type];
  }
  public getHLSEngines(): any[] {
    return this.hlsEngines;
  }

  private hideFlowLogo(): void {
    this.root.children('a[href*="flowplayer.org"]').hide();
  }

  private configKey(key: string): string {
    return 'vsq-player-' + key;
  }

  private static log(...params: Object[]): void {
    if (!Flow.debug)
      return;

    params.unshift("[Flow]");
    console.log.apply(console, params);
  }

  private log(...params: Object[]): void {
    Flow.log(...params);
  }

  private callOnArray(data: any[], funcName: string, args?: any): any {
    let ret: any = [];
    for (let i = data.length - 1; i >= 0; i--) {
      let elem = data[i];
      if (elem == null)
        continue;

      ret[i] = elem[funcName].apply(elem, args);
    }

    return ret;
  }

  private setOnArray(data: any[], property: string, value: any): void {
    let ret: any = [];
    for (let i = data.length - 1; i >= 0; i--) {
      let elem = data[i];
      if (elem == null)
        continue;

      elem[property] = value;
    }
  }

  public hlsCall(funcName: string, args?: any): any {
    return this.callOnArray(this.hlsEngines, funcName, args);
  }

  public hlsSet(property: string, value: any): void {
    this.setOnArray(this.hlsEngines, property, value);
  }

  public tagCall(funcName: string, args?: any): any {
    return this.callOnArray(this.videoTags, funcName, args);
  }

  public tagSet(property: string, value: any): void {
    this.setOnArray(this.videoTags, property, value);
  }

  private getType(type: string): string {
    if (Flow.isHLSType(type))
      return "application/x-mpegurl";

    return type;
  }

  private static isHLSType(type: string): boolean {
    return type.toLowerCase().indexOf("mpegurl") > -1;
  }

  public static canPlay(type: string, conf: FlowConfig): boolean {
    let b = flowplayer.support.browser;
    let wn = window.navigator;
    let isIE11 = wn.userAgent.indexOf("Trident/7") > -1;

    // engine disabled for player or clip
    if (
         conf['vsq'] === false || conf.clip['vsq'] === false ||
         conf['hlsjs'] === false || conf.clip['hlsjs'] === false
       )
      return false;

    if (Flow.isHLSType(type)) {
      // https://bugzilla.mozilla.org/show_bug.cgi?id=1244294
      if (
          conf.hlsjs &&
          conf.hlsjs.anamorphic &&
          wn.platform.indexOf("Win") === 0 &&
          b.mozilla && b.version.indexOf("44.") === 0
         )
        return false;

      // https://github.com/dailymotion/hls.js/issues/9
      return isIE11 || !b.safari;
    }

    return false;
  }

  private addPoster(): void {
    let master = jQuery(this.videoTags[Flow.MASTER]);
    master.one(this.eventName("timeupdate"), () => {
      this.root.addClass("is-poster");
      this.player.poster = true;
    });
  }

  private removePoster(): void {
    if (!this.player.poster)
      return;

    let master = jQuery(this.videoTags[Flow.MASTER]);
    master.one(this.eventName("timeupdate"), () => {
      this.root.removeClass("is-poster");
      this.player.poster = false;
    });
  }

  public hasMultipleVideos(): boolean {
    if (this.introOrOutro)
      return false;

    return this.cfg.secondarySources.length !== 0;
  }

  private isLongerVideo(e: Event): boolean {
    let type = this.getTypeFromEvent(e);
    if (this.introOrOutro)
      return type === Flow.MASTER;

    return type === this.longerType;
  }

  private syncVideos(): void {
    // ha csak egy video van, nincs mihez syncelni
    if (!this.hasMultipleVideos())
      return;

    // live videonal nem fog a currentTime sose pontosan megegyezni, hagyjuk
    if (this.player.live)
      return;

    let master = this.videoTags[Flow.MASTER];
    let content = this.videoTags[Flow.CONTENT];

    // ha az egyik felvetelt mar befejeztuk
    if (master.currentTime == 0 || master.currentTime >= master.duration)
      return;
    if (content.currentTime == 0 || content.currentTime >= content.duration)
      return;

    // ha az elteres a ketto kozott tobb mint X masodperc
    // akkor mindig a master felvetelhez igazodunk
    if (Math.abs(master.currentTime - content.currentTime) > 0.5) {
      this.log("syncing videos to master");
      content.currentTime = master.currentTime;
    }
  }

  private handleLoadedData(e: Event): boolean | undefined {
    // csak akkor kell kivarni mind az esetlegesen ketto videot
    // ha ez eppen a master, amugy csak egy lesz mindig
    if (this.player.video.index === this.cfg.masterIndex) {

      // master mindig van, content nem biztos
      this.loadedCount++;
      let vidCount = 1 + this.cfg.secondarySources.length;
      if (this.loadedCount != vidCount) {
        e.stopImmediatePropagation();
        return false;
      }

      // mivel a default longerType ertek a Flow.MASTER igy csak egy esetet kell nezni
      if (
          vidCount > 1 &&
          this.videoTags[Flow.CONTENT].duration > this.videoTags[Flow.MASTER].duration
         )
        this.longerType = Flow.CONTENT;
    } else // hogy a longerType mindig ertelmes legyen akkor is ha outro kovetkezik
      this.longerType = Flow.MASTER;

    let tag = this.videoTags[this.longerType];
    let data = jQuery.extend(this.player.video, {
      duration: tag.duration,
      seekable: tag.seekable.end(0),
      width: tag.videoWidth, // TODO ezeket mire hasznalja a flowplayer
      height: tag.videoHeight,
      // az src mindig ugyanaz lesz, hiaba master vagy content
      url: this.videoInfo[Flow.MASTER].src
    });

    this.triggerPlayer("ready", data);

    // ha volt intro, akkor egyertelmi az autoplay miutan betoltottuk
    // mert az intro lejatszasanak a befejezese jelentette azt hogy betoltodott
    // a master, ergo eredetileg elinditottak
    if (this.player.video.index === this.cfg.masterIndex && this.cfg.masterIndex > 0)
      this.tagCall('play');

    return false;
  }

  private handlePlay(e: Event): boolean | undefined {
    let tag = e.currentTarget as HTMLVideoElement;
    // ismeretlen bug, de kezeljuk le
    // hack to prevent Chrome engine from hanging
    if (tag.currentTime >= tag.duration)
      tag.currentTime = 0;

    let type = this.getTypeFromEvent(e);
    // a lenyeg csak az hogy egyszer fusson le
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    this.removePoster();
    if (!this.hlsConf.bufferWhilePaused) {
      if (this.introOrOutro)
        this.hlsEngines[Flow.MASTER].startLoad(tag.currentTime);
      else
        this.hlsCall('startLoad', [tag.currentTime]);
    }

    this.triggerPlayer("resume", undefined);
  }

  private handlePause(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    let tag = e.currentTarget as HTMLVideoElement;
    // ha tobb video van csak akkor nem erdekel minket ha a rovidebb felvetelnek
    // lett vege (mert ha vege egy videonak, akkor jon egy pause is)
    if (
         this.hasMultipleVideos() && type !== this.longerType &&
         tag.currentTime >= tag.duration
       ) {
      e.stopImmediatePropagation();
      return false;
    }

    // amugy a rovidebb video pause nem erdekel minket abszolut
    if (type !== this.longerType) {
      e.stopImmediatePropagation();
      return false;
    }

    this.removePoster();
    if (!this.hlsConf.bufferWhilePaused)
      this.hlsCall('stopLoad');
    this.triggerPlayer("pause", undefined);
  }

  private handleEnded(e: Event): boolean | undefined {
    // vagy nem szabad inditani content tehat intro/outro jatszodik epp
    //   ergo csak a mastert kell figyelembe venni,
    // vagy kelett inditani contentet, es mostmar figyelni kell hogy a megfelelo
    //   tag befejezodeset nezzuk
    if (!this.isLongerVideo(e)) {
      e.stopImmediatePropagation();
      return false;
    }

    let video = this.player.video;
    this.hlsCall('trigger', [
      Hls.Events.BUFFER_FLUSHING,
      {
        startOffset: 0,
        endOffset: this.cfg.duration * 0.9
      }
    ]);

    this.tagCall('pause');

    if (this.introOrOutro && !video.is_last) {
      this.player.next();

      // az intro csak egyszer jatszodik le, utana soha tobbet
      // onnan tudjuk hogy intro hogy a masterIndex nem nulla
      // ergo a master elott csak intro lehet
      if (video.index === 0 && this.cfg.masterIndex !== 0) {
        this.player.removePlaylistItem(0);
        this.cfg.masterIndex--; // mivel kitoroltuk az introt, az index is csokkent
      }
    }

    if (video.is_last)
      this.triggerPlayer("finish", undefined);
  }

  private handleProgress(e: Event): boolean | undefined {
    if (!this.isLongerVideo(e)) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = this.videoTags[this.longerType];
    let buffer: number = 0;
    try {
      let buffered = tag.buffered;
      buffer = buffered.end(0);
      if (tag.currentTime) {
        for (var i = buffered.length - 1; i >= 0; i--) {
          let buffend = buffered.end(i);
          if (buffend >= tag.currentTime)
            buffer = buffend;
        }
      }
    } catch(_) {};

    this.player.video.buffer = buffer;
    this.triggerPlayer("buffer", buffer);
  }

  private handleRateChange(e: Event): boolean | undefined {
    if (!this.isLongerVideo(e)) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = e.currentTarget as HTMLVideoElement;
    this.triggerPlayer("speed", tag.playbackRate);
  }

  private handleSeeked(e: Event): boolean | undefined {
    if (!this.isLongerVideo(e)) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = e.currentTarget as HTMLVideoElement;
    this.removePoster();
    if (!this.hlsConf.bufferWhilePaused && tag.paused) {
      this.hlsCall('stopLoad');
      this.tagCall('pause');
    }

    this.triggerPlayer("seek", tag.currentTime);
    return false;
  }

  private handleTimeUpdate(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);

    // ha a contenthez nem szabad nyulni mert eppen nem a konkret master video megy
    // vagy ha nem a hoszabbik tipus vagyunk
    if (
        (this.introOrOutro && type !== Flow.MASTER) ||
        (!this.introOrOutro && type !== this.longerType)
       ) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = this.videoTags[this.longerType];
    this.triggerPlayer("progress", tag.currentTime);

    this.syncVideos();
  }

  private handleVolumeChange(e: Event): boolean | undefined {
    let type = this.getTypeFromEvent(e);
    if (type === Flow.CONTENT) {
      e.stopImmediatePropagation();
      return false;
    }

    let tag = e.currentTarget as HTMLVideoElement;
    this.triggerPlayer("volume", tag.volume);
  }

  private handleError(e: Event): boolean | undefined {
    e.stopImmediatePropagation();
    const MEDIA_ERR_NETWORK = 2;
    const MEDIA_ERR_DECODE = 3;

    let type = this.getTypeFromEvent(e);
    let err = this.videoTags[type].error.code || MEDIA_ERR_DECODE;
    this.log(this.videoTags[type].error, e)

    let arg: any = {code: err};
    if (err > MEDIA_ERR_NETWORK)
      arg.video = jQuery.extend(this.videoInfo[type], {url: this.videoInfo[type].src});

    this.player.trigger("error", [this.player, arg]);
    return false;
  }

  private triggerPlayer(event: string, data: any): void {
    if (event !== "buffer" && event !== "progress")
      this.log("[flow event]", event, data);

    this.player.trigger(event, [this.player, data]);
    this.hideFlowLogo();
  }

  private getTypeFromEvent(e: Event): number {
    let t = jQuery(e.currentTarget);
    if (!t.is('.vsq-master, .vsq-content'))
      throw new Error("Unknown event target");

    if (t.is('.vsq-master'))
      return Flow.MASTER;

    return Flow.CONTENT;
  }

  private setupVideoEvents(video: FlowVideo): void {
    if (this.eventsInitialized)
      return;

    // TODO live sync-hez nezni a buffer eventeket es ha a master bufferel
    // akkor a content addig pausolni, es vica versa?
    let sources = jQuery(this.videoTags);

    // video event -> flowplayer event
    let events = {
      ended       : "finish",
      loadeddata  : "ready",
      pause       : "pause",
      play        : "resume",
      progress    : "buffer",
      ratechange  : "speed",
      seeked      : "seek",
      timeupdate  : "progress",
      volumechange: "volume"
    };

    jQuery.each(events, (videoEvent: string, flowEvent: string): void => {
      videoEvent = this.eventName(videoEvent);
      sources.on(videoEvent, (e: Event): boolean | undefined => {
        if (e.type !== "progress" && e.type !== "timeupdate")
          this.log("event", videoEvent, flowEvent, e);

        switch(videoEvent) {
          case "loadeddata.vsq":
            return this.handleLoadedData(e);
          case "play.vsq":
            return this.handlePlay(e);
          case "pause.vsq":
            return this.handlePause(e);
          case "ended.vsq":
            return this.handleEnded(e);
          case "progress.vsq":
            return this.handleProgress(e);
          case "ratechange.vsq":
            return this.handleRateChange(e);
          case "seeked.vsq":
            return this.handleSeeked(e);
          case "timeupdate.vsq":
            return this.handleTimeUpdate(e);
          case "volumechange.vsq":
            return this.handleVolumeChange(e);
          case "error.vsq":
            return this.handleError(e);
          default:
            throw new Error("unhandled event: " + videoEvent);
        }
      });
    });

    if (this.player.conf.poster) {
      this.player.on(this.eventName("stop"), () => {
        this.addPoster();
      });

      // ha live akkor postert vissza
      // amit varunk: az autoplay mindig false, ergo a postert kirakhatjuk
      if (this.player.live)
        jQuery(this.videoTags[Flow.MASTER]).one(this.eventName("seeked"), () => {
          this.addPoster();
        });
    }
  }

  private eventName(event?: string): string {
    let postfix = '.' + Flow.engineName;
    if (!event)
      return postfix;

    return event + postfix;
  }

  private createVideoTag(video: FlowVideo): HTMLVideoElement {
    let ret: HTMLVideoElement = document.createElement('video');
    ret.src = video.src;
    ret.className = 'fp-engine vsq-engine';

    ret.setAttribute('type', this.getType(video.type));

    if (this.cfg.autoplay) {
      ret.autoplay = true;
      ret.setAttribute('autoplay', 'autoplay');
    } else
      ret.autoplay = false;

    ret.setAttribute('x-webkit-airplay', 'allow');

    return ret;
  }

  private destroyVideoTag(index: number): void {
    let tagElem = this.videoTags[index];

    let elem = jQuery(tagElem);
    elem.find('source').removeAttr('src');
    elem.removeAttr('src');

    tagElem.load();
    elem.remove();
    delete(this.videoTags[index]);
  }

  private setupHLS(type: number): void {
    let video = this.videoInfo[type];
    let hls = new Hls({
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
      initialLiveManifestSize: 2 // min 2 fragment mert sokat akad kulonben
    });

    hls.on(Hls.Events.MEDIA_ATTACHED, (event: string, data: any): void => {
      hls.loadSource(video.src);
    });
    hls.on(Hls.Events.ERROR, (event: string, err: any): void => {
      if (!err.fatal)
        return;

      this.root.removeClass('is-paused');
      this.root.addClass('is-seeking');
      let now = performance.now();
      switch(err.type) {
        case Hls.ErrorTypes.NETWORK_ERROR:
          // 403 -> checkstreamaccess visszautasitott
          if (err.response && err.response.code === 403) {
            this.player.trigger("error", [this.player, {code: this.accessDeniedError}]);
            return;
          }

          // muszaj ratelimitelni
          if (!this.recoverMediaDate || now - this.recoverMediaDate > 3000) {
            this.recoverMediaDate = performance.now();
            hls.startLoad();
          }
          return;
        case Hls.ErrorTypes.MEDIA_ERROR:
          if (!this.swapAudioCodecDate || now - this.swapAudioCodecDate > 3000) {
            this.swapAudioCodecDate = performance.now();
            hls.swapAudioCodec();
          }

          if (!this.recoverMediaDate || now - this.recoverMediaDate > 3000) {
            this.recoverMediaDate = performance.now();
            hls.recoverMediaError();
          }
          return;
      }

      // nem tudtuk lekezelni a hibat, mutassunk valamit, 2 = NETWORK_ERROR
      let arg: any = {code: 2};
      this.player.trigger("error", [this.player, arg]);
    });
    hls.attachMedia(this.videoTags[type]);
    this.hlsEngines[type] = hls;

    for (let i = this.plugins.length - 1; i >= 0; i--)
      this.plugins[i].setupHLS(hls, type);
  }

  public load(video: FlowVideo): void {
    this.introOrOutro = true;

    // vagy csak egy video van, vagy ez nem a master
    if ((video.index === 0 && video.is_last) || video.index === this.cfg.masterIndex)
      this.introOrOutro = false;

    // volt elottunk intro, autoplay
    if (video.index === this.cfg.masterIndex && this.cfg.masterIndex > 0)
      video.autoplay = true;

    // mihez fogjuk prependelni a videokat
    let root = this.root.find('.fp-player');
    root.find('img').remove();

    this.hlsConf = jQuery.extend(
      this.hlsConf,
      this.player.conf.hlsjs,
      this.player.conf.clip.hlsjs,
      video.hlsjs
    );

    // outro video kovetkezik, destroy a contentet ha volt
    if (video.index > this.cfg.masterIndex && this.videoTags[Flow.CONTENT])
      this.destroyVideoTag(Flow.CONTENT);

    // eloszor a content videot, mert mindig csak prependelunk
    // es igy lesz jo a sorrend
    // de csak akkor rakjuk ki ha a master videot akarjuk loadolni
    if (
         video.index === this.cfg.masterIndex &&
         this.hasMultipleVideos()
       ) {
      if (this.videoTags[Flow.CONTENT])
        this.destroyVideoTag(Flow.CONTENT);

      // deep copy the video, and set its properties
      let secondVideo = jQuery.extend(true, {}, video);
      secondVideo.src = this.cfg.secondarySources[0].src;
      secondVideo['vsq-labels'] = this.cfg.secondarySources[0]['vsq-labels'];
      secondVideo.sources = this.cfg.secondarySources;
      this.videoInfo[Flow.CONTENT] = secondVideo;

      // and insert it into the DOM
      this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo);
      this.videoTags[Flow.CONTENT].load();
      let engine = jQuery(this.videoTags[Flow.CONTENT]);
      engine.addClass('vsq-content');
      root.prepend(engine);

      this.setupHLS(Flow.CONTENT);
    }

    if (this.videoTags[Flow.MASTER])
      this.destroyVideoTag(Flow.MASTER);

    this.videoInfo[Flow.MASTER] = video;
    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
    this.videoTags[Flow.MASTER].load();
    let engine = jQuery(this.videoTags[Flow.MASTER]);
    engine.addClass('vsq-master');
    // vagy intro/outro es nincs content
    if (
        video.index !== this.cfg.masterIndex ||
        !this.hasMultipleVideos()
       )
      engine.addClass("vsq-fullscale");

    root.prepend(engine);
    this.setupHLS(Flow.MASTER);

    this.player.on(this.eventName("error"), () => {
      this.unload();
    });

    this.setupVideoEvents(video);

    for (let i = this.plugins.length - 1; i >= 0; i--)
      this.plugins[i].load();

    if (this.cfg.autoplay)
      this.tagCall("play");
  }

  public pause(): void {
    this.tagCall('pause');
  }

  public resume(): void {
    if (this.introOrOutro) {
      this.videoTags[Flow.MASTER].play();
      return;
    }

    // amugy minden videonak
    this.tagCall('play');
  }

  public speed(speed: Number): void {
    this.tagSet('playbackRate', speed);
    this.player.trigger('speed', [this.player, speed]);
  }

  public volume(volume: Number): void {
    this.tagSet('volume', volume);
  }

  public unload(): void {
    for (let i = this.plugins.length - 1; i >= 0; i--)
      this.plugins[i].destroy();

    let videoTags = jQuery(this.videoTags);
    videoTags.remove();

    this.hlsCall('destroy');

    let listeners = this.eventName();
    this.player.off(listeners);
    this.root.off(listeners);
    videoTags.off(listeners);

    for (let i = this.hlsEngines.length - 1; i >= 0; i--)
      this.hlsEngines.pop();

    for (let i = this.videoTags.length - 1; i >= 0; i--)
      this.videoTags.pop();
  }

  public seek(to: number): void {
    // tuti csak egy video van, egyszeru
    if (!this.hasMultipleVideos()) {
      this.videoTags[Flow.MASTER].currentTime = to;
      return;
    }

    /*
      Ha tobb video van:
        - lehet hogy seekelni akarunk, de az adott video nem eleg hosszu
          ilyenkor a rovid videot a vegere seekeltetjuk es leallitjuk
        - ha visszafele seekelunk, es tudunk mind a kettoben, lehet hogy az egyik
          video pausolva van
          ha barmelyik video nem volt pausolva, akkor a videokat el kell inditani
          miutan meg volt a seek
    */

    // kigyujtjuk azokat a videokat amikben lehet oda seekelni ahova szeretnenk
    // es megnezzuk hogy epp megy e valamelyik video (playing)
    let tags: HTMLVideoElement[] = [];
    let playing = false;
    for (let i = this.videoTags.length - 1; i >= 0; i--) {
      let tag = this.videoTags[i];
      playing = playing || !tag.paused;

      if (tag.duration > to)
        tags.push(tag);
      else {
        // ha nem lehet a videoban seekelni mert rovidebb akkor a vegere es pause
        tag.currentTime = tag.duration;
      }
    }

    // a maradek videokban ugrunk es ha elozoleg jatszodtak akkor inditjuk oket
    this.setOnArray(tags, 'currentTime', to);
    if (playing)
      this.callOnArray(tags, 'play', []);
  }

  public pick(sources: FlowSource[]): FlowSource | null {
    if (sources.length == 0)
      throw new Error("Zero length FlowSources passed");

    for (let i = 0; i < sources.length; ++i) {
      let source = sources[i];
      if (!Flow.isHLSType(source.type))
        continue;

      source.src = flowplayer.common.createAbsoluteUrl(source.src);
      return source;
    }

    return null;
  }

  public static setup(): void {
    if (Flow.initDone)
      return;

    let proxy: any = (player: Flowplayer, root: Element) => {
      return new Flow(player, root);
    };
    proxy.engineName = Flow.engineName;
    proxy.canPlay = Flow.canPlay;

    flowplayer.engines.unshift(proxy);
    Flow.initDone = true;
  }
}

/* definialni hogy kell a vsq flowplayer confignak kineznie */
export interface VSQConfig {
  type: string;
  debug: boolean;
  duration: number;
  autoplay: boolean;
  secondarySources: FlowSource[];
  contentOnRight: boolean;
  masterIndex: number;
  locale: Locale;
}
