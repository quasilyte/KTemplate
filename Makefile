.PHONY: test-php
test-php:
	php ./vendor/bin/phpunit --bootstrap=./vendor/autoload.php tests

.PHONY: test-kphp
test-kphp:
	./vendor/bin/ktest phpunit tests
