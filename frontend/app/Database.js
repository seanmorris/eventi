import { Bindable } from 'curvature/base/Bindable';

const PrimaryKey = Symbol('PrimaryKey');

const Store = Symbol('Store');
const Fetch = Symbol('Each');

export class Database
{
	constructor(connection)
	{
		this.connection = connection;
		this.bank       = {};
	}

	static open(dbName, version = 0)
	{
		if(this.instances[dbName])
		{
			return Promise.resolve(this.instances[dbName]);
		}

		return new Promise((accept, reject) => {
			const request = indexedDB.open(dbName, version);

			request.onerror = error => reject(error);

			request.onsuccess = event => {

				this.instances[dbName] = new this(event.target.result);

				accept(this.instances[dbName]);

			};

			request.onupgradeneeded = event => {

				const database = event.target.result;

				database.onerror = error => console.error(error);

				this._version_0(database);

				this.instances[dbName] = new this(database);

				accept(this.instances[dbName]);
			};
		});
	}

	static _version_0(database)
	{
		const eventLog = database.createObjectStore(
			'event-log', {keyPath: 'id'}
		);

		eventLog.createIndex('id',      'id',      {unique: false});
		eventLog.createIndex('created', 'created', {unique: false});
	}

	select({store, index, direction = 'next', limit = 0, offset = 0})
	{
		const t = this.connection.transaction(store, "readonly");
		const s = t.objectStore(store);
		const i = s.index(index);

		return {
			each:   this[Fetch](i, direction, limit, offset)
			, one:  this[Fetch](i, direction, 1, offset)
			, then: c=>(this[Fetch](i, direction, limit, offset))(e=>e).then(c)
		};
	}

	insert(storeName)
	{
		return (record) => new Promise((accept, reject) => {
			const trans = this.connection.transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			if(!this.bank[storeName])
			{
				this.bank[storeName] = {};
			}

			record = Bindable.makeBindable(record);

			const bank = this.bank[storeName];

			const request = store.add(Object.assign({}, record));

			request.onerror   = error => reject(error);
			request.onsuccess = event => {

				const pk = event.target.result;

				bank[pk] = record;

				console.log(pk, bank);

				record[PrimaryKey] = Symbol.for(pk);
				record[Store]      = storeName;

				accept(record);
			};
		});
	}

	update(record)
	{
		if(!record[PrimaryKey])
		{
			throw Error('Value provided is not a DB record!');
		}

		const storeName = record[Store];

		return new Promise((accept, reject) => {
			const trans = this.connection.transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			const request = store.put(Object.assign({}, record));

			request.onsuccess = database => accept(database);
			request.onerror   = error    => reject(error);
		});
	}

	delete(record)
	{
		if(!record[PrimaryKey])
		{
			throw Error('Value provided is not a DB record!');
		}

		const storeName = record[Store];

		return new Promise((accept, reject) => {
			const trans = this.connection.transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			const request = store.delete(record[PrimaryKey].description);

			request.onsuccess = database => accept(database);
			request.onerror   = error    => reject(error);
		});
	}

	[Fetch](index, direction, limit, offset)
	{
		return callback => new Promise((accept, reject) => {

			const request = index.openCursor(null, direction);
			const results = {};
			let i = 0;

			request.addEventListener('success', event => {
				const cursor = event.target.result;

				if(!cursor || (limit && i - offset >= limit))
				{
					accept(results);
					i++;

					return;
				}

				if(offset && offset > i)
				{
					cursor.continue();
					i++;

					return;
				}

				i++;

				const source = cursor.source;

				if(!this.bank[source.objectStore.name])
				{
					this.bank[source.objectStore.name] = {};
				}

				const bank   = this.bank[source.objectStore.name];
				const value  = cursor.value;
				const pk     = cursor.primaryKey;

				console.log(pk, bank);

				if(bank[pk])
				{
					Object.assign(bank[pk], value);
				}
				else
				{
					value[PrimaryKey] = Symbol.for(pk);
					value[Store]      = source.objectStore.name;

					bank[pk] = Bindable.makeBindable(value);
				}

				results[pk] = callback(bank[pk]);

				cursor.continue();
			});

		});
	}
}

Database.instances = [];
