
TMP_DIR=./build

.PHONY: clean

help:
	@echo "Use 'make (composer | cs-check | cs-fix | all-tests | clean)'"

composer:
	composer install

cs-check:
	./vendor/bin/php-cs-fixer fix --verbose --dry-run --diff

cs-fix:
	./vendor/bin/php-cs-fixer fix --verbose

all-tests:
	./vendor/bin/phpunit

clean:
	@echo "Cleans all temporary files"
	rm -fR $(TMP_DIR)

all: composer cs-check all-tests clean
