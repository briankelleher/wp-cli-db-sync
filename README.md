## Getting Started

```
composer install
docker-compose up
```

You will need to swap the DB_HOST line in www/wp-config.php after the docker container starts with something of the sorts:

```
if ( defined('WP_CLI') && WP_CLI ) {
	define('DB_HOST', '127.0.0.1');
} else {
	define('DB_HOST', 'mysql');
}
```