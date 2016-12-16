/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/flowplayer/flowplayer.d.ts" />
"use strict";
//import "es6-promise"; // majd ha kell

declare var Hls: any;

/**
 * A flowplayer plugin implementacioert felel (dual-stream, reconnect stb)
 * Typescript rewrite of:
 * https://github.com/flowplayer/flowplayer-hlsjs/tree/06687f55ea4ad83a83515a9d9daf591def4377df
 */
export default class Flow {
  public static engineName = "vsq";
  private static initDone = false;
  private static readonly MASTER = 0;
  private static readonly CONTENT = 1;

  private id: string;
  private videoTags: Element[] = [];
  private hlsEngines: any[] = [];
  private hlsConf: any;

  private player: Flowplayer;
  private root: JQuery;
  private cfg: VSQConfig;
  private volumeLevel: number;
  private eventsInitialized = false;
  private timer: number;

  private maxLevel: number = 0; // hls specific
  private recoverMediaErrorDate: number;
  private swapAudioCodecDate: number;

  constructor(player: Flowplayer, root: Element) {
    Flow.log(arguments);
    this.player = player;
    this.cfg = player.conf.vsq as VSQConfig;
    this.hlsConf = jQuery.extend({
        bufferWhilePaused: true,
        smoothSwitching: true,
        recoverMediaError: true
      },
      flowplayer.conf['hlsjs'],
      this.player.conf['hlsjs'],
      this.player.conf['clip']['hlsjs'],
    );

    this.root = jQuery(root);
    this.id = this.root.attr('data-flowplayer-instance-id');
  }

  private static log(...params: Object[]): void {
    params.unshift("[Flow]");
    console.log.apply(console, params);
  }

  private log(...params: Object[]): void {
    Flow.log(params);
  }

