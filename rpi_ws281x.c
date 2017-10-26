/* rpi_ws281x extension for PHP */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "ext/standard/info.h"
#include "php_rpi_ws281x.h"

/* {{{ void rpi_ws281x_test1()
 */
PHP_FUNCTION(rpi_ws281x_test1)
{
	ZEND_PARSE_PARAMETERS_NONE();

	php_printf("The extension %s is loaded and working!\r\n", "rpi_ws281x");
}
/* }}} */

/* {{{ string rpi_ws281x_test2( [ string $var ] )
 */
PHP_FUNCTION(rpi_ws281x_test2)
{
	char *var = "World";
	size_t var_len = sizeof("World") - 1;
	zend_string *retval;

	ZEND_PARSE_PARAMETERS_START(0, 1)
		Z_PARAM_OPTIONAL
		Z_PARAM_STRING(var, var_len)
	ZEND_PARSE_PARAMETERS_END();

	retval = strpprintf(0, "Hello %s", var);

	RETURN_STR(retval);
}
/* }}}*/

/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(rpi_ws281x)
{
#if defined(ZTS) && defined(COMPILE_DL_RPI_WS281X)
	ZEND_TSRMLS_CACHE_UPDATE();
#endif

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(rpi_ws281x)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "rpi_ws281x support", "enabled");
	php_info_print_table_end();
}
/* }}} */

/* {{{ arginfo
 */
ZEND_BEGIN_ARG_INFO(arginfo_rpi_ws281x_test1, 0)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO(arginfo_rpi_ws281x_test2, 0)
	ZEND_ARG_INFO(0, str)
ZEND_END_ARG_INFO()
/* }}} */

/* {{{ rpi_ws281x_functions[]
 */
const zend_function_entry rpi_ws281x_functions[] = {
	PHP_FE(rpi_ws281x_test1,		arginfo_rpi_ws281x_test1)
	PHP_FE(rpi_ws281x_test2,		arginfo_rpi_ws281x_test2)
	PHP_FE_END
};
/* }}} */

/* {{{ rpi_ws281x_module_entry
 */
zend_module_entry rpi_ws281x_module_entry = {
	STANDARD_MODULE_HEADER,
	"rpi_ws281x",					/* Extension name */
	rpi_ws281x_functions,			/* zend_function_entry */
	NULL,							/* PHP_MINIT - Module initialization */
	NULL,							/* PHP_MSHUTDOWN - Module shutdown */
	PHP_RINIT(rpi_ws281x),			/* PHP_RINIT - Request initialization */
	NULL,							/* PHP_RSHUTDOWN - Request shutdown */
	PHP_MINFO(rpi_ws281x),			/* PHP_MINFO - Module info */
	PHP_RPI_WS281X_VERSION,		/* Version */
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_RPI_WS281X
# ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
# endif
ZEND_GET_MODULE(rpi_ws281x)
#endif
