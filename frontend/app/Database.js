import { Bindable } from 'curvature/base/Bindable';

const PrimaryKey = Symbol('PrimaryKey');
const Connection = Symbol('Connection');
const Instances  = Symbol('Instances');
const Target = Symbol('Target');
const Store  = Symbol('Store');
const Fetch  = Symbol('Each');
const Name   = Symbol('Name');
const Bank   = Symbol('Bank');

export class Database
{
	constructor(connection)
	{
		Object.defineProperty(this, Bank, {
			value: {}
		});

		Object.defineProperty(this, Connection, {
			value: connection
		});
	}

	static open(dbName, version = 0)
	{
		if(this[Instances][dbName])
		{
			return Promise.resolve(this[Instances][dbName]);
		}

		return new Promise((accept, reject) => {
			const request = indexedDB.open(dbName, version);

			request.onerror = error => {

				Database.dispatchEvent(new CustomEvent('readError', {detail: {
					database:  this[Name]
					, error:   error
					, store:   storeName
					, type:    'read'
					, subType: 'select'
				}}));

				reject(error);
			};

			request.onsuccess = event => {

				const instance = new this(event.target.result);

				this[Instances][dbName] = instance

				instance[Name] = dbName;

				accept(instance);

				accept(this[Instances][dbName]);

			};

			request.onupgradeneeded = event => {

				const connection = event.target.result;

				connection.addEventListener('error', error => {
					console.error(error)
				});

				for(let v = event.oldVersion + 1; v <= version; v++)
				{
					this['_version_' + v](connection);
				}

				const instance = new this(connection);

				this[Instances][dbName] = instance

				instance[Name] = dbName;

				accept(instance);
			};
		});
	}

	static _version_1(database)
	{
		const eventLog = database.createObjectStore(
			'event-log', {keyPath: 'id'}
		);

		eventLog.createIndex('id',      'id',      {unique: false});
		eventLog.createIndex('created', 'created', {unique: false});
	}

	select({store, index, range = null, direction = 'next', limit = 0, offset = 0})
	{
		const t = this[Connection].transaction(store, "readonly");
		const s = t.objectStore(store);
		const i = index
			? s.index(index)
			: s;

		return {
			each:   this[Fetch](i, direction, range, limit, offset)
			, one:  this[Fetch](i, direction, range, 1, offset)
			, then: c=>(this[Fetch](i, direction, range, limit, offset))(e=>e).then(c)
		};
	}

	insert(storeName)
	{
		return (record) => new Promise((accept, reject) => {
			const trans = this[Connection].transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			if(!this[Bank][storeName])
			{
				this[Bank][storeName] = new WeakMap;
			}

			record = Bindable.makeBindable(record);

			const bank = this[Bank][storeName];

			const request = store.add(Object.assign({}, record));

			request.onerror = error => {

				Database.dispatchEvent(new CustomEvent('writeError', {detail: {
					database:  this[Name]
					, record:  record
					, store:   storeName
					, type:    'write'
					, subType: 'insert'
				}}));

				reject(error);
			};

			request.onsuccess = event => {
				const pk = event.target.result;

				bank[pk] = record;

				record[PrimaryKey] = Symbol.for(pk);
				record[Store]      = storeName;

				Database.dispatchEvent(new CustomEvent('write', {detail: {
					database: this[Name]
					, key:    Database.getPrimaryKey(record)
					, store:  storeName
					, type:    'write'
					, subType: 'insert'
				}}));

				accept(record);

				trans.commit();
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
			const trans = this[Connection].transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			const request = store.put(Object.assign({}, record));

			request.onerror = error => {

				Database.dispatchEvent(new CustomEvent('writeError', {detail: {
					database:  this[Name]
					, key:    Database.getPrimaryKey(record)
					, store:   storeName
					, type:    'write'
					, subType: 'update'
				}}));

				reject(error);
			};

			request.onsuccess = event => {

				Database.dispatchEvent(new CustomEvent('write', {detail: {
					database: this[Name]
					, key:    Database.getPrimaryKey(record)
					, store:  storeName
					, type:    'write'
					, subType: 'update'
				}}));

				accept(event);

				trans.commit();
			};
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
			const trans = this[Connection].transaction([storeName], "readwrite");
			const store = trans.objectStore(storeName);

			const request = store.delete(record[PrimaryKey].description);

			request.onerror = error => {

				Database.dispatchEvent(new CustomEvent('writeError', {detail: {
					database: this[Name]
					, key:    Database.getPrimaryKey(record)
					, store:  storeName
					, type:    'delete'
					, subType: 'update'
				}}));

				reject(error);
			};

			request.onsuccess = event => {

				Database.dispatchEvent(new CustomEvent('write', {detail: {
					database: this[Name]
					, key:    Database.getPrimaryKey(record)
					, store:  storeName
					, type:    'write'
					, subType: 'delete'
				}}));

				accept(event);

				trans.commit();
			};
		});
	}

	[Fetch](index, direction, range, limit, offset)
	{
		return callback => new Promise((accept, reject) => {

			const request = index.openCursor(range, direction);
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

				const source    = cursor.source;
				const storeName = source.objectStore
					? source.objectStore.name
					: index.name;

				if(!this[Bank][storeName])
				{
					this[Bank][storeName] = new WeakMap;
				}

				const bank   = this[Bank][storeName];
				const value  = cursor.value;
				const pk     = cursor.primaryKey;

				if(bank[pk])
				{
					Object.assign(bank[pk], value);
				}
				else
				{
					value[PrimaryKey] = Symbol.for(pk);
					value[Store]      = storeName;

					bank[pk] = Bindable.makeBindable(value);
				}

				Database.dispatchEvent(new CustomEvent('read', {detail: {
					database:  this[Name]
					, record:  value
					, store:   storeName
					, type:    'read'
					, subType: 'select'
				}}));

				results[pk] = callback(bank[pk]);

				cursor.continue();
			});

		});
	}

	static getPrimaryKey(record)
	{
		return record[PrimaryKey]
			? record[PrimaryKey].description
			: null;
	}
}

Object.defineProperty(Database, Instances, {
	value: []
});

Object.defineProperty(Database, Target, {
	value: new EventTarget
});

for(const method in Database[Target])
{
	Object.defineProperty(Database, method, {
		value: (...args) => Database[Target][method](...args)
	});
}
