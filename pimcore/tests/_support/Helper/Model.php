<?php
namespace Pimcore\Tests\Helper;

use Codeception\Module;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Tests\Util\TestHelper;

class Model extends Module
{
    /**
     * @var array
     */
    protected $config = [
        'initialize_definitions' => true,
        'cleanup'                => true
    ];

    /**
     * @return Module|ClassManager
     */
    protected function getClassManager()
    {
        return $this->getModule('\\' . ClassManager::class);
    }

    /**
     * @inheritDoc
     */
    public function _beforeSuite($settings = [])
    {
        AbstractObject::setHideUnpublished(false);

        if ($this->config['initialize_definitions']) {
            if (TestHelper::supportsDbTests()) {
                $this->initializeDefinitions();
            } else {
                $this->debug('[MODEL] Not initializing model definitions as DB is not connected');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function _afterSuite()
    {
        if ($this->config['cleanup']) {
            TestHelper::cleanUp();
        }
    }

    /**
     * Initialize mode class definitions
     */
    public function initializeDefinitions()
    {
        $cm = $this->getClassManager();

        $cm->setupFieldcollection('unittestfieldcollection', 'fieldcollection-import.json');

        $unittestClass  = $this->setupUnittestClass('unittest', 'class-import.json');
        $allFieldsClass = $this->setupUnittestClass('allfields', 'class-allfields.json');

        $cm->setupClass('inheritance', 'inheritance.json');

        $cm->setupObjectbrick('unittestBrick', 'brick-import.json', [$unittestClass->getId()]);
    }

    /**
     * Setup standard Unittest class
     *
     * @param string $name
     * @param string $file
     *
     * @return ClassDefinition
     */
    public function setupUnittestClass($name = 'unittest', $file = 'class-import.json')
    {
        $cm = $this->getClassManager();

        if (!$cm->hasClass($name)) {
            /** @var ClassDefinition $class */
            $class = $cm->setupClass($name, $file);

            /** @var ClassDefinition\Data\ObjectsMetadata $fd */
            $fd = $class->getFieldDefinition('objectswithmetadata');
            if ($fd) {
                $fd->setAllowedClassId($class->getId());
                $class->save();
            }

            return $class;
        }

        return $cm->getClass($name);
    }
}
