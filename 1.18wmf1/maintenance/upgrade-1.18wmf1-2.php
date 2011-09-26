<?php

require_once( dirname( __FILE__ ) . '/Maintenance.php' );
require( dirname( __FILE__ ) . '/../../wmf-config/InitialiseSettings.php' );

class SchemaMigration extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "Run Schema Migrations for branch against all wikis";
        $this->addOption( 'secondary', 'Run on secondary / non-prod slaves', false, false );
    }

    function doAllSchemaChanges() {
        global $wgLBFactoryConf, $wgConf;

        if ( $this->getOption( 'secondary' ) ) { 
            require( dirname( __FILE__ ) . '/../../wmf-config/db-secondary.php' );
        }

        $sectionLoads = $wgLBFactoryConf['sectionLoads'];
        $sectionsByDB = $wgLBFactoryConf['sectionsByDB'];

        $rootPass = trim( wfShellExec( '/home/wikipedia/bin/mysql_root_pass' ) );

        // Compile wiki lists
        $wikisBySection = array();
        foreach ( $wgConf->getLocalDatabases() as $wiki ) {
            if ( isset( $sectionsByDB[$wiki] ) ) {
                $wikisBySection[$sectionsByDB[$wiki]][] = $wiki;
            } else {
                $wikisBySection['DEFAULT'][] = $wiki;
            }
        }

        // Do the upgrades
        foreach ( $sectionLoads as $section => $loads ) {
            $master = true;
            foreach ( $loads as $server => $load ) {
                if ( $master ) {
                    echo "Skipping $section master $server\n";
                    $master = false;
                    continue;
                }

                $db = new DatabaseMysql(
                    $server,
                    'root',
                    $rootPass,
                    false, /* dbName */
                    0, /* flags, no transactions */
                    '' /* prefix */
                );

                foreach ( $wikisBySection[$section] as $wiki ) {
                    $db->selectDB( $wiki );
                    $this->upgradeWiki( $db );
                    if ( !$this->getOption( 'secondary' ) ) { 
                        while ( $db->getLag() > 10 ) {
                            echo "Waiting for $server to catch up to master.\n";
                            sleep( 60 );
                        }
                    }
                }
            }
        }

        echo "All done (except masters).\n";
    }

    function upgradeWiki( $db ) {
        global $wgConf;
        $wiki = $db->getDBname();
        $server = $db->getServer();

        $upgradeLogRow = $db->selectRow( 'updatelog',
            'ul_key',
            array( 'ul_key' => '1.18wmf1-lt' ),
            __FUNCTION__ );
        if ( $upgradeLogRow ) {
            echo $db->getDBname() . ": already done\n";
            return;
        }

        echo "$server $wiki 1.18wmf1-lt";

        if ( $wgConf->get( 'wmgUseLiquidThreads', $wiki ) && $wiki != "test2wiki") {
            echo " liquidthreads";
            $this->sourceUpgradeFile( $db, dirname( __FILE__ ) .'/ums_conversation.sql' );
        } else { 
            echo " no-op";
        }

        $db->insert( 'updatelog', 
            array( 'ul_key' => '1.18wmf1-lt' ),
            __FUNCTION__ );
        echo " ok\n";
    }

    function sourceUpgradeFile( $db, $file ) {
        if ( !file_exists( $file ) ) {
            echo "File missing: $file\n";
            exit( 1 );
        }
        $db->sourceFile( $file );
    }
    
    function execute() { 
        $this->doAllSchemaChanges();
    }
}

$maintClass = "SchemaMigration";
require_once( RUN_MAINTENANCE_IF_MAIN );

