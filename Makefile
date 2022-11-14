.PHONY: test-php
test-php:
	php ./vendor/bin/phpunit --bootstrap=./vendor/autoload.php tests

.PHONY: test-kphp
test-kphp:
	./vendor/bin/ktest phpunit tests

gen-opcodes: _script/gen_opcodes/main.go
	go run ./_script/gen_opcodes/main.go > src/Internal/Op.php
gen-exprs: _script/gen_exprs/main.go
	go run ./_script/gen_exprs/main.go > src/Internal/Compile/ExprKind.php
gen-tokens: _script/gen_tokens/main.go
	go run ./_script/gen_tokens/main.go > src/Internal/Compile/TokenKind.php
