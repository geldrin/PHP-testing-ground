interface Flowplayer {
	(element: Element, config: Object): Flowplayer;
	on(events: string, handler: (eventObject: Event, ...args: any[]) => any): Flowplayer;
}
declare module "flowplayer" {
	export = flowplayer;
}
declare var flowplayer: Flowplayer;
