<?php
namespace Icecave\Duct\TypeCheck;

class TypeInspector
{
    public function type($value, $maxIterations = 10)
    {
        $nativeType = \gettype($value);
        if ('array' === $nativeType) {
            return $this->arrayType($value, $maxIterations);
        }
        if ('double' === $nativeType) {
            return 'float';
        }
        if ('NULL' === $nativeType) {
            return 'null';
        }
        if ('object' === $nativeType) {
            return $this->objectType($value, $maxIterations);
        }
        if ('resource' === $nativeType) {
            return $this->resourceType($value);
        }
        return $nativeType;
    }

    protected function arrayType(array $value, $maxIterations)
    {
        return \sprintf('array%s', $this->traversableSubTypes($value, $maxIterations));
    }

    protected function objectType($value, $maxIterations)
    {
        $reflector = new \ReflectionObject($value);
        $class = $reflector->getName();
        $traversableSubTypes = '';
        if ($value instanceof \Traversable) {
            $traversableSubTypes = $this->traversableSubTypes($value, $maxIterations);
        }
        return \sprintf('%s%s', $class, $traversableSubTypes);
    }

    protected function traversableSubTypes($value, $maxIterations)
    {
        $keyTypes = array();
        $valueTypes = array();
        $i = 0;
        foreach ($value as $key => $subValue) {
            \array_push($keyTypes, $this->type($key));
            \array_push($valueTypes, $this->type($subValue));
            $i++;
            if ($i >= $maxIterations) {
                break;
            }
        }
        if (\count($valueTypes) < 1) {
            return '';
        }
        $keyTypes = \array_unique($keyTypes);
        \sort($keyTypes, SORT_STRING);
        $valueTypes = \array_unique($valueTypes);
        \sort($valueTypes, SORT_STRING);
        return \sprintf('<%s, %s>', \implode('|', $keyTypes), \implode('|', $valueTypes));
    }

    protected function resourceType($value)
    {
        $ofType = \get_resource_type($value);
        if ('stream' === $ofType) {
            return $this->streamType($value);
        }
        return \sprintf('resource {ofType: %s}', $ofType);
    }

    protected function streamType($value)
    {
        $metaData = \stream_get_meta_data($value);
        if (\preg_match('/[r+]/', $metaData['mode'])) {
            $readable = 'true';
        } else {
            $readable = 'false';
        }
        if (\preg_match('/[waxc+]/', $metaData['mode'])) {
            $writable = 'true';
        } else {
            $writable = 'false';
        }
        return \sprintf('stream {readable: %s, writable: %s}', $readable, $writable);
    }

}
