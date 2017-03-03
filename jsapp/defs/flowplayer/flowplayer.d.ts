// medium effort typed flowplayer api definition, purely for our own use. Beware
interface FlowConfig {
  keyboard: boolean;
  ratio: number;
  adaptiveRatio: boolean;
  rtmp: number;
  proxy: string;
  splash: boolean;
  live: boolean;
  swf: string;
  swfHls: string;
  speeds: number[];
  tooltip: boolean;
  mouseoutTimeout: number;
  volume: number;
  errors: string[];
  errorUrls: string[];
  playlist: any[];
  clip: any;

  hlsjs?: any;
  poster?: boolean;
  smoothSwitching?: boolean;
  vsq?: any;
}

interface FlowHLSConfig {
  recoverMediaError: boolean;
  recoverNetworkError: boolean;
  smoothSwitching: boolean;
  strict: boolean;
}
interface FlowSource {
  type: string;
  src: string;
}

interface FlowVideo {
  buffer: number;
  hlsjs: FlowHLSConfig;
  sources: FlowSource[];
  title: string;
  type: string;
  src: string;
  autoplay: boolean;
  index: number;
  is_last: boolean;
  duration?: number;
  time?: number;
}

interface FlowSupport {
  animation: boolean;
  browser: any;
  cachedVideoTag: boolean;
  dataload: boolean;
  firstframe: boolean;
  flashVideo: boolean;
  fullscreen: boolean;
  hlsDuration: boolean;
  inlineBlock: boolean;
  inlineVideo: boolean;
  seekable: boolean;
  subtitles: boolean;
  touch: boolean;
  video: boolean;
  volume: boolean;
  zeropreload: boolean;
}

// TODO actual types would be nice
interface FlowCommon {
  addClass(e: any, t: any): any;
  append(e: any, t: any): any;
  appendTo(e: any, t: any): any;
  attr(e: any, t: any, n: any): any;
  browser: any;
  createAbsoluteUrl(e: any): any;
  createElement(e: any, t: any, n: any): any;
  css(e: any, t: any, n: any): any;
  find(e: any, t: any): any;
  findDirect(e: any, t: any): any;
  getPrototype(e: any): any;
  hasClass(e: any, t: any): any;
  hasOwnOrPrototypeProperty(e: any, t: any): any;
  hasParent(e: any, t: any): any;
  height(e: any, t: any): any;
  hostname(e: any): any;
  html(e: any, t: any): any;
  identity(e: any): any;
  insertAfter(e: any, t: any, n: any): any;
  isSameDomain(e: any): any;
  lastChild(e: any): any;
  matches(e: any, t: any): any;
  noop(): void;
  offset(e: any): any;
  pick(e: any, t: any): any;
  prepend(e: any, t: any): any;
  prop(e: any, t: any, n: any): any;
  removeClass(e: any, t: any): any;
  removeNode(e: any): any;
  text(e: any, t: any): any;
  toggleClass(e: any, t: any, n: any): any;
  width(e: any, t: any): any;
  xhrGet(e: any, t: any, n: any): any;
}
interface FlowTimeline {
  max(value: number): void;
  disable(flag: boolean): void;
  slide(value: number, speed?: number, fireEvent?: boolean): void;
  disableAnimation(value?: boolean, alsoCssAnimations?: boolean): void;
}

interface FlowSliders {
  timeline: FlowTimeline;
  volume: any;
}

interface Flowplayer {
  common: FlowCommon;
  conf: FlowConfig;
  currentSpeed: number;
  volumeLevel: number;
  video: FlowVideo;
  support: FlowSupport;

  keyboard: boolean;
  ratio: number;
  adaptiveRatio: boolean;
  rtmp: number;
  proxy: string;
  live: boolean;
  dvr: boolean;
  swf: string;
  swfHls: string;
  speeds: number[];
  tooltip: boolean;
  mouseoutTimeout: number;
  errors: string[];
  errorUrls: string[];
  playlist: any[];
  sliders: FlowSliders;

  // state
  disabled: boolean;
  finished: boolean;
  loading: boolean;
  muted: boolean;
  paused: boolean;
  playing: boolean;
  ready: boolean;
  splash: boolean;
  rtl: boolean;

  poster: any;
  engines: ((player: Flowplayer, root: Element) => any)[];

  (callback: (api: Flowplayer, root: Element) => any): Flowplayer;
  (element: Element, config: Object): Flowplayer;

  on(events: string, handler: (eventObject: Event, ...args: any[]) => any): Flowplayer;
  off(events: string, handler?: (eventObject: Event, ...args: any[]) => any): Flowplayer;

  trigger(event: string, args: any): void;
  load(video: any, args: any): Flowplayer;
  pause(cb?: (api: Flowplayer, root: Element) => any): Flowplayer;
  resume(): Flowplayer;
  toggle(): Flowplayer;
  seek(time: number, cb: (api: Flowplayer, root: Element) => any): Flowplayer;
  seekTo(position: number, cb: (api: Flowplayer, root: Element) => any): Flowplayer;
  mute(flag?: string | boolean, skipStore?: boolean): Flowplayer;
  volume(level: number, skipStore?: boolean): Flowplayer;
  speed(val: number, cb: (api: Flowplayer, root: Element) => any): Flowplayer;
  stop(): Flowplayer;
  unload(): Flowplayer;
  shutdown(): void;
  disable(flag?: string | boolean): Flowplayer;

  next(index?: number): Flowplayer;
  removePlaylistItem(index: number): Flowplayer;
}

declare module "flowplayer" {
  export = flowplayer;
}
declare var flowplayer: Flowplayer;
