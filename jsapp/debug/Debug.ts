/// <reference path="../defs/jquery/jquery.d.ts" />
/// <reference path="../defs/TraceKit/tracekit.d.ts" />
"use strict";

declare var BASE_URI: string;
export default class Debug {
  private $: JQueryStatic;
  private url: string;
  private stringify;

  public constructor($: JQueryStatic) {
    this.$ = $;
    this.url = BASE_URI + 'telemetry/exception';
    this.stringify = (JSON || {}).stringify;
  }

  public init(): void {
    // unsupported
    if (!this.stringify)
      return;

    TraceKit.report.subscribe((stack) => {
      this.onError(stack);
    });
  }

  private onError(stack: StackTrace): void {
    // TODO extra browser data, es user context-et
    let data = this.stringify(stack);
    this.$.ajax({
      url: this.url,
      contentType: 'application/json',
      data: data,
      type: 'POST',
      timeout: 5000
    });
  }
}
