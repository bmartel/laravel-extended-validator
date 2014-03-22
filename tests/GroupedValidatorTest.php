<?php

namespace Crhayes\Validation\Tests;

use Crhayes\Validation\GroupedValidator;
use Mockery as m;

class GroupedValidatorTest extends \PHPUnit_Framework_TestCase
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
        parent::setUp();
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

        $this->context = ['default'];

        $this->validator = new ConcreteValidator($this->input[0]);
    }

    public function testMakeMethodReturnsGroupedValidator()
    {
        $this->assertInstanceOf('\CrHayes\Validation\GroupedValidator', GroupedValidator::make());
    }

    public function testAddValidatorAcceptsSingleValidatorObject()
    {
        $validator = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[0]);

        $groupedValidator = GroupedValidator::make();

        $returnVal = $groupedValidator->addValidator($validator);

        $this->assertInstanceOf('\CrHayes\Validation\GroupedValidator', $returnVal);
    }

    public function testAddValidatorAcceptsValidatorObjectArray()
    {
        $validator[] = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[0]);
        $validator[] = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[1]);

        $groupedValidator = GroupedValidator::make();

        $returnVal = $groupedValidator->addValidator($validator);

        $this->assertInstanceOf('\CrHayes\Validation\GroupedValidator', $returnVal);
    }

    public function testGroupedValidatorPasses()
    {
        $validator1 = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[0]);
        $validator2 = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[1]);

        $validator1->shouldReceive('passes')->once()->andReturn(true);
        $validator2->shouldReceive('passes')->once()->andReturn(true);

        $groupedValidator = GroupedValidator::make()->addValidator([$validator1,$validator2]);

        $this->assertTrue($groupedValidator->passes());
    }

    public function testGroupedValidatorFails()
    {
        $messageBag = m::mock('\Illuminate\Support\MessageBag');
        $validator1 = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[0]);
        $validator2 = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[1]);

        $messageBag->shouldReceive('getMessages')->once()->andReturn(['errors']);
        $validator1->shouldReceive('passes')->once()->andReturn(false);
        $validator1->shouldReceive('getMessageBag')->once()->andReturn($messageBag);
        $validator2->shouldReceive('passes')->once()->andReturn(true);

        $groupedValidator = GroupedValidator::make()->addValidator([$validator1,$validator2]);

        $this->assertTrue($groupedValidator->fails());
    }

    public function testGroupedValidatorReturnsMessageBagErrors()
    {
        $messageBag = m::mock('\Illuminate\Support\MessageBag');
        $validator1 = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[0]);
        $validator2 = m::mock('\CrHayes\Validation\ContextualValidator', $this->input[1]);

        $messageBag->shouldReceive('getMessages')->once()->andReturn(['errors']);
        $validator1->shouldReceive('passes')->once()->andReturn(false);
        $validator1->shouldReceive('getMessageBag')->once()->andReturn($messageBag);
        $validator2->shouldReceive('passes')->once()->andReturn(true);

        $groupedValidator = GroupedValidator::make()->addValidator([$validator1,$validator2]);
        $groupedValidator->fails();

        $this->assertInstanceOf('\Illuminate\Support\MessageBag', $groupedValidator->errors());
    }
}
 