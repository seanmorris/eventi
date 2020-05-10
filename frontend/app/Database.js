const PrimaryKey = Symbol('PrimaryKey');

const Each = Symbol('each');

export class Database
{
	constructor(connection)
	{
		this.connection = connection;
		this.bank       = {};
	}

	select({store, index, direction = 'next', limit = 0, offset = 0})
	{
		const t = this.connection.transaction([store], "readonly");
		const s = t.objectStore(store);
		const i = s.index(index);

		return {
			each:  this[Each](i, direction, limit, offset)
			, one: this[Each](i, direction, 1, offset)
		};
	}

	insert(storeName)
	{
		return (record) => new Promise((accept, reject) => {
			const trans = this.connection.transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			const request = store.add(record);

			request.onsuccess = database => accept(database);
			request.onerror   = error    => reject(error);
		});
	}

	static open(dbName)
	{
		if(this.instances[dbName])
		{
			return Promise.resolve(this.instances[dbName]);
		}

		return new Promise((accept, reject) => {
			const request = indexedDB.open('event-log', 1);

			request.onerror = error => reject(error);

			request.onsuccess = event => {

				this.instances[dbName] = new this(event.target.result);

				accept(this.instances[dbName]);

			};

			request.onupgradeneeded = event => {

				const database = event.target.result;

				database.onerror = error => console.error(error);

				const eventLog = database.createObjectStore(
					'event-log', {keyPath: 'id'}
				);

				eventLog.createIndex('id',      'id',      {unique: false});
				eventLog.createIndex('created', 'created', {unique: false});

				this.instances[dbName] = new this(database);

				accept(this.instances[dbName]);
			};
		});
	}

	static _version_0(){}

	[Each](index, direction, limit, offset)
	{
		return callback => {

			return new Promise((accept, reject) => {

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

					if(!this.bank[cursor.source.name])
					{
						this.bank[cursor.source.name] = new WeakMap;
					}

					const bank = this.bank[cursor.source.name];
					const pk   = cursor.primaryKey;

					let value  = cursor.value;

					if(bank[pk])
					{
						Object.assign(bank[pk], value);
					}
					else
					{
						value[PrimaryKey] = Symbol.for(pk);

						bank[pk] = value;
					}

					results[pk] = callback(bank[pk]);

					cursor.continue();
				});

			});
		}
	}
}

Database.instances = [];
