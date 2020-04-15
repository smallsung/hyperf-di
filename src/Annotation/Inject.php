<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Di\Annotation;

use Hyperf\Autoload\AnnotationReader;
use Hyperf\Di\BetterReflectionManager;
use Hyperf\Di\ReflectionManager;
use Hyperf\Utils\Str;
use PhpDocReader\AnnotationException;
use PhpDocReader\PhpDocReader;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Object_;
use Roave\BetterReflection\TypesFinder\FindPropertyType;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Inject extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var bool
     */
    public $required = true;

    /**
     * @var bool
     */
    public $lazy = false;

    /**
     * @var FindPropertyType
     */
    private $typeInvoker;

    public function __construct($value = null)
    {
        parent::__construct($value);
        $this->typeInvoker = make(FindPropertyType::class);
    }

    public function collectProperty(string $className, ?string $target): void
    {
        try {
            $reflectionClass = BetterReflectionManager::reflectClass($className);
            $reflectionProperty = $reflectionClass->getProperty($target);
            $reflectionTypes = $this->typeInvoker->__invoke($reflectionProperty, $reflectionClass->getDeclaringNamespaceAst());
            if ($reflectionTypes[0] instanceof Object_) {
                $this->value = ltrim((string)$reflectionTypes[0], '\\');
            }
            AnnotationCollector::collectProperty($className, $target, static::class, $this);
            if ($this->lazy) {
                $this->value = 'HyperfLazy\\' . $this->value;
            }
        } catch (AnnotationException $e) {
            if ($this->required) {
                throw $e;
            }
            $this->value = '';
        }
    }
}
