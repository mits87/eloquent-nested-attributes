<?php

namespace Eloquent\NestedAttributes\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

trait HasNestedAttributesTrait
{
    /**
     * Defined nested attributes 
     *
     * @var array
     */
    protected $acceptNestedAttributesFor = [];

    /**
     * Get accept nested attributes
     *
     * @return array
     */
    public function getAcceptNestedAttributesFor()
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
    public function fill(array $attributes)
    {
        if (!empty($this->nested)) {
            $this->acceptNestedAttributesFor = [];

            foreach ($this->nested as $attr) {
                if (isset($attributes[$attr])) {
                    $this->acceptNestedAttributesFor[$attr] = $attributes[$attr];
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
    public function save(array $options = [])
    {
        DB::beginTransaction();

        if (!parent::save($options)) {
            return false;
        }

        foreach ($this->getAcceptNestedAttributesFor() as $attribute => $stack) {
            $methodName = lcfirst(join(array_map('ucfirst', explode('_', $attribute))));
    
            if (!method_exists($this, $methodName)) {
                throw new Exception('The nested atribute relation "' . $methodName . '" does not exists.');
            }

            $relation = $this->$methodName();
            
            if ($relation instanceof HasOne || $relation instanceof MorphOne) {
                if (!$this->saveNestedAttributes($relation, $stack)) {
                    return false;
                }
            } else if ($relation instanceof HasMany || $relation instanceof MorphMany) {
                foreach ($stack as $params) {
                    if (!$this->saveNestedAttributes($relation, $params)) {
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
     * Save the nested relation attributes to the database.
     *
     * @param  Illuminate\Database\Eloquent\Relations  $relation
     * @param  array                                   $params
     * @return bool
     */
    protected function saveNestedAttributes($relation, array $params)
    {
        if ($this->exists) {
            if (isset($params['id'])) {
                $model = $relation->findOrFail($params['id']);
            } else {
                $model = $relation->firstOrFail();
            }
            return $model->update($params);
        } else if ($relation->create($params)) {
            return true;
        }
        return false;
    }
}