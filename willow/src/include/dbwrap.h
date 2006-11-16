/* @(#) $Id$ */
/* This source code is in the public domain. */
/*
 * Willow: Lightweight HTTP reverse-proxy.
 * dbwrap: C++ Berkeley DB wrapper.
 */

#ifndef DBWRAP_H
#define DBWRAP_H

#if defined __SUNPRO_C || defined __DECC || defined __HP_cc
# pragma ident "@(#)$Id$"
#endif

#include <sys/types.h>

#include <algorithm>
using std::back_inserter;

#include <db.h>

#include "willow.h"
#include "util.h"

namespace db {

template<typename Key, typename Value>
struct database;
struct transaction;

struct environment : noncopyable {
	static environment	*open(string const &path);
	static environment	*create(string const &path);
	~environment();

	template<typename Key, typename Value>
	database<Key, Value>  *open_database(string const &path);

	template<typename Key, typename Value>
	database<Key, Value> *create_database(string const &path);

	int	error		(void) const;
	string	strerror	(void) const;
	void	close		(void);

	struct transaction *transaction(void);

private:
	template<typename Key, typename Value>
	friend struct database;
	friend struct transaction;

	explicit environment(string const &path, uint32_t flags);

	DB_ENV	*_env;
	int	 _error;
};

template<typename Key, typename Value>
struct database : noncopyable {
	int	error		(void) const;
	string	strerror	(void) const;
	void	close		(void);

	~database();

	bool	 put(Key const &key, Value const &value, transaction *);
	bool	 put(Key const &key, Value const &value);
	Value	*get(Key const &key, transaction *);
	Value	*get(Key const &key);

private:
	friend struct environment;

	explicit database(environment *env, string const &path, uint32_t flags);
	static void errcall(DB_ENV const *, char const *pfx, char const *msg);

	DB		*_db;
	environment	*_env;
	int		 _error;
};

struct transaction {
	~transaction();

	bool	 commit(void);
	bool	 rollback(void);

	int	 error(void) const;
	string	 strerror(void) const;

private:
	friend struct environment;
	template<typename Key, typename Value>
	friend struct database;

	transaction(environment *);
	DB_TXN	*_txn;
	int	 _error;
};

struct marshalling_buffer {
	marshalling_buffer()
		: _buf(NULL)
		, _size(0)
		, _bufsz(0)
	{}

	marshalling_buffer(char const *buf, uint32_t sz)
		: _buf(const_cast<char *>(buf))
		, _size(0)
		, _bufsz(sz)
	{}
		
	~marshalling_buffer(void) {
	}

	void reserve(size_t size) {
		_bufsz = size;
		_buf = new char[size];
	}

	template<typename T>
	void append(T const &);

	template<typename charT, typename traits, typename allocator>
	void append(basic_string<charT, traits, allocator> const &);

	void append_bytes(char const *buf, size_t s) {
std::cout<<"appending "<<s<<" bytes\n";
		assert(_size + s <= _bufsz);
		memcpy(_buf + _size, buf, s);
		_size += s;
	}

	char const *buffer(void) const {
		return _buf;
	}

	size_t size(void) const {
		return _size;
	}

	template<typename T>
	bool extract(T &);

	template<typename charT, typename traits, typename allocator>
	bool extract(basic_string<charT, traits, allocator> &);

