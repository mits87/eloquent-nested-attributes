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

    /**
     * The fillable test.
     */
    public function testFillable()
    {
        $payload = [
            'title'      => 'foo',
            'not_exists' => [],
            'model_bar'  => ['text' => 'bar'],
            'model_foos' => [
                ['text' => 'foo1'],
                ['text' => 'foo2'],
            ]
        ];

        $this->model->fill($payload);

        $this->assertEquals(['title' => 'foo'], $this->model->getAttributes());
        $this->assertEquals([
            'model_bar'  => ['text' => 'bar'],
            'model_foos' => [
                ['text' => 'foo1'], 
                ['text' => 'foo2']
            ]
        ], $this->model->getAcceptNestedAttributesFor());
    }
}

class ModelEloquentStub extends Model {
    protected $table    = 'stubs';
    protected $fillable = ['title'];
    protected $nested   = ['model_bar', 'model_foos' ];
    
    public function modelBar() {
        return $this->hasOne(ModelBarStub::class);
    }

    public function modelFoos() {
        return $this->hasOne(ModelFooStub::class);
    }
}

class ModelBarStub extends Model {
    protected $fillable = ['text'];
    
    public function parent() {
        return $this->belongsTo(ModelEloquentStub::class);
    }
}

class ModelFooStub extends Model {
    protected $fillable = ['text'];

    public function parent() {
        return $this->belongsTo(ModelEloquentStub::class);
    }
}