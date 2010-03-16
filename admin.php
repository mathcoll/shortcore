<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Shortcore list</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" href="./css/admin.css" media="screen" />
	<script src="./js/sortable.js"></script>
	<script src="./js/jquery-1.3.2.js"></script>
	<script src="./js/ui.core.js"></script>
	<script src="./js/admin.js"></script>
</head>
<body>
<div class="head">
	<h2>shortcore</h2>
	<span id="baseline">a small url shortener service</span>
	<div id="tags">
		<a href="#" onClick="$('#tags').hide('slow');return false;" title="Fermer">x</a>
		<input type="hidden" name="tagsId" id="tagsId" />
		<h2>Ajouter des tags</h2>
		<label for="tagslist">Tags : </label><input type="text" name="tagslist" id="tagslist" />
		<input type="button" id="addTags" value="Ajouter" onClick="javascript:addTags();return false;" />
	</div>
</div>

<div id="content">
<?php
$tag = isset($_GET["tag"]) ? $_GET["tag"] : false;
if ( isset($tag) ) {
	print "Filtre sur les tags : ";
}
foreach(split(",", $tag) as $t) {
	if( isset($t) && $t!="" ) {
		$tags = str_replace(",".$t, "", $tag);
		print $t."<a href='admin.php?tag=".$tags."' class=\"removeTag\" title=\"Supprimer ".strToLower($t)."\">&otimes;</a> ";
	}
}
if ( isset($tag) ) {
	print "<br /><a href='admin.php'>Tout afficher</a> ";
}
?>
	<table class="sortable" width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr class="header">
			<td class="counter">Clicks</td>
			<td>Lien</td>
			<td>Source</td>
			<td>Titre</td>
			<td>Date</td>
			<td class="tags">Tags</td>
		</tr>
<?php
require './shortcore.config.php';
$sc = new Shortcore($cfg);

foreach($sc->getItems($tag) as $item)
{
	$id = $item[1];
	$url = $item[2];
	$redudced_url = substr($url, 7, 20)."...";
	$title = stripcslashes($item[3]);
	$counter = $item[4];
	$created = date("d/m/Y H\hi:s", $item[5]);
	$tags = "";
	//print_r($item);
	foreach($item[6] AS $t)
	{
		$tags .= "<a href=\"?tag=".$tag.",".strToLower($t)."\">".strToLower($t)."</a><a class=\"removeTag\" href=\"#\" onclick=\"javascript:removeTag('$id', '".strToLower($t)."');\" title=\"Supprimer ".strToLower($t)."\">&otimes;</a>, ";
	}
	$tags = substr($tags, 0, -2);
	//$tags = join(", ", $item[5]);
	print "\t\t<tr>\n";
	print sprintf('			<td>%d</td><td><a href="./_%s_">%s</a></td><td title="%s">%s</td><td class="title">%s</td><td>%s</td><td id="%s_tagslist"><a class="showTags" href="#" onclick="showTags(\'%s\');return false;" title="Ajouter des tags">+</a> %s</td>', $counter, $id, $id, $url, $redudced_url, $title, $created, $id, $id, $tags);
	print "\n\t\t</tr>\n";
}
?>
</table>
</div>
</body>
</html>
<?php

/**
 * Everything Shortcore
 * @package shortcore
 */
class Shortcore {
    private $cfg;
    private $db;
    private $DEBUG;
    private $version;

    /**
     * Constructor
     * @param array $cfg config values
     */
    function __construct($cfg) {
        $this->version = '0.2';
        $this->cfg     = $cfg;
        $this->DEBUG   = $cfg['DEBUG'];
        $this->db      = new PDO('sqlite://'.$this->cfg['dbfile']);
        return $this;
    }

    function exec($sql, $array) {
        try {
            $q = $this->db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $q->execute($array);
        } catch (Exception $e) {
            $q = false;
        }
        return $q;
    }


    /*
     */
    function getItems($tag) {
        if ( $tag ) {
         $list = split(",", $tag);
			$and = " AND (";
         foreach($list as $tag) {
         	if( isset($tag) && $tag!="" )
         		$and .= "(LOWER(t.value)='".addslashes($tag)."') OR ";
         }
			$and = substr($and, 0, -4);
			$and .= ")";
			$sql_select = sprintf('SELECT DISTINCT(s.id), s.*  FROM %s s, tags t WHERE (t.shortcore_id=s.id)'.$and.' ORDER BY s.created DESC', $this->cfg['table']);
        	$q = $this->exec($sql_select, array());
		} else {
			$sql_select = sprintf('SELECT DISTINCT(s.id), s.* FROM %s s ORDER BY s.created DESC', $this->cfg['table']);
        	$q = $this->exec($sql_select, array());
		}
		//print $sql_select;
        
        $result = Array();
        if ($q === false) {
           $result = false;
        } else {
	        while ($row = $q->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
	        {
				/* get all tags */
				$sql_tags = sprintf('SELECT * FROM tags WHERE shortcore_id="%s"', $row[1]);
				$q_tags = $this->exec($sql_tags, array());
				$row[6] = array();
				while ($row_tags = $q_tags->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
	        	{
					array_push($row[6], $row_tags[1]);
				}
				array_push($result, $row);
	        }
        }
        return $result;
    }
	
    function getVersion() {
        return $this->version;;
    }

    /**
     * Debug wrapper to error_log()
     * @param mixed $arg what to show
     */
    function _e($arg) {
        error_log('(sho) '.$arg);
    }
}
?>
