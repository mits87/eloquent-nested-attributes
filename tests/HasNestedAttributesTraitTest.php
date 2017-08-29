<?php

use PHPUnit\Framework\TestCase;
use Eloquent\NestedAttributes\Model;

class HasNestedAttributesTraitTest extends TestCase
{
    /**
     * Set Up and Prepare Tests.
     */
    public function setUp()
    {
        // Mock the Model that uses the custom traits
        $this->model = Mockery::mock('ModelEloquentStub');
        $this->model->makePartial();
    }

    /**
     * Tear Down and Clean Up Tests.
     */
    public function tearDown()
    {
        Mockery::close();
    }

    public function testFillable()
    {
        $params = [
            'title' => 'foo',

            'not_exists' => [],

            'model_bar' => [
                'text' => 'bar'
            ],

            'model_foos' => [
                ['text' => 'foo']
            ]
        ];

        $this->model->fill($params);

        $this->assertEquals(['title' => 'foo'], $this->model->getAttributes());

        $this->assertEquals([
            'model_bar'  => ['text' => 'bar'],
            'model_foos' => [['text' => 'foo']]
        ], $this->model->getAcceptNestedAttributesFor());
    }
}

class ModelEloquentStub extends Model {
    protected $table = 'stub';
    protected $fillable = ['title'];
    protected $nested = ['model_bar', 'model_foos' ];
}