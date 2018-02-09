# cqrs-es-framework-laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

This is the Laravel Adapter for SmoothPHP CQRS Event Sourcing package, It contains everything you need to get started event souring in Laravel. 
## Install

Via Composer

``` bash
$ composer require smoothphp/cqrs-es-framework-laravel
```
Add to `config/app.php`
``` php
SmoothPhp\LaravelAdapter\ServiceProvider::class,
```

Run Command
```bash
$ php artisan vendor:publish
```

## Supervisor
If you wish to run the smooth queue separately from other queue jobs you can config it to run on a different queue. 
If left it will run on default queue with rest of laravel.

see `config/cqrses.php`
```php
'queue_name' => 'default',
```
Change to smooth or other name. Then use the following supervisor config

```bash
[program:smoothphp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:listen --queue=smooth --sleep=1
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stderr_logfile=/var/log/supervisor.log
stdout_logfile=/var/log/supervisor.log
```


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email simon@pixelatedcrow.com instead of using the issue tracker.

## Credits

- [Simon Bennett][link-author]
- [Jordan Crocker](https://github.com/jrdnrc)
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support
![https://i.imgur.com/iqFWqYD.png](https://i.imgur.com/iqFWqYD.png)

SmoothPHP is a [Pixelated Crow](https://pixelatedcrow.com) Product. 

For commercial support please contact smoothphp@pixelatedcrow.com

[ico-version]: https://img.shields.io/packagist/v/smoothphp/cqrs-es-framework-laravel.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/smoothphp/cqrs-es-framework-laravel/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/smoothphp/cqrs-es-framework-laravel.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/smoothphp/cqrs-es-framework-laravel.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/smoothphp/cqrs-es-framework-laravel.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/smoothphp/cqrs-es-framework-laravel
[link-travis]: https://travis-ci.org/smoothphp/cqrs-es-framework-laravel
[link-scrutinizer]: https://scrutinizer-ci.com/g/smoothphp/cqrs-es-framework-laravel/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/smoothphp/cqrs-es-framework-laravel
[link-downloads]: https://packagist.org/packages/smoothphp/cqrs-es-framework-laravel
[link-author]: https://github.com/mrsimonbennett
[link-contributors]: ../../contributors
