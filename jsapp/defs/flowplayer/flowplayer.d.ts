// TODO typed conf object
interface Flowplayer {
	conf: Object;
	paused: boolean;
	engines: ((player: Flowplayer, root: Element) => any)[];

	(callback: (api: Flowplayer, root: Element) => any): Flowplayer;
	(element: Element, config: Object): Flowplayer;

	on(events: string, handler: (eventObject: Event, ...args: any[]) => any): Flowplayer;
}

declare module "flowplayer" {
	export = flowplayer;
}
declare var flowplayer: Flowplayer;
