
.PHONY : test

test:
	php vendor/bin/phpcs --report=full --report-file=./report.txt -p src
	php vendor/bin/phpstan analyse -c phpstan.neon
