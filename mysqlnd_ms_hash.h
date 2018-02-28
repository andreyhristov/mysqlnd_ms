/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
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

#ifndef MYSQLND_MS_HASH_H
#define MYSQLND_MS_HASH_H

#define mms_hash_init(ht, nSize, pHashFunction, pDestructor, persistent)	_mms_hash_init((ht), (nSize), (dtor_func_t) (pDestructor), (persistent) ZEND_FILE_LINE_CC)
#define mms_hash_add(ht, arKey, nKeyLength, pData, nDataSize, pDest)		_mms_hash_add(ht, arKey, nKeyLength, pData ZEND_FILE_LINE_CC)
#define mms_hash_update(ht, arKey, nKeyLength, pData, nDataSize, pDest)		_mss_hash_update(ht, arKey, nKeyLength, pData ZEND_FILE_LINE_CC)
#define mms_hash_exists(ht, arKey, nKeyLength)								zend_hash_str_exists(ht, arKey, nKeyLength)
#define mms_hash_sort(ht, sort_func, compare_func, renumber)				zend_hash_sort(ht, compare_func, renumber)
#define mms_hash_get_current_key_ex(ht, str_index, str_length, num_index, duplicate, pos) _mms_hash_get_current_key_ex(ht, str_index, str_length, num_index, pos)
int mms_hash_find(const HashTable * ht, const char * const arKey, const size_t nKeyLength, void ** pData);
int mms_hash_index_find(const HashTable * const ht, const ulong h, void ** pData);
#define mms_hash_del(ht, arKey, nKeyLength)									zend_hash_str_del(ht, arKey, nKeyLength)

// XXX: here pData should be *pData, as pre-7 a ** (&ptr) is passed instead of * (ptr)
#define mms_hash_next_index_insert(ht, pData, nDataSize, pDest)				zend_hash_next_index_insert_ptr(ht, pData)

// XXX: here pData should be *pData, as pre-7 a ** (&ptr) is passed instead of * (ptr)
#define mms_hash_index_update(ht, h, pData, nDataSize, pDest)				zend_hash_index_update_ptr(ht, h, pData)

#define mms_hash_get_current_data(ht, pData)								mms_hash_get_current_data_ex(ht, pData, NULL)
int mms_hash_get_current_data_ex(const HashTable * const ht, void ** pData, const HashPosition * const pos);

int _mms_hash_get_current_key_ex(const HashTable * const ht, char ** str_index, uint * const str_length, zend_ulong * const num_index, const HashPosition * const pos);

int _mms_hash_init(HashTable * ht, const uint32_t nSize, dtor_func_t pDestructor, const zend_bool persistent ZEND_FILE_LINE_DC);

int _mms_hash_add(HashTable * ht, const char * const arKey, const size_t nKeyLength, void * pData ZEND_FILE_LINE_DC);
int _mms_hash_update(HashTable * ht, const char * const arKey, const size_t nKeyLength, void * pData ZEND_FILE_LINE_DC);


/* Just for convenience to have common prefix. For now the old names will work too */
#define mms_hash_has_more_elements(ht)										zend_hash_has_more_elements(ht)
#define mms_hash_move_forward_ex(ht, pos)									zend_hash_move_forward_ex(ht, pos)
#define mms_hash_internal_pointer_reset_ex(ht, pos)							zend_hash_internal_pointer_reset_ex(ht, pos)
#define mms_hash_internal_pointer_reset(ht)									zend_hash_internal_pointer_reset(ht)
#define mms_hash_num_elements(ht)											zend_hash_num_elements(ht)
#define mms_hash_move_forward(ht)											zend_hash_move_forward(ht)
void mms_hash_clean(HashTable * ht);
void mms_hash_destroy(HashTable * ht);




#endif /* MYSQLND_MS_HASH_H */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
