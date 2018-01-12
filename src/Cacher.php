<?php

/**
 * Project sberp
 *
 * @file Cacher
 * @author Jaromír Polášek
 * @version 1.2 work
 * Encoding UTF-8
 */

namespace Loprym\Cascher;

use Nette\SmartObject,
    Nette\Database\Table\Selection,

    Loprym\Utils\Container,

    App\Model\Create,
    App\Model\Database,
    App\Lib\Table,
    App\Lib\Files\Space,
    App\Lib\Files\Temp,
    App\Lib\Statics\Cons;

/**
 * Description of Cacher
 *
 */
class Cacher extends Container {

    use SmartObject;

    const CACHE = 'cacher',
	    //TABLE = Cons::TABLE_CACHE,
	    SECTION = Cons::COLUMN_SECTION,
	    SPACE = Cons::COLUMN_SPACE,
	    VALUE = Cons:: COLUMN_VALUE;

    //private $database = NULL;
    private $cache = NULL;
    private $data = NULL;

    public function __construct() {
	parent::__construct(NULL);
	//$this->database = $database;
	Temp::CreateTemp(Cons::SYSTEM);
    }

    /**
     * Ověření existence spaceru
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset) {
	$result = parent::offsetExists($offset);
	if (!$result) {
	    $result = $this->isCache($offset);
	}
	//if (!$result) {
	//    $result = $this->isDB($offset);
	//}
	return $result;
    }

    /**
     * Vrací instanci spaceru
     * @param string $offset
     * @return App\Lib\Files\Space
     */
    public function offsetGet($offset) {
	if (parent::offsetExists($offset, $this->container)) {
	    return $this->container[$offset];
	} else {
	    $result = $this->getSection($offset);
	    if ($result === NULL) {
		return $this->GetTemp($offset);
	    } else {
		$this->container[$offset] = $result;
		return $this->container[$offset];
	    }
	}
    }

    /**
     * nastaví instanci spaceru
     * @param string $offset
     * @param type $value
     */
    public function offsetSet($offset, $value) {
	if ($value != NULL) {
	    $this->setSection($offset, $value);
	} else {
	    $this->offsetUnset($offset);
	}
    }

    /**
     * Zruší instanci spaceru a smaže složku
     * @param type $offset
     */
    public function offsetUnset($offset) {
	$this->unsetSection($offset);
	if (parent::offsetExists($offset)) {
	    parent::offsetUnset($offset);
	}
    }

    /**
     * Uloží složku a prostory
     * @param array ($dir, $space   )
     */
    public function setSection($dir, $section) {
	$this->container[$dir] = $section;
	$this->cache()->clean();

	//foreach ($section->spaces as $item) {
	//    $this->setDB($dir, $item);
	//}
    }

    /**
     * Smaže celou složku
     * @param string $offset
     */
    public function unsetSection(string $offset) {
	$section = $this->getSection($offset);
	foreach (array_keys($section->spaces) as $key){
	    unset($section[$key]);
	}
    }

    /**
     * Odstraní Space. pokud je poslední parametr TRUE, odstraní i složku
     * @param string $dir
     * @param string $space
     * @param bool $all
     */
    public function unsetSpace($dirName, $spaceName) {
	//$this->database()->data->where(self::SECTION, $dirName)->where(self::SPACE, $spaceName)->delete();
	$this->unsetCache($dirName);
	if (empty($this->container[$dirName]->spaces)) {
	    unset($this->container[$dirName]);
	    Temp::DeleteTemp($dirName);
	}
    }

    /*     * **********************PRIVATE*********************************** */

    private function getSection($offset) {
	$spaceNames = $this->getCache($offset);
	if ($spaceNames === NULL) {
	    //$spaceNames = $this->getDB($offset);
	    if ($spaceNames != NULL) {
		$this->setCache($offset, $spaceNames);
	    } else {
		return NULL;
	    }
	}
	return Create::Space($offset, $spaceNames);
    }

    /*     * ***********************CACHE************************************ */

    /**
     * Vrátí požadovaná data z cache
     * @param string $offset
     * @return $array
     */
    private function getCache(string $offset) {
	return $this->cache()->load($offset);
    }

    /**
     * zruší požadované data v cache
     * @param string $offset
     */
    private function unsetCache(string $offset) {
	$this->cache()->clean($offset);
    }

    /**
     * Ověří existenci dat v cache
     * @param string $offset
     * @return bool
     */
    private function isCache(string $offset) {
	return ($this->cache()->load($offset) != NULL);
    }

    /**
     * vloží požadovaná data do cache (z DB)
     * @param string $offset
     * @param Selection $rows
     */
    private function setCache(string $offset, array $rows) {
	//$result = array();
	//foreach ($rows as $value) {
	//$result[self::SPACE] = $value[self::SPACE];
	//}
	$this->cache()->save($offset, $rows);
    }

    /*     * *****************************DATABASE********************************** */

    /**
     * načte celou sekci s DB a vrací pole namespaců
     * @param string $offset
     * @return string
     */
    private function getDB(string $offset) {
	$spaceNames = $this->database()->data->where(self::SECTION, $offset);
	if ($spaceNames->count('*') > 0) {
	    $result = array();
	    foreach ($spaceNames as $value) {
		$result[$value[self::SPACE]] = $value[self::SPACE];
	    }
	    return $result;
	} else {
	    return NULL;
	}
    }

    /**
     * smaže celou složku v DB
     * @param string $offset
     */
    private function unsetDB(string $offset) {
	$this->database()->data->where(self::SECTION, $offset)->delete();
    }

    /**
     * ověří existenci Složky v DB
     * @param string $offset
     * @return bool
     */
    private function isDB($offset) {
	return $this->database()->data->where(self::SECTION, $offset)->count('*') > 0;
    }

    /**
     * Vytvoří složku s namespacem v DB
     * @param string $offset
     * @param string $spaceName
     */
    private function setDB(string $offset, string $spaceName) {
	$database = $this->database()->data->where(self::SECTION, $offset)->where(self::SPACE, $spaceName);
	if ($database->count('*') === 0) {
	    $this->database()->insert($this->CreateData($offset, $spaceName));
	}
    }

    /**
     * upraví data pro vložení do db
     * @param string $offset
     * @param string $spaceName
     * @return type
     */
    private function CreateData(string $offset, string $spaceName) {
	return array(
	    self::SECTION => $offset,
	    self::SPACE => $spaceName
	);
    }

    /**
     * V případě potřeby nahraje Cache
     */
    private function cache() {
	if ($this->cache === NULL) {
	    $this->cache = Create::Cache(Create::Space(Cons::SYSTEM, array(self::CACHE => self::CACHE)), self::CACHE, Cons::SYSTEM);
	}
	return $this->cache;
    }

    /**
     * v případě potřeby vytvoří spojení s DB
     * @return Table
     */
    private function database() {
	if ($this->data === NULL) {
	    $this->data = Create::Table(self::TABLE);
	}
	return $this->data->copy;
    }

    /**
     * Vrátí novou (prázdnou)instanci Spaceru
     * @param string $offset
     * @return \App\Lib\Files\Space
     */
    private function getTemp($offset) : Space {
	return Create::Space($offset);
    }
}
