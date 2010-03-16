<?php
/**
 * Shortcore, a small url shortener service
 *   (c) 2009 Florian Anderiasch, <fa at art dash core dot org>
 *   BSD-licenced
 */


// START DEFAULT CONFIG, do no change, use the extra .config.php
$cfg = array(
    'dbfile' => '/home/www/shortcore/db/shortcore.db',
    'table' => 'shortcore',
    'DEBUG' => false,
    'home' => 'http://example.org/',
    'tpl_body' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Shortcore</title>
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />
</head>
<body>
<div>
%s
</div>
  <p style="float: right;">
    <a href="http://validator.w3.org/check?uri=referer"><img
        src="http://www.w3.org/Icons/valid-xhtml10"
        alt="Valid XHTML 1.0 Strict" height="31" width="88" style="border:0;" /></a>
  </p>
</body>
</html>'
);
// END DEFAULT CONFIG

require './shortcore.config.php';

$sc = new Shortcore($cfg);

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
        $this->handle();
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


    /**
     * Grabs a result from the database
     * @param string $id the desired id
     * @return mixed
     */
    function getResult($id) {
        $sql_select = sprintf('SELECT * FROM %s WHERE id=:id', $this->cfg['table']);
        $q = $this->exec($sql_select, array(':id' => $id));

        if ($q === false) {
            $result = false;
        } else {
            $result = $q->fetch(PDO::FETCH_ASSOC);
        }
        return $result;
    }

    /*
     */
    function addTags($shortcore_id, $tags) {
		foreach($tags AS $tag)
		{
			$tag = trim($tag);
			$sql = "INSERT INTO tags (shortcore_id, value) VALUES (:shortcore_id, :value)";
			//print $sql;
			$q = $this->exec($sql, array(':shortcore_id' => $shortcore_id, ':value' => $tag));
		}
        return true;
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
     * Redirects to the shortened url
     * @param string $id what to look up
     */
    function redirect($id) {
        $preview = false;
        if (substr($id, -1) == '_') {
            $preview = true;
            $id = substr($id, 0, -1);
        }
        $result = $this->getResult($id);
        if (false === $result) {
            $this->page();
        } else {
            $counter = intval($result['counter']) + 1;
            $sql_update  = sprintf('UPDATE %s SET counter="%s" WHERE id=:id;', $this->cfg['table'], $counter);
            $p = $this->exec($sql_update, array(':id' => $id));
			
            $sql_insert = 'INSERT INTO clicks (shortcore_id, ip, date, user_agent, referer) VALUES (:id, :ip, :time, :user_agent, :referer)';
            $insert = $this->exec(
				$sql_insert,
				array(
					':id'				=> $id,
					':ip'				=> $_SERVER['REMOTE_ADDR'],
					':time'				=> time(),
					':user_agent'		=> $_SERVER["HTTP_USER_AGENT"],
					':referer'			=> $_SERVER["HTTP_REFERER"]
				)
			);

            if ($preview) {
                $link1 = sprintf('<a href="%s_%s">%s_%s</a>', $this->cfg['home'], $id, $this->cfg['home'], $id);
                $link2 = sprintf('<a href="%s">%s</a>', $result['url'], $result['url']);
                $text = sprintf(
	                'Le lien <em>%s</em>, est une url r&eacute;duite redirigeant vers <strong>%s</strong>,<br />'.
	                'Url r&eacute;duite le <em>%s</em>, cliqu&eacute;e %s fois.<br />'.
	                'Vous allez &ecirc;tre redirig&eacute; automatiquement dans 4 secondes...'.
	                '<meta http-equiv="refresh" content="4;url=%s" />', 
	                $link1,
	                $link2,
	                date('d.m.Y H:i', $result['created']),
	                $counter,
	                $result['url']
                );
                echo sprintf($this->cfg['tpl_body'], stripslashes($result['title']), stripslashes($result['title']), $text);
                exit;
            } else {
                $this->page($result['url']);
            }
        }
    }

    /**
     * Adds a new shortened url
     * @param mixed $id the desired id
     * @param string $url the target url
     * @param mixed $title an optional title
     */
    function add($id = null, $url, $title = null) {
        $time = time();
        if (is_null($id)) {
            $id = $this->randomId();
			$id = $this->clean($id);
            $result = $this->getResult($id);
            while (false !== $result) {
                $id = $this->randomId();
                $result = $this->getResult($id);
            }
        } else {
			$id = $this->clean($id);
            $result = $this->getResult($id);

            while (false !== $result) {
                $last = substr($id, -1);
                if (intval($last) > 0 && intval($last) < 9) {
                    $id = substr( $id, 0, -1) . ($last+1);
                } else {
                    $id .= '2';
                }
                $result = $this->getResult($id);
            }

        }
        if (is_null($title)) {
            $title = 'untitled';
        }
        $sql_insert = sprintf('INSERT INTO %s VALUES(:id, :url, :title, 0, "%s");', $this->cfg['table'], $time);
        $this->exec($sql_insert, array(':id' => $id, ':url' => $url, ':title' => $title));
        $this->page($cfg['home'].'_'.$id.'_');
    }

    /**
     * Executes a redirect
     * @param mixed $arg where to go
     */
    function page($arg = null) {
        if (is_null($arg)) {
            $arg = $this->cfg['home'];
        }
        $this->_e('[page] '.$arg);
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: '.$arg);
        exit;
    }

    /**
     * Handles front controller stuff
     */
    function handle() {
        $_id    = null;
        $_url   = null;
        $_title = null;
        $_help  = null;

        if(isset($_GET['help'])) {
            $_help = true;
        }

        if (isset($_GET['url']) && substr($_GET['url'],0,4) == 'http' && strlen($_GET['url']) > 10) {
            $_url = $_GET['url'];
        }
        if (isset($_GET['title']) && strlen(strip_tags($_GET['title'])) > 0 ) {
            $_title = strip_tags($_GET['title']);
        }
        if (isset($_GET['id'])) {
            $pat = '([a-z0-9-]{2,})i';
            if (preg_match($pat, $_GET['id'])) {
                $_id = $_GET['id'];
            }
        }
        if (isset($_POST['action']) && ($_POST['action'] == "addTags")) {
            $this->addTags($_POST["shortcore_id"], split(",", $_POST["tagslist"]));
            exit;
        }
        if (isset($_POST['action']) && ($_POST['action'] == "removeTag")) {
            $this->removeTag($_POST["shortcore_id"], $_POST["tag"]);
            exit;
        }

        // writing
        if (!is_null($_url)) {
            $this->_e('adding:'.$_id);
            $this->add($_id, $_url, $_title);
        // reading
        } else {
            // this is "/_<id>"
            if (!is_null($_id)) {
                $this->_e('redir:'.$_id);
                $this->redirect($_id);
            }
        }
        if (!is_null($_help)) {
            
            $bookmarklet = <<<BML
javascript:foo=prompt('id?');location.href='%sshortcore.php?url='+encodeURIComponent(location.href)+'&amp;title='+encodeURIComponent(document.title)+'&amp;id='+foo
BML;
            $bookmarklet = sprintf($bookmarklet, $this->cfg['home']);
            $link = sprintf('<a href="%s">shorten</a>', $bookmarklet);

            $text = sprintf('<p>Powered by Shortcore v. %s</p>Bookmarklet: %s', $this->version, $link);

            echo sprintf($this->cfg['tpl_body'],$text);
            exit;
        }
        $this->page();
    }

    /**
     * Generates a random id
     * @param int $len the desired length
     * @return string
     */
    function randomId($len = 4) {
        $choice = array();
        $a = ord('a');
        $z = ord('z');
        $A = ord('A');
        $Z = ord('Z');

        // ignore the hard to read chars
        $banned = array(ord('l'), ord('I'), ord('O'));

        for($i=$a;$i<=$z;$i++) {
            if (!in_array($i, $banned)) {
                $choice[] = chr($i);
            }
        }
        for($i=$A;$i<=$Z;$i++) {
            if (!in_array($i, $banned)) {
                $choice[] = chr($i);
            }
        }
        for($i=0;$i<=9;$i++) {
            $choice[] = $i;
        }
        $out = '';
        for ($i=0;$i<$len;$i++) {
            $rand = rand(0, count($choice)-1);
            $out .= $choice[$rand];
        }
        return $out;
    }

    /**
     */
    function clean($str) {
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $str);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", '-', $clean);

		return $clean;
    }

    /**
     * Debug wrapper to error_log()
     * @param mixed $arg what to show
     */
    function _e($arg) {
        error_log('(shortcore) '.$arg);
    }
}
?>
