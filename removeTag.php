<?php
require './shortcore.config.php';
$sc = new Shortcore($cfg);

$sc->removeTag($_POST["shortcore_id"], $_POST["tag"]);

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
    function removeTag($shortcore_id, $tag) {
		$tag = trim($tag);
		$sql = "DELETE FROM tags WHERE shortcore_id=:shortcore_id AND value=:tag";
		//print $sql;
		$q = $this->exec($sql, array(':shortcore_id' => $shortcore_id, ':tag' => $tag));
      return true;
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
