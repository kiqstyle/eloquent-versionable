DOCKER_PHP=kiqstyle-eloquent-versionable

### COMPOSER ###
composer-install:
	docker compose exec $(DOCKER_PHP) composer install

composer-update:
	docker compose exec $(DOCKER_PHP) composer update

composer-update-lock:
	docker compose exec $(DOCKER_PHP) composer update --lock

composer-autoload:
	docker compose exec $(DOCKER_PHP) composer dumpautoload

composer-require:
	docker compose exec $(DOCKER_PHP) composer require $(package)

composer-remove:
	docker compose exec $(DOCKER_PHP) composer remove $(package)

composer-why:
	docker compose exec $(DOCKER_PHP) composer why $(package)

### TESTS ###
test-with-coverage:
	docker compose exec $(DOCKER_PHP) php -dpcov.enabled=1 -dpcov.directory=. -dpcov.exclude="~vendor~" ./vendor/bin/pest --coverage --min=92

test-init:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/pest --init

### INSIGHTS COMMANDS ###
insights:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpinsights --config-path=config/phpinsights.php --no-interaction --ansi

insights-analyse:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpinsights analyse $(path)

### UTILS ###
php-extensions:
	docker compose exec $(DOCKER_PHP) php -m

up:
	docker compose up -d

build:
	docker compose build $(DOCKER_PHP)
	make up

### PHPMD COMMANDS ###
phpmd:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode


