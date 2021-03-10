# I2C GPIO Extender
This package contains php classes to extend your gpio pins.

### Installation
```bin
$ composer require ikarus/sps-i2c-gpio-extender
```

### Usage

```php
<?php
use Ikarus\SPS\I2C\Extender_8GPIO_PCF_8574;
use TASoft\Bus\I2C;

$extender = new Extender_8GPIO_PCF_8574( new I2C(0x3F) );
$extender->digitalWrite( $extender::PIN_3, $extender::VAL_HIGH );
sleep(1);
$extender->digitalWrite( $extender::PIN_3, $extender::VAL_LOW );
$extender->cleanup();
```