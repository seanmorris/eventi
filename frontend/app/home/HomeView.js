import { View } from 'curvature/base/View';

export class HomeView extends View {
	constructor()
	{
		super();
		this.routes = {};
		this.template = require('./homeView.tmp');

		this.args.events = [];

		this.eventSource = new EventSource('/events');

		this.eventSource.addEventListener(
			'ServerEvent'
			, event => {

				const data = JSON.parse(event.data);
				const id   = event.lastEventId;

				while(this.args.events.length >= 25)
				{
					this.args.events.shift();
				}

				this.args.events.push({data, id});
			}
		);

		this.eventSource.onerror = error => console.error(error);
	}

	send()
	{
		fetch('/send', {method: 'post'});
	}
};
