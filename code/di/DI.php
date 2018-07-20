<?php

namespace DI;

require 'vendor/autoload.php';

use Psr\Container\ContainerInterface;

use DI\Exceptions\NotFoundException;
use DI\Exceptions\ContainerException;

class DI implements ContainerInterface
{
    // 存储对象
    protected static $instance = [];
    // 存储注册的依赖
    protected $mapping = [];

    // 绑定依赖
    public function bind($string, $value)
    {
        return $this->mapping[$string] = $value;
    }

    public function instance($string, $instance)
    {
        return self::$instance[$string] = $instance;
    }

    public function resolve($string)
    {
        if (!$this->has($string)) throw new NotFoundException;
        return $this->build($string);
    }

    // 构建对象
    protected function build($string)
    {
        if (!$this->has($string)) {
            throw new NotFoundException;
        }

        if (isset(self::$instance[$string])) {
            return self::$instance[$string];
        }

        $className = $this->mapping[$string];

        if (!class_exists($className)) return $className;

        $class        = new \ReflectionClass($className);
        $dependencies = $this->resolveDependencies($class);
        $instance     = $class->newInstanceArgs($dependencies);
        return $instance;
    }

    // 处理依赖
    protected function resolveDependencies(\ReflectionClass $class)
    {
        $constructor = $class->getConstructor();

        $resolvedDependencies = [];
        if ($constructor && $constructor->isPublic()) {
            $params = $constructor->getParameters();
            foreach ($params as $param) {
                $paramClass = $param->getClass() ? $param->getClass()->name : null;
                if ($paramClass) {
                    $resolvedDependencies[] = $this->resolve($paramClass);
                } else {
                    $resolvedDependencies[] = $this->resolveNonClass($param);
                }
            }
        } else {

        }
        return $resolvedDependencies;
    }

    protected function resolveNonClass(\ReflectionParameter $param)
    {
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        throw new Exception($param->name . '没有赋值');
    }

    // 实现psr-11
    public function get($string)
    {
        if (isset(self::$instance[$string])) return self::$instance[$string];

        if ($this->has($string)) {
            return $this->resolve($string);
        }
        throw new NotFoundException;
    }

    // 实现psr-11
    public function has($string)
    {
        return isset(self::$instance[$string]) || isset($this->mapping[$string]);
    }

}