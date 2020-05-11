import { View } from 'curvature/base/View';
import { Database } from '../Database';

export class HomeView extends View
{
	constructor()
	{
		super();

		this.routes   = {};
		this.template = require('./homeView.tmp');

		this.args.selected = [];
		this.args.events   = [];

		Database.open('event-log', 1).then(database => {
			const worker = new Worker('worker.js');

			worker.addEventListener('message', event => {
				if(event.data.subType == 'insert')
				{
					const store = event.data.store;
					const range = IDBKeyRange.only(event.data.key);

					database.select({store, range}).one(entry => {

						this.args.events.push(entry);

						while(this.args.events.length > 25)
						{
							this.args.events.shift();
						}
					});
				}
			});
		});
	}

	send()
	{
		return fetch('/send', {method: 'post'});
	}

	loadLog()
	{
		Database.open('event-log', 1).then(database => {
			this.args.selected.splice(0, this.args.selected.length);

			const select = database.select({
				store:       ['event-log']
				, index:     'created'
				, direction: 'prev'
				, limit:     25
			});

			return select.each(entry => this.args.selected.push(entry));
		}).catch(error => console.error(error))
	}

	editEven()
	{
		Database.open('event-log', 1).then(database => {
			const select = database.select({
				store:       ['event-log']
				, index:     'created'
				, direction: 'prev'
				, limit:     50
			});

			return select.each(entry => {

				if(!entry.edited && Math.floor(entry.created) % 2 === 0)
				{
					entry.body += ' Edited!!!';
					entry.edited = true;
				}

				return database.update(entry);
			});
		}).catch(error => console.error(error));
	}

	deleteLast500()
	{
		Database.open('event-log', 1).then(database => {
			const select = database.select({
				store:       ['event-log']
				, index:     'created'
				, direction: 'prev'
				, limit:     50
			});

			return select.each(entry => {
				entry.body += ' -- DELETED!';
				database.delete(entry)
			});
		}).catch(error => console.error(error));
	}
};
