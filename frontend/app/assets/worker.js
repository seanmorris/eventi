function Node(){};
function IntersectionObserver(){};

let init = false;

let connections = 0;

window = {};
importScripts('/app.js');
init = true;

const Database = require('Database').Database;

Database.addEventListener('write', event => postMessage(event.detail));

Database.open('event-log', 1).then(database =>  {

	const eventSource = new EventSource('/events');

	eventSource.addEventListener('ServerEvent', event => {

		const insert = database.insert('event-log');

		return insert(JSON.parse(event.data)).then(entry => {
			const type = 'insert';
			const key  = Database.getPrimaryKey(entry);

			// postMessage({
			// 	type, key
			// });
		});

	});

	eventSource.addEventListener('error', error => {

		console.error(error);

	});

});

self.addEventListener('message', event => {

	if(!init)
	{
	}

	const Database = require('Database').Database;


}, {passive: true});
