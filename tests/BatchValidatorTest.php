<?php

namespace Crhayes\Validation\Tests;

use Crhayes\Validation\BatchValidator;
use Mockery as m;

class BatchValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $validator;

    private $input;

    private $context;

    public function tearDown()
    {
        m::close();
    }

    public function setUp()
    {
        $this->input = [
            [
                'first_name' => 'Chris',
                'last_name' => 'Hayes',
                'website' => 'http://www.chrishayes.ca'
            ],
            [
                'first_name' => 'Brandon',
                'last_name' => 'Martel',
                'website' => 'http://www.brandonmartel.ca'
            ]
        ];

        $this->context = 'edit';

        $this->validator = new ConcreteValidator($this->input[0]);
    }

    public function testProcessMethodReturnsBatchValidator()
    {
        $this->assertInstanceOf('\Crhayes\Validation\BatchValidator', (new BatchValidator())->process($this->input, $this->validator));
    }

    public function testGetValidatorsReturnsArray()
    {
        $validators = (new BatchValidator())->process($this->input, $this->validator)->getValidators();

        $this->assertEquals(true, is_array($validators));
    }

    public function testGetValidatorsReturnsValidatorForEachDataArray()
    {
        $validators = (new BatchValidator())->process($this->input, $this->validator)->getValidators();

        $this->assertEquals(count($this->input), count($validators));
    }

    public function testAllValidatorsAreTheSameValidationClass()
    {
        $validators = (new BatchValidator())->process($this->input, $this->validator)->getValidators();

        $this->assertInstanceOf('\Crhayes\Validation\ContextualValidator', $validators[0]);
        $this->assertInstanceOf('\Crhayes\Validation\ContextualValidator', $validators[1]);
    }

    public function testEachValidatorHasCorrectInput()
    {
        $validators = (new BatchValidator())->process($this->input, $this->validator)->getValidators();

        $this->assertEquals($this->input[0], $validators[0]->getAttributes());
        $this->assertEquals($this->input[1], $validators[1]->getAttributes());
    }

    public function testSettingContextThroughConstructor()
    {
        $validators = (new BatchValidator())->process($this->input, $this->validator, $this->context)->getValidators();

        $this->assertEquals([$this->context], $validators[0]->getContexts());
        $this->assertEquals([$this->context], $validators[1]->getContexts());
    }

    public function testBindingReplacements()
    {
        $batchValidator = new BatchValidator();
        $validators[] = new ConcreteValidator($this->input[0], ['edit']);
        $validators[] = new ConcreteValidator($this->input[1], ['edit']);

        $batchValidator->setValidators($validators);

        $batchValidator->bindReplacements('first_name', 'id', [1,2]);
    }
}
 