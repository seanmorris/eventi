import { View } from 'curvature/base/View';
import { Database } from '../Database';

export class HomeView extends View
{
	constructor()
	{
		super();

		this.routes   = {};
		this.template = require('./homeView.tmp');
		this.database = null;

		this.args.selected = [];
		this.args.events   = [];


		Database.open('event-log', 1).then(database =>  {

			const insertor = database.insert('event-log');

			return this.listenForEvents(event => {

				const message = this.parseMessage(event);
				const entry   = message.data;

				return insertor(entry).then(entry => {

					this.args.events.push(entry);

					while(this.args.events.length > 25)
					{
						this.args.events.shift();
					}

				});
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
		Database.open('event-log', 1).then(database => {

			const selector = database.select({
				store:       ['event-log']
				, index:     'created'
				, direction: 'prev'
				, limit:     25
				, offset:    1
			});

			this.args.selected = [];

			return selector.each(entry => {

				this.args.selected.push(entry);

				return entry;

			}).then(results => {

				// console.log(results);

			});

		}).catch(error => {

			console.error(error);

		});
	}

	editEven()
	{
		Database.open('event-log', 1).then(database => {

			const selector = database.select({
				store:       ['event-log']
				, index:     'created'
				, direction: 'prev'
				, limit:     50
			});

			return selector.each(entry => {

				entry.body = '!!!';

				if(!entry.edited && Math.floor(entry.created) % 2 === 0)
				{
					entry.edited = true;
				}

				return database.update(entry);

			}).then(results => {

				return Promise.all(Object.values(results));

			});

		}).catch(error => {

			console.error(error);

		});
	}

	deleteLast500()
	{
		Database.open('event-log', 1).then(database => {

			const selector = database.select({
				store:       ['event-log']
				, index:     'created'
				, direction: 'prev'
				, limit:     500
			});

			return selector.each(entry => {

				return database.delete(entry);

			});

		}).catch(error => {

			console.error(error);

		});
	}
};