  private callOnArray(data: any[], funcName: string, args?: any): any {
    let ret: any = [];
    for (let i = data.length - 1; i >= 0; i--) {
      let elem = data[i];
      if (elem == null)
        continue;

      ret[i] = data[funcName].apply(elem, args);
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

  private hlsCall(funcName: string, args?: any): any {
    return this.callOnArray(this.hlsEngines, funcName, args);
  }

  private hlsSet(property: string, value: any): void {
    this.setOnArray(this.hlsEngines, property, value);
  }

  private tagCall(funcName: string, args?: any): any {
    return this.callOnArray(this.videoTags, funcName, args);
  }

  private tagSet(property: string, value: any): void {
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
  private static HLSQualitiesSupport(conf: any): boolean {
    let hlsQualities = (conf.clip && conf.clip.hlsQualities) || conf.hlsQualities;

    return flowplayer.support.inlineVideo &&
      (hlsQualities === true ||
      (hlsQualities && hlsQualities.length))
    ;
  }

  public static canPlay(type: string, conf: Object): boolean {
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

  // TODO de-magicnumber
  private doRecover(conf: any, flowEvent: string, isNetworkError: boolean): number | undefined {
    if (conf.debug)
      this.log('recovery.vsq', flowEvent);

    this.root.removeClass('is-paused');
    this.root.addClass('is-seeking');
    if (isNetworkError)
      this.hlsCall('startLoad');
    else {
      let now = performance.now();
      if (!this.recoverMediaErrorDate || now - this.recoverMediaErrorDate > 3000) {
        this.recoverMediaErrorDate = performance.now();
        this.hlsCall('recoverMediaError');
      } else {
        if (!this.swapAudioCodecDate || now - this.swapAudioCodecDate > 3000) {
          this.swapAudioCodecDate = performance.now();
          this.hlsCall('swapAudioCodec');
          this.hlsCall('recoverMediaError');
        } else
          return 3;
      }
    }

    return undefined;
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

  private setupVideoEvents(video: FlowVideo): void {
    if (this.eventsInitialized)
      return;

    let masterHLS = this.hlsEngines[Flow.MASTER];
    let masterTag = this.videoTags[Flow.MASTER];
    let master = jQuery(masterTag);
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
      volumechange: "volume",
      error       : "error"
    };
    let hlsEvents = Hls.Events;

    let currentTime: number = masterTag.currentTime;
    let arg: any = {};
    jQuery.each(events, (videoEvent: string, flowEvent: string): void => {
      videoEvent = this.eventName(videoEvent);

      master.on(videoEvent, (e: Event) => {
        if (flowEvent.indexOf("progress") < 0)
          this.log(videoEvent, flowEvent, e);

        let video = this.player.video;
        switch(flowEvent) {
          case "ready":
            arg = jQuery.extend(arg, video, {
              duration: masterTag.duration,
              seekable: masterTag.seekable.end(null),
              width: masterTag.videoWidth,
              height: masterTag.videoHeight,
              url: video.src
            });
            break;

          case "resume":
            this.removePoster();
            if (!this.hlsConf.bufferWhilePaused)
              this.hlsCall('startLoad', [currentTime]);
            break;

          case "seek":
            this.removePoster();
            if (!this.hlsConf.bufferWhilePaused && masterTag.paused) {
              this.hlsCall('stopLoad');
              this.tagCall('pause');
            }
            arg = currentTime;
            break;

          case "pause":
            this.removePoster();
            if (!this.hlsConf.bufferWhilePaused)
              this.hlsCall('stopLoad');
            break;

          case "progress":
            arg = currentTime;
            break;

          case "speed":
            arg = masterTag.playbackRate;
            break;

          case "volume":
            arg = masterTag.volume;
            break;

          case "buffer":
            let buffered: any;
            let buffer: number = 0;
            try {
              buffered = masterTag.buffered;
              buffer = buffered.end(null);
              if (currentTime) {
                for (var i = buffered.length - 1; i >= 0; i--) {
                  let buffend = buffered.end(i);
                  if (buffend >= currentTime)
                    buffer = buffend;
                }
              }
            } catch(_) {};

            video.buffer = buffer;
            arg = buffer;
            break;

          case "finish":
            let flush = false;

            if (
                 this.hlsConf.bufferWhilePaused && masterHLS.autoLevelEnabled &&
                 (
                   video.loop ||
                   this.player.conf.playlist.length < 2 ||
                   this.player.conf.advance == false
                 )
               ) {
              flush = !masterHLS.levels[this.maxLevel].details;
              if (!flush)
                masterHLS[this.maxLevel].details.fragments.forEach((frag: any) => {
                  flush = !!flush || !frag.loadCounter;
                });
              }

            if (flush) {
              this.hlsCall('trigger', [
                hlsEvents.BUFFER_FLUSHING,
                {
                  startOffset: 0,
                  endOffset: video.duration
                }
              ]);

              this.log(this.maxLevel);
              this.hlsSet('nextLoadLevel', this.maxLevel);
              this.hlsCall('startLoad', [masterHLS.config.startPosition]);
              this.maxLevel = 0;

              if (!video.loop) {
                // hack to prevent Chrome engine from hanging
                master.one(this.eventName("play"), () => {
                  if (masterTag.currentTime >= masterTag.duration)
                    masterTag.currentTime = 0;
                });
              }
            }
            break;

          case "error":
            let code = masterTag.error.code;
            if (
                 (this.hlsConf.recoverMediaError && code === 3) ||
                 (this.hlsConf.recoverNetworkError && code === 2) ||
                 (this.hlsConf.recover && (code === 2 || code === 3))
               )
              code = this.doRecover(this.player.conf, flowEvent, code === 2);

            arg = false;
            if (code !== undefined) {
              arg = {code: code};
              if (code > 2)
                arg.video = jQuery.extend(video, {url: video.src});
            }
            break;
        }

        if (arg === false)
          return arg;

        this.player.trigger(flowEvent, [this.player, arg]);

        if (flowEvent === "ready" && this.player.quality) {
          let selectorIndex: number;
          if (this.player.quality === "abr")
            selectorIndex = 0;
          else
            selectorIndex = this.player.qualities.indexOf(this.player.quality) + 1;

          this.root.find(".fp-quality-selector li").eq(selectorIndex).addClass(this.activeQuality);
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
        master.one(this.eventName("seeked"), () => {
          this.addPoster();
        });
    }

    this.player.on(this.eventName("error"), () => {
      this.hlsCall('destroy');
    });
  }

  private setupHLSEvents(video: FlowVideo): void {
    let conf = jQuery.extend({}, this.hlsConf);
    conf.autoStartLoad = false;

    this.hlsEngines[Flow.MASTER] = new Hls(conf);
    this.hlsEngines[Flow.MASTER].VSQType = Flow.MASTER;
    this.hlsEngines[Flow.CONTENT] = new Hls(conf);
    this.hlsEngines[Flow.CONTENT].VSQType = Flow.CONTENT;

    let hlsEvents = Hls.Events;
    jQuery.each(hlsEvents, (eventName: string, hlsEvent: string): void => {
      let shouldTrigger = this.hlsConf.listeners && this.hlsConf.listeners.indexOf(hlsEvent) > -1;
      jQuery.each(this.hlsEngines, (hlsType: number, hls: any): void => {

        hls.on(hlsEvent, (e: Event, data: any): void => {
          let errorTypes = Hls.ErrorTypes;
          let errorDetails = Hls.ErrorDetails;

          switch(eventName) {
            case "MEDIA_ATTACHED":
              hls.loadSource(video.src);
              break;

            case "MANIFEST_PARSED":
              delete this.player.quality;
              hls.startLoad(hls.config.startPosition);
              break;

            case "FRAG_LOADED":
              if (
                  this.hlsConf.bufferWhilePaused && !this.player.live &&
                  hls.autoLevelEnabled && hls.nextLoadLevel > this.maxLevel
                )
                this.maxLevel = hls.nextLoadLevel;
              break;

            case "FRAG_PARSING_METADATA":
              break;

            case "ERROR":
              let flowError: number | undefined = undefined;
              let errorObj: any = {};
              if (data.fatal || this.hlsConf.strict) {
                switch(data.type) {
                  case errorTypes.NETWORK_ERROR:
                    if (this.hlsConf.recoverNetworkError)
                      this.doRecover(this.player.conf, data.type, true);
                    else if (data.frag && data.frag.url) {
                      errorObj.url = data.frag.url;
                      flowError = 2;
                    } else
                      flowError = 4;
                    break;

                  case errorTypes.MEDIA_ERROR:
                    if (this.hlsConf.recoverMediaError)
                      flowError = this.doRecover(this.player.conf, data.type, false);
                    else
                      flowError = 3;
                    break;

                  default:
                    hls.destroy();
                    flowError = 5;
                    break;
                }

                if (flowError !== undefined) {
                  errorObj.code = flowError;
                  if (flowError > 2) {
                    errorObj.video = jQuery.extend(video, {
                      url: data.url || video.src
                    });
                  }

                  this.player.trigger("error", [this.player, errorObj]);
                }
              } else {
                switch(data.details) {
                  case errorDetails.BUFFER_STALLED_ERROR:
                  case errorDetails.FRAG_LOOP_LOADING_ERROR: // !!!FALLTHROUGH!!!
                    this.root.addClass('is-seeking'); // a recoveryClass
                    jQuery(this.videoTags).one(this.eventName("timeupdate"), () => {
                      this.root.removeClass('is-seeking');
                    });
                    break;
                }
              }
              break;

            default:
              throw new Error("unhandled hls eventname: " + eventName);
          }

          if (shouldTrigger && hlsType === Flow.MASTER)
            this.player.trigger(e, [this.player, data]);
        });

      });
    });

    jQuery.each(this.hlsEngines, (hlsType: number, hls: any): void => {
      let tag = this.videoTags[hls.VSQType];
      if (this.hlsConf.adaptOnStartOnly) {
        jQuery(tag).one(this.eventName("timeupdate"), () => {
          hls.loadLevel = hls.loadLevel;
        });
      }

      hls.attachMedia(tag);
      // TODO autoplay
    });
  }

  private createVideoTag(video: FlowVideo): Element {
    let autoplay = false;

    let ret: any = document.createElement('video');
    ret.src = video.src;
    ret.type = this.getType(video.type);
    ret.className = 'fp-engine vsq-engine';
    ret.autoplay = autoplay? 'autoplay': false;
    ret.setAttribute('x-webkit-airplay', 'allow');

    return ret;
  }

  private destroyVideoTag(index: number): void {
    let tagElem = this.videoTags[index];

    let elem = jQuery(tagElem);
    elem.find('source').removeAttr('src');
    elem.removeAttr('src');

    // warning squelch
    (tagElem as any).load();
    elem.remove();
  }

  public load(video: FlowVideo): void {
    // mihez fogjuk prependelni a videokat
    let root = this.root.find('.fp-player');
    this.hlsConf = jQuery.extend(
      this.hlsConf,
      this.player.conf.hlsjs,
      this.player.conf.clip.hlsjs,
      video.hlsjs
    );

    // eloszor a content videot, mert mindig csak prependelunk
    // es igy lesz jo a sorrend
    if (this.cfg.secondarySources) {
      if (this.videoTags[Flow.CONTENT])
        this.destroyVideoTag(Flow.CONTENT);

      // deep copy the video, and set its properties
      let secondVideo = jQuery.extend(true, {}, video);
      secondVideo.src = this.cfg.secondarySources[0].src;
      secondVideo.sources = this.cfg.secondarySources;

      // and insert it into the DOM
      this.videoTags[Flow.CONTENT] = this.createVideoTag(secondVideo);
      this.videoTags[Flow.CONTENT].load();
      let engine = jQuery(this.videoTags[Flow.CONTENT]);
      engine.addClass('vsq-content');
      root.prepend(engine);
    }

    if (this.videoTags[Flow.MASTER])
      this.destroyVideoTag(Flow.MASTER);

    this.videoTags[Flow.MASTER] = this.createVideoTag(video);
    this.videoTags[Flow.MASTER].load();
    let engine = jQuery(this.videoTags[Flow.MASTER]);
    engine.addClass('vsq-master');
    root.prepend(engine);

    this.setupVideoEvents(video);
  }

  public pause(): void {
    this.multiCall('pause');
  }

  public resume(): void {
    this.multiCall('play');
  }

  public speed(speed: Number): void {
    this.multiSet('playbackRate', speed);
    this.player.trigger('speed', [this.player, speed]);
  }

  public volume(volume: Number): void {
    this.multiSet('volume', volume);
  }

  private eventName(event?: string): string {
    let postfix = '.' + Flow.engineName;
    if (!event)
      return postfix;

    return event + postfix;
  }

  public unload(): void {
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

  public seek(to: Number): void {
    this.multiSet('currentTime', to);
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

    flowplayer((api: Flowplayer): void => {
      // to take precedence over VOD quality selector
      if (Flow.HLSQualitiesSupport(api.conf) && Flow.canPlay("application/x-mpegurl", api.conf))
        api.pluginQualitySelectorEnabled = true;
      else
        api.pluginQualitySelectorEnabled = false;
    });

    Flow.initDone = true;
  }
}

interface Listeners {
  [index: string]: ((e: Event) => void);
}

/* definialni hogy kell a vsq flowplayer confignak kineznie */
interface FlowHLSConfig {
  recoverMediaError: boolean;
  recoverNetworkError: boolean;
  smoothSwitching: boolean;
  strict: boolean;
}
interface FlowSource {
  readonly type: string;
  readonly src: string;
}
interface FlowVideo {
  hlsjs: FlowHLSConfig;
  sources: FlowSource[];
  title: string;
  type: string;
  src: string;
  autoplay: boolean;
}
interface VSQConfig {
  secondarySources?: FlowSource[];
}