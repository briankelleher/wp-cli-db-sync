<?php

require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use Ifsnop\Mysqldump as IMysqldump;

ini_set('memory_limit','2048M');

class WP_CLI_Sync_DB {

    public function __invoke( $args, $assoc_args ) {
        
        $value = $this->ingest_yml_file( $assoc_args );

        $dumpfile = './dump.sql';
        $this->write_sql_dump( $dumpfile );

        $this->search_replace( $value, $dumpfile );
    }

    // Ingests sr.yml file in current directory, or from the --file flag.
    // Prints an error if no valid one supplied.
    private function ingest_yml_file( $assoc_args ) {
        $value = '';

        if ( isset( $assoc_args["file"] ) && file_exists( $assoc_args["file"] ) ) {
            $file_info = pathinfo( $assoc_args["file"] );
            if ( !$file_info["extension"] == "yml" ) {
                WP_CLI::error("File supplied is not a Yaml file.");
            } else {
                $value = Yaml::parse( file_get_contents( $assoc_args["file"] ) );
            }            
        } else if ( file_exists("./sr.yml") ) {
            $value = Yaml::parse( file_get_contents( './sr.yml' ) );
        } else {
            WP_CLI::error("No valid Yaml file found.  Please supply a sr.yml file or the path to a valid Yaml file with the --file flag.");
        }

        return $value;
    }


    private function write_sql_dump( $dumpfile ) {
        try {
            $dump = new IMysqldump\Mysqldump('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
            $dump->start( $dumpfile );
        } catch ( \Exception $e ) {
            WP_CLI::error('mysqldump-php error: ' . $e->getMessage() );
        }
    }


    private function search_replace( $value, $dumpfile ) {
        foreach ( $value["search-replace"] as $v ) {
            $new_dump_sql = str_replace($v["search"], $v["replace"], file_get_contents($dumpfile));
            $pattern = '/(s:)([0-9]*)(:\\\\")([^"]*'.str_replace('/', '\/', preg_quote($v["replace"])).'((?!\\\\\\").)*)(\\\\")/';
            $new_dump_sql = preg_replace_callback($pattern, function ($m){
                return($m[1].mb_strlen($m[4], 'utf-8').$m[3].$m[4].$m[6]);
            }, $new_dump_sql);
            file_put_contents( $dumpfile, $new_dump_sql );
            WP_CLI::success( "Replaced " . $v["search"] . " with " . $v["replace"] );
        }
        
    }

}
WP_CLI::add_command( 'sync-db', 'WP_CLI_Sync_DB' );