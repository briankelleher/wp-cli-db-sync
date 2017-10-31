## WHAT THIS IS

This wp-cli command will export your database (or read a .sql file), run a search replace on it, and then import it into your local wordpress installation.  This was created because the standard search-replace command does not allow for multiple commands on a single DB.  You can script it to have such, but I wanted an all in one tool with a config file.  This command reads a sr.yml file (or else specified) for its search/replace candidates.  An example of that file might look like this:

```
#sr.yml
search-replace:
  - search: 'term1'
    replace: 'term2'
  - search: '//dev.example.com'
    replace: '//localhost:8080'
```

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

## Example Usage

```
# With this in root directory
# --export tells this command to use a fresh export from the db it is acting on (cannot use with --dbfile)
# --require loads in the file to activate the command
# --path points to the valid wp install
# --dbfile points to the file that should be used to run the search/replace on.  no gunzip, only .sql. (cannot use with --export)
# --file points to the file with the search/replace config
wp sync-db --require=wp-cli-sync-db.php --export --path=www --file=sr.yml
# or
wp sync-db --require=wp-cli-sync-db.php --dbfile=in.sql --path=www --file=sr.yml
```