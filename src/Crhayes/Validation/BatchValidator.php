<?php

namespace Crhayes\Validation;

class BatchValidator
{
    /**
     * @var \Crhayes\Validation\ContextualValidator
     */
    protected $validator;

    /**
     * @var \Crhayes\Validation\ContextualValidator[]
     */
    protected $validationSet = [];

    /**
     * Process the validation against the collection of data
     *
     * @param array $dataCollection
     * @param ContextualValidator $validator
     * @param null $context
     */
    public function __construct(array $dataCollection, ContextualValidator $validator, $context = null)
    {
        $this->validationSet = array_map(function($data) use($validator, $context){
            return new $validator($data, $context);
        },$dataCollection);
    }

    /**
     * Static alias for the constructor
     *
     * @param array $dataCollection
     * @param ContextualValidator $validator
     * @param null $context
     * @return static
     */
    public static function process(array $dataCollection, ContextualValidator $validator, $context = null)
    {
        return new static($dataCollection,$validator,$context);
    }

    /**
     * Get the array of validation objects
     *
     * @return array|ContextualValidator[]
     */
    public function getValidators()
    {
        return $this->validationSet;
    }
} 