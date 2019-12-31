<?php

namespace Eloquent\NestedAttributes\Traits;

use Exception;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;

trait HasNestedAttributesTrait
{
    /**
     * Defined nested attributes.
     *
     * @var array
     */
    protected $acceptNestedAttributesFor = [];

    /**
     * Defined "destroy" key name.
     *
     * @var string
     */
    protected $destroyNestedKey = '_destroy';

    /**
     * Get accept nested attributes.
     *
     * @return array
     */
    public function getAcceptNestedAttributesFor(): array
    {
        return $this->acceptNestedAttributesFor;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param  array  $attributes
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes): self
    {
        if (! empty($this->nested)) {
            $this->acceptNestedAttributesFor = [];

            foreach ($this->nested as $attr) {
                if (isset($attributes[$attr])) {
                    $this->acceptNestedAttributesFor[$attr] = $attributes[$attr];
                    unset($attributes[$attr]);
                }
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = []): bool
    {
        DB::beginTransaction();

        if (! parent::save($options)) {
            return false;
        }

        foreach ($this->getAcceptNestedAttributesFor() as $attribute => $stack) {
            $methodName = lcfirst(implode(array_map('ucfirst', explode('_', $attribute))));

            if (! method_exists($this, $methodName)) {
                throw new Exception('The nested atribute relation "' . $methodName . '" does not exists.');
            }

            $relation = $this->$methodName();

            if ($relation instanceof HasOne || $relation instanceof MorphOne) {
                if (! $this->saveNestedAttributes($relation, $stack)) {
                    return false;
                }
            } elseif ($relation instanceof HasMany || $relation instanceof MorphMany) {
                foreach ($stack as $params) {
                    if (! $this->saveManyNestedAttributes($this->$methodName(), $params)) {
                        return false;
                    }
                }
            } else {
                throw new Exception('The nested atribute relation is not supported for "' . $methodName . '".');
            }
        }

        DB::commit();

        return true;
    }

    /**
     * Save the hasOne nested relation attributes to the database.
     *
     * @param  Illuminate\Database\Eloquent\Relations  $relation
     * @param  array                                   $params
     * @return bool
     */
    protected function saveNestedAttributes(Relations $relation, array $params): bool
    {
        if ($this->exists && $model = $relation->first()) {
            if ($this->allowDestroyNestedAttributes($params)) {
                return $model->delete();
            }

            return $model->update($stack);
        } elseif ($relation->create($stack)) {
            return true;
        }

        return false;
    }

    /**
     * Save the hasMany nested relation attributes to the database.
     *
     * @param  Illuminate\Database\Eloquent\Relations  $relation
     * @param  array                                   $params
     * @return bool
     */
    protected function saveManyNestedAttributes($relation, array $params): bool
    {
        if (isset($params['id']) && $this->exists) {
            $model = $relation->findOrFail($params['id']);

            if ($this->allowDestroyNestedAttributes($params)) {
                return $model->delete();
            }

            return $model->update($params);
        } elseif ($relation->create($params)) {
            return true;
        }

        return false;
    }

    /**
     * Check can we delete nested data.
     *
     * @param  array $params
     * @return bool
     */
    protected function allowDestroyNestedAttributes(array $params): bool
    {
        return isset($params[$this->destroyNestedKey]) && (bool) $params[$this->destroyNestedKey] == true;
    }
}
