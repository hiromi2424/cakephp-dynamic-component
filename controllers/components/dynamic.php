<?php
/**
 * Dynamic Compoent - loading components dynamically
 * 
 * This component allows you to load components from Controller::_initialize().
 * 
 * <code>
 * class FooController {
 *   function _initialize() {
 *     if($someCondition) {
 *       $this->Dynamic->loadComponents(array('Session', 'Admin'));
 *     }
 *   }
 * } 
 * </code>
 * 
 * And the components loaded by this way can also load other components
 * within their initialize callback.
 *
 * <code>
 * class AdminComponent {
 *   function initialize($controller, $settings=array()) {
 *     $controller->Dynamic->loadComponents('Auth');
 *   }
 * } 
 * </code>
 * 
 * @copyright Copyright 2010, Takayuki Miwa http://github.com/tkyk/
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * DynamicComponent class
 */
class DynamicComponent extends Object {

    /**
     * If the initialization phase is completed
     * 
     * @var boolean
     */
    protected $_initialized = false;

    /**
     * Controller
     * 
     * @var object
     */
    protected $_controller;

    /**
     * initialize callback
     * 
     * settings:
     *   prefix => if true, tries to load {Prefix}Component.
     * 
     * @param object $controller
     * @param array $settings
     */
    public function initialize($controller, $settings=array()) {
        $this->_controller = $controller;

        if(!empty($settings['prefix']) &&
           !empty($controller->params["prefix"])) {
            $this->_loadPrefixComponent($controller->params["prefix"]);
        }

        if(method_exists($this->_controller, '_initialize')) {
            $this->_callInitializeMethod($this->_controller, '_initialize');
        }
        $this->_initialized = true;
    }

    /**
     * Calls $object->$method(*$args)
     * 
     * @param object  $object
     * @param string  $method
     * @param array   $args
     * @return boolean
     */
    protected function _callInitializeMethod($object, $method, $args=array()) {
        if($this->_initialized) {
            // Only the initialize callback can call this method.
            return false;
        }
        call_user_func_array(array($object, $method), $args);
        return true;
    }

    /**
     * Tells this component to load $components.
     * Components already loaded are skipped.
     * 
     * loadComponents('Component')
     * loadComponents('Component1', 'Component2', .., 'ComponentN')
     * loadComponents(array('Component1',
     *                     'Component2' => array('params')))
     *
     * @param mixed $components
     */
    public function loadComponents($components) {
        if(func_num_args() > 1) {
            $components = func_get_args();
        } else {
            $components = (array)$components;
        }

        $componentsToLoad = array();
        foreach($components as $component => $cfg) {
            if(is_int($component)) {
                $component = $cfg;
                $cfg = array();
            }
            list($plugin, $name) = pluginSplit($component);
            if(empty($this->_controller->{$name})) {
                $componentsToLoad[$component] = $cfg;
            }
        }
        $newlyLoaded = $this->_loadComponents($this->_controller,
                                              $componentsToLoad);
        $this->_initializeComponents($this->_controller, $newlyLoaded);
    }

    /**
     * Loads components using Component::_loadComponents and 
     * returns newly loaded component names.
     * 
     * @param object $ctrl
     * @param array  $components
     * @return array  Newly loaded component names
     */
    protected function _loadComponents($ctrl, $components) {
        $beforeLoaded = array_keys($ctrl->Component->_loaded);
        $origComponents = $ctrl->components;
        $ctrl->components = $components;
        $ctrl->Component->_loadComponents($ctrl);
        $ctrl->components = $origComponents;
        $afterLoaded = array_keys($ctrl->Component->_loaded);
        return array_diff($afterLoaded, $beforeLoaded);
    }

    /**
     * Calls initialize method of the loaded components.
     * 
     * @param object $controller
     * @param array  $names  component names
     */
    protected function _initializeComponents($controller, $names) {
        $Loader = $controller->Component;
        foreach ($names as $name) {
            $component =& $Loader->_loaded[$name];
            
            if (method_exists($component,'initialize') && $component->enabled === true) {
                $settings = array();
                if (isset($Loader->__settings[$name])) {
                    $settings = $Loader->__settings[$name];
                }
                $this->_callInitializeMethod($component,
                                             'initialize',
                                             array(&$controller, $settings));
            }
        }
    }

    /**
     * Loads a component named $prefix
     * 
     * @param string prefix
     * @return boolean
     */
    function _loadPrefixComponent($prefix) {
        $component = Inflector::camelize($prefix);
        if(App::import('Component', $component)) {
            $this->loadComponents($component);
            return true;
        }
        return false;
    }
}
