<?php

namespace Crhayes\Validation;

use Crhayes\Validation\Exceptions\ReplacementBindingException;

class BatchValidator
{
    /**
     * @var \Crhayes\Validation\ContextualValidator
     */
    protected $validator;

    /**
     * @var \Crhayes\Validation\ContextualValidator[]
     */
    protected $validationSet;

    /**
     * Process the validation against the collection of data
     *
     * @param array $dataCollection
     * @param ContextualValidator $validator
     * @param null $context
     * @return static
     */
    public function process(array $dataCollection, ContextualValidator $validator, $context = null)
    {
        if($dataCollection && $validator) {
            $this->validationSet = array_map(function ($data) use ($validator, $context) {
                return $validator::make($data, $context);
            }, $dataCollection);
        }

        return $this;
    }

    /**
     * Perform the replacement binding on all of the fields
     *
     * @param $field
     * @param $replacements
     */
    public function bindReplacements($field, $placeholder, $replacements)
    {
        if (count($replacements) !== count($this->validationSet)) {
            throw new ReplacementBindingException('Number of replacements bound must be equal to the validation set size');
        } else {
            array_map(function (&$validator,$index) use ($field, $placeholder,$replacements) {
                $temp = null;
                $temp[$placeholder] = $replacements[$index];

                $validator->bindReplacement($field, $temp);

            }, $this->validationSet, array_keys($this->validationSet));
        }

        return $this;
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

    /**
     * Set the validators
     *
     * @param ContextualValidator[]|ContextualValidator
     */
    public function setValidators($validators)
    {
        $validators = is_array($validators) ? $validators : [$validators];
        $this->validationSet = $validators;
    }
} 