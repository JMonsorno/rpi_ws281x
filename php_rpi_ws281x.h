/* rpi_ws281x extension for PHP */

#ifndef PHP_RPI_WS281X_H
# define PHP_RPI_WS281X_H

extern zend_module_entry rpi_ws281x_module_entry;
# define phpext_rpi_ws281x_ptr &rpi_ws281x_module_entry

# define PHP_RPI_WS281X_VERSION "0.1.0"

# if defined(ZTS) && defined(COMPILE_DL_RPI_WS281X)
ZEND_TSRMLS_CACHE_EXTERN()
# endif

#endif	/* PHP_RPI_WS281X_H */
