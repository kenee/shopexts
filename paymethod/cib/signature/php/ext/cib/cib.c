/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2007 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id: header,v 1.16.2.1.2.1 2007/01/01 19:32:09 iliaa Exp $ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_cib.h"

/* If you declare any globals in php_cib.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(cib)
*/

/* True global resources - no need for thread safety here */
static int le_cib;

/* {{{ cib_functions[]
 *
 * Every user visible function must have an entry in cib_functions[].
 */
zend_function_entry cib_functions[] = {
	PHP_FE(confirm_cib_compiled,	NULL)		/* For testing, remove later. */
	PHP_FE(cibSign,	NULL)
	PHP_FE(,	NULL)
	{NULL, NULL, NULL}	/* Must be the last line in cib_functions[] */
};
/* }}} */

/* {{{ cib_module_entry
 */
zend_module_entry cib_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"cib",
	cib_functions,
	PHP_MINIT(cib),
	PHP_MSHUTDOWN(cib),
	PHP_RINIT(cib),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(cib),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(cib),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_CIB
ZEND_GET_MODULE(cib)
#endif

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("cib.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_cib_globals, cib_globals)
    STD_PHP_INI_ENTRY("cib.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_cib_globals, cib_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ php_cib_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_cib_init_globals(zend_cib_globals *cib_globals)
{
	cib_globals->global_value = 0;
	cib_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(cib)
{
	/* If you have INI entries, uncomment these lines 
	REGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(cib)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(cib)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(cib)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(cib)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "cib support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */


/* Remove the following function when you have succesfully modified config.m4
   so that your module can be compiled into PHP, it exists only for testing
   purposes. */

/* Every user-visible function in PHP should document itself in the source */
/* {{{ proto string confirm_cib_compiled(string arg)
   Return a string to confirm that the module is compiled in */
PHP_FUNCTION(confirm_cib_compiled)
{
	char *arg = NULL;
	int arg_len, len;
	char *strg;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &arg, &arg_len) == FAILURE) {
		return;
	}

	len = spprintf(&strg, 0, "Congratulations! You have successfully modified ext/%.78s/config.m4. Module %.78s is now compiled into PHP.", "cib", arg);
	RETURN_STRINGL(strg, len, 0);
}
/* }}} */
/* The previous line is meant for vim and emacs, so it can correctly fold and 
   unfold functions in source code. See the corresponding marks just before 
   function definition, where the functions purpose is also documented. Please 
   follow this convention for the convenience of others editing your code.
*/

/* {{{ proto int cibSign()
   unsigned char *key,unsigned char *message,unsigend char *mac) */
PHP_FUNCTION(cibSign)
{

	int  len,i,mac_key_len,message_len;
	char *mac_key,mac[9],*asc_buff,*message;
	
	int err;

	if (ZEND_NUM_ARGS() != 2) {
		WRONG_PARAM_COUNT;
	}
	
	//获得参数
	if (
			zend_parse_parameters(
				ZEND_NUM_ARGS() TSRMLS_CC, "ss",
				&mac_key,
				&mac_key_len,
				&message,
				&message_len
			) == FAILURE
		) return;
	len = strlen(message);
	//des加密
	err = ANSIX99(mac_key, message, len, mac); 
	if(err < 0) {
		RETURN_LONG(err);
	}
	asc_buff = malloc(16);
	memset( asc_buff, 0, 16);
	bcd_to_asc( asc_buff, mac, 16, 1 );
	memset( mac, 0, 9);
	strncpy(mac, asc_buff, 8);

	RETURN_STRING(mac,1);
}
/* }}} */

/* {{{ proto  ()
    */
PHP_FUNCTION()
{
	if (ZEND_NUM_ARGS() != 0) {
		WRONG_PARAM_COUNT;
	}
	php_error(E_WARNING, ": not yet implemented");
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
