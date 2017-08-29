<?php

namespace Eloquent\NestedAttributes\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            
            if ($relation instanceof HasOne) {
                if ($this->exists && $model = $relation->first()) {
                    if (!$model->update($stack)) {
                        return false;
                    }
                } else if (!$relation->create($stack)) {
                    return false;
                }
            } else if ($relation instanceof HasMany) {
                foreach ($stack as $attrs) {
                    if (isset($attrs['id']) && $this->exists) {
                        if (!$model = $relation->find($attrs['id'])) {
                            throw new Exception('Not found.');
                        }

                        if (!$model->update($attrs)) {
                            return false;
                        }
                    } else if (!$relation->create($attrs)) {
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
}