DOCKER_PHP=kiqstyle-eloquent-versionable

### COMPOSER ###
composer-install:
	docker compose exec $(DOCKER_PHP) composer install

composer-update:
	docker compose exec $(DOCKER_PHP) composer update

composer-autoload:
	docker compose exec $(DOCKER_PHP) composer dumpautoload

### TESTS ###
test:
	docker compose exec $(DOCKER_PHP) ./vendor/bin/phpunit $(param) --no-coverage

### UTILS ###
php-extensions:
	docker compose exec $(DOCKER_PHP) php -m
	
up:
	docker compose up -d
