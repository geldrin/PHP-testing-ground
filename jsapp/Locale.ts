"use strict";
export default class Locale {
  private data: Object;
  constructor(data: Object) {
    this.data = data;
  }

  get(key: string) {
    if (this.data[key])
      return this.data[key];

    return key;
  }
}