import { View } from 'curvature/base/View';
import { Database } from '../Database';

export class HomeView extends View {
	constructor()
	{
		super();

		this.routes   = {};
		this.template = require('./homeView.tmp');
		this.database = null;

		this.args.events = [];


		Database.open('event-log').then(database =>  {

			const insertor = database.insert('event-log');

			this.listenForEvents(event => {

				const message = this.parseMessage(event);

				insertor(message.data);

				this.args.events.push(message.data);

				while(this.args.events.length > 25)
				{
					this.args.events.shift();
				}

			});

		}).catch( error => console.error(error) );
	}

	parseMessage(event)
	{
		const message = JSON.parse(event.data);

		return {
			data: message
			, id: event.lastEventId
		};
	}

	send()
	{
		return fetch('/send', {method: 'post'});
	}

	listenForEvents(c)
	{
		this.eventSource = new EventSource('/events');

		this.eventSource.addEventListener('ServerEvent', e => c(e));

		this.eventSource.onerror = error => console.error(error);
	}

	loadLog()
	{
		Database.open('event-log').then(database => {

			const selector = database.select({
				store:       'event-log'
				, index:     'created'
				, direction: 'prev'
				, limit:     2
				, offset:    0
			});

			return selector.each(entry => {

				selector.each(entryB => {
					console.log( entry.created, entry.body, entry == entryB );
				});

			});

		}).catch(error => console.error(error));
	}
};
