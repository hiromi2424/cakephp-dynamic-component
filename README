# Dynamic Component

This plugin allows you to load components dynamically.

## Requirements

- CakePHP 1.3
- PHP 5.1 or later

## Installation 

    cd app/plugins
    git clone git://github.com/tkyk/cakephp-dynamic-component.git dynamic_component

## Usage

DynamicComponent's `initialize` callback invokes controller's `_initialize` method,
in which you can use `loadComponents` method to load components dynamically.

    class AppController extends Controller
    {
      var $components = array('DynamicComponent.Dynamic');

      function _initialize() {
        if(!empty($this->params["prefix"]) && $this->params["prefix"] == 'admin') {
          $this->Dynamic->loadComponents('Admin');
        }
        if(Configure::read('debug') > 0) {
          $this->Dynamic->loadComponents('Debug');
        }
      }
    }

The components loaded by this way are also able to load other components
in their own `initialize` callbacks.

    class AdminComponent extends Object
    {
      function initialize($controller) {
        $controller->Dynamic->loadComponents('Auth');

        // setup AuthComponent
        $controller->Auth->loginAction = ...
      }
    }

If `prefix` option is set to true, DynamicComponent will load a component
named params['prefix']. In this case, AdminComponent will be automatically loaded
when the `admin` prefix is on.
    
    class AppController extends Controller
    {
      var $components = array('DynamicComponent.Dynamic' => array('prefix' => true));

      function _initialize() {
        if(Configure::read('debug') > 0) {
          $this->Dynamic->loadComponents('Debug');
        }
      }
    }
