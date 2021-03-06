/* rpi_ws281x extension for PHP */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "zend_types.h"
#include "ext/standard/info.h"
#include "php_rpi_ws281x.h"
#include "ws2811.h"

#define TARGET_FREQ             WS2811_TARGET_FREQ
#define GPIO_PIN                18
#define DMA                     5
//#define STRIP_TYPE            WS2811_STRIP_RGB		// WS2812/SK6812RGB integrated chip+leds
#define STRIP_TYPE              WS2811_STRIP_GBR		// WS2812/SK6812RGB integrated chip+leds
//#define STRIP_TYPE            SK6812_STRIP_RGBW		// SK6812RGBW (NOT SK6812RGB)

#define LED_COUNT               30

ws2811_t ledstring =
{
    .freq = TARGET_FREQ,
    .dmanum = DMA,
    .channel =
    {
        [0] =
        {
            .gpionum = GPIO_PIN,
            .count = LED_COUNT,
            .invert = 0,
            .brightness = 255,
            .strip_type = STRIP_TYPE,
        },
        [1] =
        {
            .gpionum = 0,
            .count = 0,
            .invert = 0,
            .brightness = 0,
        },
    },
};

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

/* {{{ object rpi_ws281x_test3( [ long $var ] )
 */
PHP_FUNCTION(rpi_ws281x_test3)
{
  zend_long led_no = 0;
  zend_long led_color = 0;
  ws2811_return_t ret;

  ZEND_PARSE_PARAMETERS_START(2, 2)
    Z_PARAM_LONG(led_no)
    Z_PARAM_LONG(led_color)
  ZEND_PARSE_PARAMETERS_END();


  if ((ret = ws2811_init(&ledstring)) != WS2811_SUCCESS)
  {
    fprintf(stderr, "ws2811_init failed: %s\n", ws2811_get_return_t_str(ret));
  } else {

    ledstring.channel[0].leds[led_no] = led_color;

     if ((ret = ws2811_render(&ledstring)) != WS2811_SUCCESS)
    {
      fprintf(stderr, "ws2811_render failed: %s\n", ws2811_get_return_t_str(ret));
    }
    //ws2811_fini(&ledstring);
  }

  //convert_to_object(ledstring);
}
/* }}}*/

/* {{{ void rpi_ws281x( [ long $gpio_pin, long $count, long *$var ] )
 */
PHP_FUNCTION(rpi_ws281x_render)
{
  long gpio_pin;
  long count;
  zval *leds;
  zval *led;
  HashPosition position;
  ws2811_return_t ret;
  long i = 0;

  ZEND_PARSE_PARAMETERS_START(3, 3)
    Z_PARAM_LONG(gpio_pin)
    Z_PARAM_LONG(count)
    Z_PARAM_ARRAY(leds)
  ZEND_PARSE_PARAMETERS_END();

  ledstring.channel[0].count = count;
  ledstring.channel[0].gpionum = gpio_pin;

  if ((ret = ws2811_init(&ledstring)) != WS2811_SUCCESS)
  {
    fprintf(stderr, "ws2811_init failed: %s\n", ws2811_get_return_t_str(ret));
    return;
  }

  for (zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(leds), &position);
       (led = zend_hash_get_current_data_ex(Z_ARRVAL_P(leds), &position));
       zend_hash_move_forward_ex(Z_ARRVAL_P(leds), &position)) {

    if (i >= count) {
      zend_throw_exception(NULL, "LED array is longer than count.", 0);
    }

    if(Z_TYPE_P(led) == IS_LONG) {
      ledstring.channel[0].leds[i] = Z_LVAL_P(led);
    } else {
      zend_throw_exception(NULL, "LED array can only contain integers.", 0);
    }
    ++i;
  }

  if ((ret = ws2811_render(&ledstring)) != WS2811_SUCCESS)
  {
    fprintf(stderr, "ws2811_render failed: %s\n", ws2811_get_return_t_str(ret));
  }

  ws2811_fini(&ledstring);
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

ZEND_BEGIN_ARG_INFO(arginfo_rpi_ws281x_test3, 0)
	ZEND_ARG_INFO(0, long)
ZEND_END_ARG_INFO()
/* }}} */

/* {{{ rpi_ws281x_functions[]
 */
const zend_function_entry rpi_ws281x_functions[] = {
	PHP_FE(rpi_ws281x_test1,		arginfo_rpi_ws281x_test1)
	PHP_FE(rpi_ws281x_test2,		arginfo_rpi_ws281x_test2)
	PHP_FE(rpi_ws281x_test3,		arginfo_rpi_ws281x_test3)
	PHP_FE(rpi_ws281x_render,		NULL)
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
