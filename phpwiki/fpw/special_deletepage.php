<?
include_once ( "special_makelog.php" ) ;

function deletepage () {
    global $THESCRIPT , $target , $user , $iamsure ;
    global $vpage , $wikiSQLServer ;
    $target = stripslashes ( $target ) ;
    $vpage = new WikiPage ;
    $vpage->title = $title ;
    $vpage->makeSecureTitle () ;
    $ti = $vpage->secureTitle ;

    global $wikiDeleteTitle , $wikiDeleteDenied , $wikiDeleteSuccess , $wikiDeleteMsg1 , $wikiDeleteMsg2 , $wikiDeleteAsk ;
    $vpage->special ( str_replace ( "$1" , $target , $wikiDeleteTitle ) ) ;
    $vpage->makeSecureTitle () ;
    if ( !in_array ( "is_sysop" , $user->rights ) ) return $wikiDeleteDenied ;
    if ( $iamsure == "yes" ) {
        $ret = "<h2>".str_replace("$1",$target,$wikiDeleteSuccess)."</h2>" ;
        $connection = getDBconnection () ;
        $sql = "DELETE FROM cur WHERE cur_title=\"$target\"" ;
        $result = mysql_query ( $sql , $connection ) ;

        # Appending log page "log:Page Deletions"
        $now = date ( "Y-m-d H:i:s" , time () ) ;
        $logTarget = $vpage->getNiceTitle ( $target ) ;
        $logText = str_replace("$1",$now,str_replace("$2",$user->name,str_replace("$3",$logTarget,$wikiDeleteMsg1))) ;
        makeLog ( "log:Page Deletions" , $logText , str_replace("$1",$logTarget,$wikiDeleteMsg2)) ;

        # links to $target are moved from table linked to table unlinked
        $sql = "INSERT INTO unlinked ( unlinked_from, unlinked_to )
                SELECT linked_from, linked_to
                FROM linked
                WHERE linked_to = \"$target\"" ;
        mysql_query ( $sql , $connection ) ;
        $sql = "DELETE FROM linked WHERE linked_to = \"$target\"" ;
        mysql_query ( $sql , $connection ) ;
        # and links from $target are removed
        $sql = "DELETE FROM unlinked WHERE unlinked_from = \"$target\"" ;
        mysql_query ( $sql , $connection ) ;
        $sql = "DELETE FROM linked WHERE linked_from = \"$target\"" ;
        mysql_query ( $sql , $connection ) ;

    } else {
        $ret = "<font size=\"+2\">".str_replace(array("$1","$2"),array($target,wikiLink("special:deletepage&target=$target")),$wikiDeleteAsk)."</font>" ;
        }
    return "<nowiki>$ret</nowiki>" ;
    }

?>
