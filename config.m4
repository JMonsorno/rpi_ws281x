PHP_ARG_ENABLE(rpi_ws281x, whether to enable rpi_ws281x support,
[  --enable-rpi_ws281x          Enable rpi_ws281x support], no)

if test "$PHP_RPI_WS281X" != "no"; then
  AC_DEFINE(HAVE_RPI_WS281X, 1, [ Have rpi_ws281x support ])
  PHP_NEW_EXTENSION(rpi_ws281x,
    rpi_ws281x.c \
    dma.c \
    mailbox.c \
    pcm.c \
    pwm.c \
    rpihw.c \
    ws2811.c,
    $ext_shared)
fi
