/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2012 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Andrey Hristov <andrey@php.net>                              |
  +----------------------------------------------------------------------+
*/

/* $Id: mysqlnd_ms.c 311179 2011-05-18 11:26:22Z andrey $ */
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "ext/mysqlnd/mysqlnd.h"
#include "ext/mysqlnd/mysqlnd_debug.h"
#include "ext/mysqlnd/mysqlnd_priv.h"
#ifndef mnd_emalloc
#include "ext/mysqlnd/mysqlnd_alloc.h"
#endif
#if PHP_VERSION_ID >= 50400
#include "ext/mysqlnd/mysqlnd_ext_plugin.h"
#endif
#include "mysqlnd_ms.h"


/* {{{ _mms_hash_init */
void
_mms_hash_init(HashTable * ht, const uint32_t nSize, dtor_func_t pDestructor, const zend_bool persistent ZEND_FILE_LINE_DC)
{
	DBG_ENTER("_mms_hash_init");

	zend_hash_init(ht, nSize, NULL, pDestructor, persistent);
	DBG_VOID_RETURN;
}
/* }}} */


/* {{{ _mms_hash_add */
int
_mms_hash_add(HashTable * ht, const char * const arKey, const size_t nKeyLength, void * pData ZEND_FILE_LINE_DC)
{
	int ret;
	DBG_ENTER("_mms_hash_add");
	ret = zend_hash_str_add_ptr(ht,  arKey, nKeyLength, pData)? SUCCESS:FAILURE;
	DBG_RETURN(ret);
}
/* }}} */


/* {{{ _mms_hash_update */
int
_mms_hash_update(HashTable * ht, const char * const arKey, const size_t nKeyLength, void * pData ZEND_FILE_LINE_DC)
{
	int ret;
	DBG_ENTER("_mms_hash_update");
	ret = zend_hash_str_update_ptr(ht,  arKey, nKeyLength, pData);
	DBG_RETURN(ret);
}
/* }}} */


/* {{{ mms_hash_find */
int
mms_hash_find(const HashTable * ht, const char * const arKey, const size_t nKeyLength, void ** pData);
{
	void * pDataLocal;

	DBG_ENTER("mms_hash_find");
	pDataLocal = zend_hash_str_find_ptr(ht, arKey, nKeyLength);
	if (pdata && pDataLocal) {
		*pData = pDataLocal;
	}
	DBG_RETURN(pDataLocal? SUCCESS : FAILURE);
}
/* }}} */


/* {{{ mms_hash_index_find */
int
mms_hash_index_find(const HashTable * const ht, const ulong h, void ** pData);
{
	void * pDataLocal;

	DBG_ENTER("mms_hash_index_find");
	pDataLocal = zend_hash_index_find_ptr(ht, h);
	if (pdata && pDataLocal) {
		*pData = pDataLocal;
	}
	DBG_RETURN(pDataLocal? SUCCESS : FAILURE);
}
/* }}} */


/* {{{ _mms_hash_get_current_key_ex */
int
_mms_hash_get_current_key_ex(const HashTable * const ht, char ** str_index, uint * const str_length, zend_ulong * const num_index, const HashPosition * const pos);
{
	int ret;
	zend_string * key = NULL;

	DBG_ENTER("_mms_hash_get_current_key_ex");

	ret = zend_hash_get_current_key_ex(ht, &key, num_index, pos);
	if (ret == HASH_KEY_IS_STRING) {
		*str_index = key.val;
		*str_length = key.len;
	}
	
	DBG_RETURN(ret);
}
/* }}} */


/* {{{ _mms_hash_get_current_data_ex */
int
mms_hash_get_current_data_ex(const HashTable * const ht, void ** pData, const HashPosition * const pos)
{
	void * pDataLocal;

	DBG_ENTER("_mms_hash_get_current_data_ex");
	pDataLocal = zend_hash_get_current_data_ptr_ex(ht, pos);
	if (pDataLocal && pData) {
		*pData = pDataLocal;
	}
	DBG_RETURN(pDataLocal? SUCCESS : FAILURE);
}
/* }}} */


/* {{{ mms_hash_clean */
void
mms_hash_clean(HashTable * ht)
{
	DBG_ENTER("mms_hash_clean");
	zend_hash_clean(ht);
	DBG_VOID_RETURN;
}
/* }}} */


/* {{{ mms_hash_destroy */
void
mms_hash_destroy(HashTable * ht)
{
	DBG_ENTER("mms_hash_destroy");
	zend_hash_destroy(ht);
	DBG_VOID_RETURN;
}
/* }}} */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
