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

### TESTS ###
test:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpunit $(param) --no-coverage

### INSIGHTS COMMANDS ###
insights:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpinsights

insights-analyse:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpinsights analyse $(path)

### UTILS ###
php-extensions:
	docker compose exec $(DOCKER_PHP) php -m

up:
	docker compose up -d

### PHPMD COMMANDS ###
phpmd:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode


