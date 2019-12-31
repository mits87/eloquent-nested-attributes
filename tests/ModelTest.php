<?php

use Eloquent\NestedAttributes\Model;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Model.
 *
 * @author Piotr Krajewski <mits87@gmail.com>
 * @license https://github.com/esensi/model/blob/master/license.md MIT License
 */
class ModelTest extends TestCase
{
    /**
     * Set Up and Prepare Tests.
     */
    public function setUp(): void
    {
        // Mock the Model that uses the custom traits
        $this->model = Mockery::mock('ModelStub');
        $this->model->makePartial();
    }

    /**
     * Tear Down and Clean Up Tests.
     */
    public function tearDown(): void
    {
        Mockery::close();
    }

    /**
     * Test that the Model uses the traits and in the right order.
     */
    public function testModelUsesTraits(): void
    {
        // Get the traits off the model
        $traits = function_exists('class_uses_recursive') ?
            class_uses_recursive(get_class($this->model)) : class_uses(get_class($this->model));

        // Check Model uses the Validating trait
        $this->assertContains('Eloquent\NestedAttributes\Traits\HasNestedAttributesTrait', $traits);
    }
}

/**
 * Model Stub for Model Tests.
 */
class ModelStub extends Model
{
}
