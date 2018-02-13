<?php
namespace service\components;

use yii\db\ActiveRecord;

/**
 * Resource transaction model
 *
 * @todo need collect conection by name
 * @category   Mage
 * @package    Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Transaction
{
    /**
     * Objects which will be involved to transaction
     *
     * @var array
     */
    protected $_objects = [];

    /**
     * Transaction objects array with alias key
     *
     * @var array
     */
    protected $_objectsByAlias = [];

    /**
     * Callbacks array.
     *
     * @var array
     */
    protected $_beforeCommitCallbacks = [];

    protected static $instance;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Begin transaction for all involved object resources
     *
     * @return $this
     */
    protected function _startTransaction()
    {
        foreach ($this->_objects as $object) {
            /** @var $object ActiveRecord */
            $object::getDb()->beginTransaction();
        }
        return $this;
    }

    /**
     * Commit transaction for all resources
     *
     * @return $this
     */
    protected function _commitTransaction()
    {
        foreach ($this->_objects as $object) {
            /** @var $object ActiveRecord */
            $object::getDb()->getTransaction()->commit();
        }
        return $this;
    }

    /**
     * Rollback transaction
     *
     * @return $this
     */
    protected function _rollbackTransaction()
    {
        foreach ($this->_objects as $object) {
            /** @var $object ActiveRecord */
            $object::getDb()->getTransaction()->rollBack();
        }
        return $this;
    }

    /**
     * Run all configured object callbacks
     *
     * @return $this
     */
    protected function _runCallbacks()
    {
        foreach ($this->_beforeCommitCallbacks as $callback) {
            call_user_func($callback);
        }
        return $this;
    }

    /**
     * Adding object for using in transaction
     *
     * @param ActiveRecord $object
     * @param string $alias
     * @return $this
     */
    public function addObject(ActiveRecord $object, $alias = '')
    {
        $this->_objects[] = $object;
        if (!empty($alias)) {
            $this->_objectsByAlias[$alias] = $object;
        }
        return $this;
    }

    /**
     * Add callback function which will be called before commit transactions
     *
     * @param callback $callback
     * @return $this
     */
    public function addCommitCallback($callback)
    {
        $this->_beforeCommitCallbacks[] = $callback;
        return $this;
    }

    /**
     * Initialize objects save transaction
     *
     * @return $this
     * @throws \Exception
     */
    public function save()
    {
        $this->_startTransaction();
        Tools::log('_startTransaction');
        $error = false;

        try {
            foreach ($this->_objects as $object) {
                /** @var $object ActiveRecord */
                Tools::log('before object saved');
                $object->save();
                Tools::log($object->toArray());
                Tools::log('after object saved');
            }
        } catch (\Exception $e) {
            $error = $e;
            Tools::log('Object saved exception');
        }

        if ($error === false) {
            try {
                Tools::log('before_runCallbacks');
                $this->_runCallbacks();
                Tools::log('after_runCallbacks');
            } catch (\Exception $e) {
                $error = $e;
            }
        }

        if ($error) {
            Tools::log('before_rollbackTransaction');
            $this->_rollbackTransaction();
            Tools::log('after_rollbackTransaction');
            throw $error;
        } else {
            Tools::log('before_commitTransaction');
            $this->_commitTransaction();
            Tools::log('after_commitTransaction');
        }

        return $this;
    }

    /**
     * Initialize objects delete transaction
     *
     * @return $this
     * @throws \Exception
     */
    public function delete()
    {
        $this->_startTransaction();
        $error = false;

        try {
            foreach ($this->_objects as $object) {
                /** @var $object ActiveRecord */
                $object->delete();
            }
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($error === false) {
            try {
                $this->_runCallbacks();
            } catch (\Exception $e) {
                $error = $e;
            }
        }

        if ($error) {
            $this->_rollbackTransaction();
            throw $error;
        } else {
            $this->_commitTransaction();
        }
        return $this;
    }

    public function reset(){
        $this->_objects = [];
        $this->_beforeCommitCallbacks = [];
        $this->_objectsByAlias = [];
    }
}
