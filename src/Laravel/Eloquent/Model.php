<?php

namespace DeeToo\Essentials\Laravel\Eloquent;

use Illuminate\Database\Eloquent\Model as LaravelModel;
use DeeToo\Essentials\Exceptions\Error;
use DeeToo\Essentials\Laravel\Eloquent\Traits\ModelDecorators;
use DeeToo\Essentials\Laravel\Eloquent\Traits\ModelRelationships;
use DeeToo\Essentials\Laravel\Eloquent\Traits\ModelTypes;

/**
 * Class Base
 *
 * @package DeeToo\Essentials\Laravel\Eloquent
 */
abstract class Model extends LaravelModel
{
    use ModelTypes,
        ModelRelationships,
        ModelDecorators;

    /**
     * Base constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fireModelEvent('constructing', false);

        $traits = class_uses_deep($this);

        foreach ($traits as $trait) {
            $initMethod = 'init' . class_basename($trait);

            if (method_exists($this, $initMethod)) {
                $this->{$initMethod}();
            }
        }

        foreach ($traits as $trait) {
            $initMethod = 'afterInit' . class_basename($trait);

            if (method_exists($this, $initMethod)) {
                $this->{$initMethod}();
            }
        }

        parent::__construct($attributes);

        $this->fireModelEvent('constructed', false);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string $key
     *
     * @return mixed
     * @throws Error
     */
    public function getAttribute($key)
    {
        if (isset($this->decorated)) {
            if ($this->hasDecoratedAttribute($key)) {
                return $this->getDecoratedAttribute($key);
            }
        }

        if ($this->hasType($key)) {
            if ($this->hasError($key)) {
                throw new Error(':key error: :error', ['key' => $key, 'error' => $this->getError($key)]);
            }

            $value = $this->attributes[$key] ?? null;

            if (is_null($value)) {
                return $value;
            }

            $casted = $this->getType($key)->castFromPrimitive($value);

            if ($this->hasGetMutator($key)) {
                return $this->mutateAttribute($key, $casted);
            } else {
                return $casted;
            }
        }

        return parent::getAttribute($key);
    }

    /**
     * @param $key
     * @param $value
     */
    public function setRawAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Create a new LiveBuzz Eloquent Collection instance.
     *
     * @param  array $models
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * @param  \Closure|string $callback
     *
     * @return void
     */
    public static function constructing($callback)
    {
        static::registerModelEvent('constructing', $callback);
    }

    /**
     * Register a created model event with the dispatcher.
     *
     * @param  \Closure|string $callback
     *
     * @return void
     */
    public static function constructed($callback)
    {
        static::registerModelEvent('constructed', $callback);
    }
}
