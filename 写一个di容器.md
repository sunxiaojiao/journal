# 写一个di容器

di(依赖注入)是一种管理依赖的方法，它符合SOLID原则中的D，它的作用是减轻类与类之间的耦合，同时方便了测试。

现代的php框架都集成di容器做依赖管理，symfony，laravel，yii，phalcon等，还有国人熟知的thinkphp5，也已经使用了di容器。

容器容器，也就是放东西的地方，放的是什么东西呢，就是一些key,value。我们可以把具体的类放进去，把抽象接口映射成具体的实现类放进去，其实也可以把一些基础数据类型放进去，这让我想起了注册器模式。

从di容器中取出来的都是实例化好的对象，不管这个对象对应的类有多少的依赖，di容器统统都会帮你处理掉，但是你要事先在容器中定义过生成实例过程中需要的依赖项。

接下来，我们通过写一个简答的di容器来进一步了解它。
```php
namespace DI;
use Psr\Container\ContainerInterface;
// psr-11中约定异常
use Exceptions\NotFoundException;
use Exceptions\ContainerException;

class DI implements ContainerInterface
{
	// 存储对象
	protected static $instance = [];
	// 存储注册的依赖 
	protected $mapping = [];
	
	// 绑定依赖
    public function bind ($string, $value) {
        
    }
    
    public function bindOnce ($string, $value) {
        if (!$this->has($string)) {
            $this->bind($string, $value);
        }
    }
    
    public function resolve ($string) {
        if (!$this->has($string)) throw new NotFoundException;
        
    }
    
	// 构建对象
	protected function build ($string) {
        if (!$this->has($string)) {
            throw new NotFoundException;
        }
        
        if (array_key_exists($string, self::$instance[$string])) {
            return self::$instance[$string];
        }
        
        $className = $this->mapping[$string];
        
        if (!class_exists($string)) throw new ContainerException('class not found ' + $className);
        
        $class = \ReflectionClass($string);
	    $dependenies = $this->resloveDependenies($class);
	    $instance = $class->newInstanceArgs($depenies);
		return $instance;
	}

	// 处理依赖
	protected function resloveDependenies ($class) {
        $constructor = $class->getConstruct();
		
		$reslovedDependenies = [];
        if ($constructor && $contstructor->isPublic()) {
            $params = $constructor->getParams();
            foreach ($params as $param) {
                $paramClass = $param->getClass();
                if ($paramClass) {
                    $this->resloveDependenies($paramClass);
                } else {
                    $this->resloveNonClass($param);
                }
            }
        } else {
          
        }
        return $reslovedDependenies;
	}
	
	protected function resloveNonClass (\ReflectionParameter $param) {
        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }
        
        throw new Exception($param->name . '没有赋值');
	}
	
	// 实现psr-11
	public function get ($string) {
        if (self::$instance[$string]) return self::$instance[$string];
        if ($this->has($string)) {
            return $this->reslove($string);
        }
	}
	// 实现psr-11
	public function has ($string) {
        return self::$instance[$string] || $this->mapping[$string];
	}
	
}
```

```php
$di = new DI();
$di->bind(Request::class, '');
$di->bind('Controller', Controller:class);
$controller = $di->get('Controller');
$controller->method();
```