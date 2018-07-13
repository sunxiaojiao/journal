# 写一个di容器

di(依赖注入)是一种管理依赖的方法，它符合SOLID原则中的D，它的作用是减轻类与类之间的耦合，同时方便了测试。

现代的php框架都集成di容器做依赖管理，symfony，laravel，yii，phalcon等，还有国人熟知的thinkphp5，也已经使用了di容器。

容器容器，也就是放东西的地方，放的是什么东西呢，就是一些key,value。我们可以把具体的类放进去，把抽象接口映射成具体的实现类放进去，其实也可以把一些基础数据类型放进去，这让我想起了注册器模式。

从di容器中取出来的都是实例化好的对象，不管这个对象对应的类有多少的依赖，di容器统统都会帮你处理掉，但是你要事先在容器中定义过生成实例过程中需要的依赖项。

接下来，我们通过写一个简答的di容器来进一步了解它。
```php
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
                $paramClass = $param->getClass();
                if ($paramClass) {
                    $this->resolveDependencies($paramClass);
                } else {
                    $this->resolveNonClass($param);
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
```

## 使用

我们将controller放到容器里，这样我们便可以自动处理controller的依赖

```php
$di = new DI();
// 注册相关类
$di->bind(Request::class, Symfony\Component\HttpFoundation\Request::class);
$di->bind(IndexController::class, IndexController:class);
$controller = $di->get(IndexController::class);
$controller->method();

class IndexController 
{
    public __construct (Request $request) {
        // 这里的$request已经被自动注入成Symfony\Component\HttpFoundation\Request的实例
        $request;
    }    
}
```
