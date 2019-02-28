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

        // Mock the Model without fillable array set
        $this->modelWithoutFillable = Mockery::mock('ModelEloquentStubWithoutFillable');
        $this->modelWithoutFillable->makePartial();

        // Default payload for Model
        $this->payload = [
            'model_bar'  => ['text' => 'bar'],
            'model_foos' => [
                ['text' => 'foo1'],
                ['text' => 'foo2'],
            ]
        ];
    }

    /**
     * Tear Down and Clean Up Tests.
     */
    public function tearDown()
    {
        Mockery::close();
    }

    /**
     * The fillable test.
     */
    public function testFillable()
    {
        $this->model->fill(array_merge($this->payload, ['title' => 'foo', 'not_exists' => []]));

        $this->assertEquals(['title' => 'foo'], $this->model->getAttributes());
        $this->assertEquals([
            'model_bar'  => ['text' => 'bar'],
            'model_foos' => [
                ['text' => 'foo1'],
                ['text' => 'foo2']
            ]
        ], $this->model->getAcceptNestedAttributesFor());
    }

    /**
     * Test that a model with nested attributes can still save without fillable array.
     */
    public function testModelWithNestedAttributesCanSaveWithoutFillableArraySet()
    {
        $this->modelWithoutFillable->fill($this->payload);

        $this->assertEquals([
            'model_bar'  => ['text' => 'bar'],
            'model_foos' => [
                ['text' => 'foo1'],
                ['text' => 'foo2']
            ]
        ], $this->modelWithoutFillable->getAcceptNestedAttributesFor());
    }
}

class ModelEloquentStubWithoutFillable extends Model
{
    protected $table    = 'stubs';
    protected $nested   = ['model_bar', 'model_foos' ];

    public function modelBar()
    {
        return $this->hasOne(ModelBarStub::class);
    }

    public function modelFoos()
    {
        return $this->hasOne(ModelFooStub::class);
    }
}

class ModelEloquentStub extends ModelEloquentStubWithoutFillable
{
    protected $fillable = ['title'];
}

class ModelBarStub extends Model
{
    protected $fillable = ['text'];

    public function parent()
    {
        return $this->belongsTo(ModelEloquentStub::class);
    }
}

class ModelFooStub extends Model
{
    protected $fillable = ['text'];

    public function parent()
    {
        return $this->belongsTo(ModelEloquentStub::class);
    }
}
