<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Block extends Model\Document\Tag
{
    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @var array
     */
    public $indices = [];

    /**
     * Current step of the block while iteration
     *
     * @var int
     */
    public $current = 0;

    /**
     * @var string[]
     */
    public $suffixes = [];

    /**
     * @see TagInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return "block";
    }

    /**
     * @see TagInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->indices;
    }

    /**
     * @see TagInterface::admin
     */
    public function admin()
    {
        // nothing to do
    }

    /**
     * @see TagInterface::frontend
     */
    public function frontend()
    {
        // nothing to do
        return null;
    }

    /**
     * @see TagInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->indices = \Pimcore\Tool\Serialize::unserialize($data);

        return $this;
    }

    /**
     * @see TagInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->indices = $data;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDefault()
    {
        if (empty($this->indices) && isset($this->options["default"]) && $this->options["default"]) {
            for ($i = 0; $i < intval($this->options["default"]); $i++) {
                $this->indices[$i] = $i + 1;
            }
        }

        return $this;
    }

    /**
     * Loops through the block
     *
     * @return bool
     */
    public function loop()
    {
        $manual = false;
        if (array_key_exists("manual", $this->options) && $this->options["manual"] == true) {
            $manual = true;
        }

        $this->setDefault();

        if ($this->current > 0) {
            if (!$manual) {
                $this->blockDestruct();
                $this->blockEnd();
            }
        } else {
            if (!$manual) {
                $this->start();
            }
        }

        if ($this->current < count($this->indices) && $this->current < $this->options["limit"]) {
            if (!$manual) {
                $this->blockConstruct();
                $this->blockStart();
            }

            return true;
        } else {
            if (!$manual) {
                $this->end();
            }

            return false;
        }
    }

    /**
     * Alias for loop
     *
     * @deprecated
     * @see loop()
     *
     * @return bool
     */
    public function enumerate()
    {
        return $this->loop();
    }

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return $this
     */
    public function start()
    {
        $this->setupStaticEnvironment();

        // get configuration data for admin
        if (method_exists($this, "getDataEditmode")) {
            $data = $this->getDataEditmode();
        } else {
            $data = $this->getData();
        }

        $options = [
            "options" => $this->getOptions(),
            "data" => $data,
            "name" => $this->getName(),
            "id" => "pimcore_editable_" . $this->getName(),
            "type" => $this->getType(),
            "inherited" => $this->getInherited()
        ];
        $options = json_encode($options);

        $this->outputEditmode('
            <script type="text/javascript">
                editableConfigurations.push('.$options.');
            </script>
        ');

        // set name suffix for the whole block element, this will be addet to all child elements of the block
        $suffixes = [];
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_current");
        }
        $suffixes[] = $this->getName();
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_current", $suffixes);

        $class = "pimcore_editable pimcore_tag_" . $this->getType();
        if (array_key_exists("class", $this->getOptions())) {
            $class .= (" " . $this->getOptions()["class"]);
        }

        $this->outputEditmode('<div id="pimcore_editable_' . $this->getName() . '" name="' . $this->getName() . '" class="' . $class . '" type="' . $this->getType() . '">');

        return $this;
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     */
    public function end()
    {
        $this->current = 0;

        // remove the suffix which was set by self::start()
        $suffixes = [];
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_current");
            array_pop($suffixes);
        }
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode("</div>");
    }

    public function blockConstruct()
    {

        // set the current block suffix for the child elements (0, 1, 3, ...) | this will be removed in Pimcore_View_Helper_Tag::tag
        $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_numeration");
        $suffixes[] = $this->indices[$this->current];
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_numeration", $suffixes);
    }

    public function blockDestruct()
    {
        $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_numeration", $suffixes);
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     */
    public function blockStart()
    {
        $this->outputEditmode('<div class="pimcore_block_entry ' . $this->getName() . '" key="' . $this->indices[$this->current] . '">');
        $this->outputEditmode('<div class="pimcore_block_buttons_' . $this->getName() . ' pimcore_block_buttons">');
        $this->outputEditmode('<div class="pimcore_block_amount_' . $this->getName() . ' pimcore_block_amount"></div>');
        $this->outputEditmode('<div class="pimcore_block_plus_' . $this->getName() . ' pimcore_block_plus"></div>');
        $this->outputEditmode('<div class="pimcore_block_minus_' . $this->getName() . ' pimcore_block_minus"></div>');
        $this->outputEditmode('<div class="pimcore_block_up_' . $this->getName() . ' pimcore_block_up"></div>');
        $this->outputEditmode('<div class="pimcore_block_down_' . $this->getName() . ' pimcore_block_down"></div>');
        $this->outputEditmode('<div class="pimcore_block_clear"></div>');
        $this->outputEditmode('</div>');

        $this->current++;
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     */
    public function blockEnd()
    {
        $this->outputEditmode('</div>');
    }

    /**
     * Sends data to the output stream
     *
     * @param string $v
     */
    public function outputEditmode($v)
    {
        if ($this->getEditmode()) {
            echo $v . "\n";
        }
    }

    /**
     * Setup some settings that are needed for blocks
     */
    public function setupStaticEnvironment()
    {

        // setup static environment for blocks
        if (\Pimcore\Cache\Runtime::isRegistered("pimcore_tag_block_current")) {
            $current = \Pimcore\Cache\Runtime::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = [];
            }
        } else {
            $current = [];
        }

        if (\Pimcore\Cache\Runtime::isRegistered("pimcore_tag_block_numeration")) {
            $numeration = \Pimcore\Cache\Runtime::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = [];
            }
        } else {
            $numeration = [];
        }

        \Pimcore\Cache\Runtime::set("pimcore_tag_block_numeration", $numeration);
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_current", $current);
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        if (empty($options["limit"])) {
            $options["limit"] = 1000000;
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Return the amount of block elements
     *
     * @return int
     */
    public function getCount()
    {
        return count($this->indices);
    }

    /**
     * Return current iteration step
     *
     * @return int
     */
    public function getCurrent()
    {
        return $this->current - 1;
    }

    /**
     * Return current index
     *
     * @return int
     */
    public function getCurrentIndex()
    {
        return $this->indices[$this->getCurrent()];
    }

    /**
     * If object was serialized, set the counter back to 0
     */
    public function __wakeup()
    {
        $this->current = 0;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool) count($this->indices);
    }

    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param null $document
     * @param mixed $params
     * @param null $idMapper
     *
     * @return Model\Webservice\Data\Document\Element|void
     *
     * @throws \Exception
     *
     * @todo replace and with &&
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if (($data->indices === null or is_array($data->indices)) and ($data->current == null or is_numeric($data->current))) {
            $this->indices = $data->indices;
            $this->current = $data->current;
        } else {
            throw new \Exception("cannot get  values from web service import - invalid data");
        }
    }

    /**
     * @return Block\Item[]
     */
    public function getElements()
    {
        // init
        $doc = Model\Document\Page::getById($this->getDocumentId());

        $suffixes = (array)$this->suffixes;
        $suffixes[] = $this->getName();

        $list = [];
        foreach ($this->getData() as $index) {
            $list[] = new Block\Item($doc, $index, $suffixes);
        }

        return $list;
    }
}