	bool extract_bytes(char *b, size_t s) {
std::cout<<"extracting "<<s<<" bytes\n";
		if (_size + s > _bufsz)
			return false;
		memcpy(b, _buf + _size, s);
		_size += s;
		return true;
	}

private:
	char	*_buf;
	size_t	 _size;
	size_t	 _bufsz;
	bool	 _delete;
};

template<typename T>
void
marshalling_buffer::append(T const &o) {
	append_bytes((char const *)&o, sizeof(o));
}

template<>
void
marshalling_buffer::append<imstring>(imstring const &o);

template<typename charT, typename traits, typename allocator>
void
marshalling_buffer::append(basic_string<charT, traits, allocator> const &s) {
	append<size_t>(s.size() * sizeof(charT));
	append_bytes(s.data(), s.size() * sizeof(charT));
}

template<typename T>
bool
marshalling_buffer::extract(T &o)
{
	return extract_bytes((char *) &o, sizeof(o));
}

template<typename charT, typename traits, typename allocator>
bool
marshalling_buffer::extract(basic_string<charT, traits, allocator> &s)
{
size_t	sz;
	if (!extract<size_t>(sz))
		return false;
	if (_size + sz > _bufsz)
		return false;
	s.reserve(sz);
	copy(_buf + _size, _buf + _size + sz, back_inserter<charT>(s));
	_size += sz;
	return true;
}

template<typename T>
struct marshaller {
};

template<>
struct marshaller<char> {
	pair<char const *, uint32_t> marshall(char c) {
		return make_pair(&c, sizeof(c));
	}
};

template<>
struct marshaller<int> {
	pair<char const *, uint32_t> marshall(int c) {
		return make_pair((char const *)&c, sizeof(c));
	}
};

template<>
struct marshaller<long> {
	pair<char const *, uint32_t> marshall(long c) {
		return make_pair((char const *)&c, sizeof(c));
	}
};

template<>
struct marshaller<unsigned long> {
	pair<char const *, uint32_t> marshall(unsigned long c) {
		return make_pair((char const *)&c, sizeof(c));
	}
};

template<>
struct marshaller<unsigned int> {
	pair<char const *, uint32_t> marshall(unsigned int c) {
		return make_pair((char const *)&c, sizeof(c));
	}
};

template<>
struct marshaller<string> {
	pair<char const *, uint32_t> marshall(string const &s) {
		return make_pair(s.data(), s.size());
	}
};

template<typename Key, typename Value>		
database<Key, Value> *
environment::open_database(string const &name)
{
	return new database<Key, Value>(this, name, 0);
}

template<typename Key, typename Value>
database<Key, Value> *
environment::create_database(string const &name)
{
	return new database<Key, Value>(this, name, DB_CREATE);
}

template<typename Key, typename Value>
database<Key, Value>::database(environment *env, string const &path, uint32_t flags)
	: _env(env)
{
	_error = db_create(&_db, env->_env, 0);
	if (_error != 0) {
		_db->close(_db, 0);
		_db = NULL;
		return;
	}

	_error = _db->open(_db, NULL, path.c_str(), NULL, DB_HASH,
			DB_THREAD | DB_AUTO_COMMIT | flags, 0);
	if (_error != 0) {
		_db->close(_db, 0);
		_db = NULL;
		return;
	}

	_db->set_errcall(_db, &database::errcall);
}

template<typename Key, typename Value>
void
database<Key, Value>::errcall(DB_ENV const *, char const *pfx, char const *msg)
{
	if (pfx)
		wlog(WLOG_WARNING, format("%s: %s") % pfx % msg);
	else
		wlog(WLOG_WARNING, msg);
}

template<typename Key, typename Value>
int
database<Key, Value>::error(void) const
{
	return _error;
}

template<typename Key, typename Value>
string
database<Key, Value>::strerror(void) const
{
	return db_strerror(_error);
}

template<typename Key, typename Value>
database<Key, Value>::~database(void)
{
	if (_db)
		_db->close(_db, 0);
}

template<typename Key, typename Value>
bool
database<Key, Value>::put(Key const &key, Value const &value)
{
	return put (key, value, NULL);
}

template<typename Key, typename Value>
bool
database<Key, Value>::put(Key const &key, Value const &value, transaction *txn)
{
pair<char const *, uint32_t>	mkey, mvalue;
DBT				dbkey, dbvalue;
marshaller<Key>			keymarsh;
marshaller<Value>		valuemarsh;
	memset(&dbkey, 0, sizeof(dbkey));
	memset(&dbvalue, 0, sizeof(dbvalue));
	mkey = keymarsh.marshall(key);
	mvalue = valuemarsh.marshall(value);
	dbkey.data = (void *) mkey.first;
	dbkey.size = mkey.second;
	dbvalue.data = (void *) mvalue.first;
	dbvalue.size = mvalue.second;
	
	_error = _db->put(_db, txn ? txn->_txn : NULL, &dbkey, &dbvalue,
		DB_NOOVERWRITE | (txn ? 0 : DB_AUTO_COMMIT));
	delete[] mkey.first;
	delete[] mvalue.first;
	if (_error != 0)
		return false;
	return true;
}

template<typename Key, typename Value>
Value *
database<Key, Value>::get(Key const &key)
{
	return get(key, NULL);
}

template<typename Key, typename Value>
Value *
database<Key, Value>::get(Key const &key, transaction *txn)
{
pair<char const *, uint32_t>	mkey;
DBT				dbkey, dbvalue;
marshaller<Key>			keymarsh;
marshaller<Value>		vmarsh;
	memset(&dbkey, 0, sizeof(dbkey));
	memset(&dbvalue, 0, sizeof(dbvalue));
	mkey = keymarsh.marshall(key);
	dbkey.data = (void *) mkey.first;
	dbkey.size = mkey.second;
	dbvalue.flags = DB_DBT_MALLOC;
	_error = _db->get(_db, txn ? txn->_txn : NULL, &dbkey, &dbvalue, 0);
	if (_error != 0)
		return NULL;
Value	*ret;
	ret = vmarsh.unmarshall(pair<char const *, uint32_t>(
			(char const *) dbvalue.data, dbvalue.size));
	free(dbvalue.data);
	return ret;
}

template<typename Key, typename Value>
void
database<Key, Value>::close(void)
{
	_db->close(_db, 0);
	_db = NULL;
}

} // namespace db

#endif
