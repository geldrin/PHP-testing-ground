"use strict";

class Limit {
  private name: string;
  private duration: number;
  private callback: any;
  private timer: number | null;
  private fireImmediately: boolean;

  constructor(name: string,  callback: any, duration: number, fireImmediately: boolean) {
    this.name = name;
    this.callback = callback;
    this.duration = duration;
    this.fireImmediately = fireImmediately;
  }

  private callLater(args: any[]): void {
    this.timer = null;
    if (!this.fireImmediately && this.callback instanceof Function)
      this.callback.apply(this, args);
  }

  public trigger(...args: any[]): void {
    let shouldCall = this.fireImmediately && this.timer === null;

    if (this.timer !== null)
      clearTimeout(this.timer);

    this.timer = setTimeout(() => {
      this.callLater(args);
    }, this.duration);

    if (shouldCall && this.callback instanceof Function)
      this.callback.apply(this, args);
  }

  public cancel(): void {
    if (this.timer === null)
      return;

    clearTimeout(this.timer);
    this.timer = null;
  }
}

class Limits {
  [key: string]: Limit;
}

export default class RateLimiter {
  public static SECOND = 1000;
  private limits: Limits;

  constructor() {
    this.limits = new Limits();
  }

  private getByName(name: string): Limit {
    let limit = this.limits[name];
    if (limit == null)
      throw new Error("Limiter for " + name + " not found!");

    return limit;
  }

  public add(name: string, callback: any, duration: number, fireImmediately: boolean) {
    this.limits[name] = new Limit(name, callback, duration, fireImmediately);
  }

  public trigger(name: string, ...args: any[]): boolean {
    let limit = this.getByName(name);

    return limit.trigger.apply(limit, args);
  }

  public cancel(name?: string): void {
    if (name != null) {
      this.getByName(name).cancel();
      return;
    }

    for (let k in this.limits) {
      if (!this.limits.hasOwnProperty(k))
        continue;

      let limit = this.limits[k];
      limit.cancel();
    }
  }
}
