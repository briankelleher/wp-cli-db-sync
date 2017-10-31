<?php

require 'vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

// Unfortunately necessary for enourmous DBs, change at your leisure.
ini_set('memory_limit','2048M');

class WP_CLI_Sync_DB {

    public $dbfile = 'in.sql';
    public $dumpfile = 'dump.sql';

    public function __invoke( $args, $assoc_args ) {
        
        $value = $this->ingest_yml_file( $assoc_args );

        $dbfile_set = isset( $assoc_args["dbfile"] ) && file_exists( $assoc_args["dbfile"] );
        $export_set = isset( $assoc_args["export"] ) && $assoc_args["export"];

        // Allows you to set the name of the file that should be ingested
        if ( $dbfile_set ) {
            $this->dbfile = $assoc_args["dbfile"];
        }

        // Allows you to specify an --export parameter that will write a file from the db being acted on
        if ( $export_set ) {
            if ( !$dbfile_set ) {
                $this->write_sql_dump( $this->dbfile );
            } else {
                WP_CLI::error("Cannot supply the --export parameter and the --dbfile parameter.  --export implies you are exporting a file from the current database, where --dbfile implies you are ingesting a database file.");
            }
        }

        $this->search_replace( $value, $this->dumpfile );

        $this->import_database( $this->dumpfile );
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


    // Writes the dump file
    private function write_sql_dump( $dumpfile ) {
        try {
            WP_CLI::run_command( array( 'db', 'export', $dumpfile ), array( 'add-drop-table' => true ) );
            WP_CLI::success("Created a dumpfile at " . $dumpfile );
        } catch ( \Exception $e ) {
            WP_CLI::error('mysqldump-php error: ' . $e->getMessage() );
        }
    }


    // Loops through the search/replace options in the .yml file, replaces as necessary
    private function search_replace( $value, $dumpfile ) {
        foreach ( $value["search-replace"] as $v ) {
            $new_dump_sql = str_replace($v["search"], $v["replace"], file_get_contents($this->dbfile));
            $pattern = '/(s:)([0-9]*)(:\\\\")([^"]*'.str_replace('/', '\/', preg_quote($v["replace"])).'((?!\\\\\\").)*)(\\\\")/';
            $new_dump_sql = preg_replace_callback($pattern, function ($m){
                return($m[1].mb_strlen($m[4], 'utf-8').$m[3].$m[4].$m[6]);
            }, $new_dump_sql);
            file_put_contents( $dumpfile, $new_dump_sql );
            WP_CLI::success( "Replaced " . $v["search"] . " with " . $v["replace"] );
        }
        WP_CLI::success( "Search/Replaced file is located at " . $dumpfile );
    }

    
    // Imports the db into the current installation
    private function import_database( $dumpfile ) {
        WP_CLI::run_command( array( 'db', 'import', $dumpfile ) );
    }

}
WP_CLI::add_command( 'sync-db', 'WP_CLI_Sync_DB' );