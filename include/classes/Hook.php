<?php
/**
 * Library for handling hooks.
 *
 * @author    Josantonius <hello@josantonius.com>
 * @copyright 2017 (c) Josantonius - PHP-Hook
 * @license   https://opensource.org/licenses/MIT - The MIT License (MIT)
 * @link      https://github.com/Josantonius/PHP-Hook
 * @since     1.0.0
 */
//namespace Josantonius\Hook;

/**
 * Hook handler.
 * @since 1.0.0
 */
class Hook
{
    protected static $id = '0';
    protected $callbacks = [];
    protected $actions = ['count' => 0];
    protected static $current = false;
    protected $singleton = 'getInstance';
    private static $instances = [];

    /**
     * Get instance.
     * @since 1.0.0
     * @param int $id
     * @return object → instance
     */
    public static function getInstance($id = '0')
    {
        self::$id = $id;
        if (isset(self::$instances[self::$id])) {
            return self::$instances[self::$id];
        }

        return self::$instances[self::$id] = new self;
    }

    /**
     * Attach custom function to action hook.
     * @since 1.0.3
     * @param string   $tag      → action hook name
     * @param callable $func     → function to attach to action hook
     * @param int      $priority → order in which the action is executed
     * @param int      $args     → number of arguments accepted
     * @return bool
     */
    public static function addAction($tag, $func, $priority = 8, $args = 0)
    {
        $that = self::getInstance(self::$id);

        $that->callbacks[$tag][$priority][] = [
            'function' => $func,
            'arguments' => $args,
        ];

        return true;
    }

    /**
     * Add actions hooks from array.
     * @since 1.0.3
     * @param array $actions
     * @return bool
     */
    public static function addActions($actions)
    {
        foreach ($actions as $arguments) {
            call_user_func_array([__CLASS__, 'addAction'], $arguments);
        }

        return true;
    }

    /**
     * Run all hooks attached to the hook.
     *
     * By default it will look for getInstance method to use singleton
     * pattern and create a single instance of the class. If it does not
     * exist it will create a new object.
     *
     * @see setSingletonName() for change the method name.
     *
     * @since 1.0.3
     *
     * @param string $tag    → action hook name
     * @param mixed  $args   → optional arguments
     * @param bool   $remove → delete hook after executing actions
     *
     * @return returns the output of the last action or false
     */
    public static function doAction($tag, $args = [], $remove = true)
    {
        $that = self::getInstance(self::$id);

        self::$current = $tag;

        $that->actions['count']++;

        if (! array_key_exists($tag, $that->actions)) {
            $that->actions[$tag] = 0;
        }

        $that->actions[$tag]++;
        $actions = $that->getActions($tag, $remove);
        asort($actions);

        foreach ($actions as $priority) {
            foreach ($priority as $action) {
                $action = $that->runAction($action, $args);
            }
        }

        self::$current = false;

        return (isset($action)) ? $action : false;
    }

    /**
     * Set method name for use singleton pattern.
     *
     * @since 1.0.0
     *
     * @param string $method → singleton method name
     */
    public static function setSingletonName($method)
    {
        $that = self::getInstance(self::$id);

        $that->singleton = $method;
    }

    /**
     * Returns the current action hook.
     *
     * @since 1.0.3
     *
     * @return string|false → current action hook
     */
    public static function current()
    {
        return self::$current;
    }

    /**
     * Check if there is a certain action hook.
     *
     * @since 1.0.7
     *
     * @param string $tag → action hook name
     *
     * @return bool
     */
    public static function isAction($tag)
    {
        $that = self::getInstance(self::$id);

        return isset($that->callbacks[$tag]);
    }

    /**
     * Run action hook.
     *
     * @since 1.0.3
     *
     * @param string $action → action hook
     * @param int    $args   → arguments
     *
     * @return callable|false → returns the calling function
     */
    private function runAction($action, $args)
    {
        $function = $action['function'];
        $argsNumber = $action['arguments'];

        $class = (isset($function[0])) ? $function[0] : false;
        $method = (isset($function[1])) ? $function[1] : false;

        $args = $this->getArguments($argsNumber, $args);

        if (! ($class && $method) && function_exists($function)) {
            return call_user_func($function, $args);
        } elseif ($obj = call_user_func([$class, $this->singleton])) {
            if ($obj !== false) {
                return call_user_func_array([$obj, $method], $args);
            }
        } elseif (class_exists($class)) {
            $instance = new $class;

            return call_user_func_array([$instance, $method], $args);
        }
    }

    /**
     * Get actions for hook
     *
     * @since 1.0.3
     *
     * @param string $tag    → action hook name
     * @param bool   $remove → delete hook after executing actions
     *
     * @return object|false → returns the calling function
     */
    private function getActions($tag, $remove)
    {
        if (isset($this->callbacks[$tag])) {
            $actions = $this->callbacks[$tag];
            if ($remove) {
                unset($this->callbacks[$tag]);
            }
        }

        return (isset($actions)) ? $actions : [];
    }

    /**
     * Get arguments for action.
     *
     * @since 1.0.3
     *
     * @param int   $argsNumber → arguments number
     * @param mixed $arguments  → arguments
     *
     * @return array → arguments
     */
    private function getArguments($argsNumber, $arguments)
    {
        if ($argsNumber == 1 && is_string($arguments)) {
            return [$arguments];
        } elseif ($argsNumber === count($arguments)) {
            return $arguments;
        }

        for ($i = 0; $i < $argsNumber; $i++) {
            if (array_key_exists($i, $arguments)) {
                $args[] = $arguments[$i];
                continue;
            }

            return $args;
        }

        return [];
    }
}