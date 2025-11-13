<?php

namespace Nexus\Backoffice\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Model;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Casts\HierarchyPath;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\BackOfficeServiceProvider;

#[CoversClass(HierarchyPath::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[CoversClass(StaffTransfer::class)]
class HierarchyPathCastTest extends TestCase
{
    protected HierarchyPath $cast;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new HierarchyPath();
    }

    // --- get() method tests ---

    #[Test]
    public function test_get_returns_null_for_null_value()
    {
        $model = $this->createMock(Model::class);
        $result = $this->cast->get($model, 'hierarchy_path', null, []);
        $this->assertNull($result);
    }

    #[Test]
    public function test_get_decodes_json_string()
    {
        $model = $this->createMock(Model::class);
        $json = json_encode([1, 2, 3]);
        $result = $this->cast->get($model, 'hierarchy_path', $json, []);
        $this->assertEquals([1, 2, 3], $result);
    }

    #[Test]
    public function test_get_returns_array_as_is()
    {
        $model = $this->createMock(Model::class);
        $value = [10, 20, 30];
        $result = $this->cast->get($model, 'hierarchy_path', $value, []);
        $this->assertSame($value, $result);
    }

    // --- set() method tests ---

    #[Test]
    public function test_set_returns_null_for_null_value()
    {
        $model = $this->createMock(Model::class);
        $result = $this->cast->set($model, 'hierarchy_path', null, []);
        $this->assertNull($result);
    }

    #[Test]
    public function test_set_encodes_array_to_json()
    {
        $model = $this->createMock(Model::class);
        $value = [5, 6, 7];
        $result = $this->cast->set($model, 'hierarchy_path', $value, []);
        $this->assertEquals(json_encode($value), $result);
    }

    #[Test]
    public function test_set_accepts_valid_json_string()
    {
        $model = $this->createMock(Model::class);
        $json = json_encode([8, 9]);
        $result = $this->cast->set($model, 'hierarchy_path', $json, []);
        $this->assertEquals($json, $result);
    }

    #[Test]
    public function test_set_throws_exception_for_invalid_json_string()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON string provided for hierarchy path.');

        $model = $this->createMock(Model::class);
        $invalidJson = '{"bad": [1,2,}';
        $this->cast->set($model, 'hierarchy_path', $invalidJson, []);
    }

    #[Test]
    public function test_set_throws_exception_for_invalid_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hierarchy path must be an array or valid JSON string.');

        $model = $this->createMock(Model::class);
        $this->cast->set($model, 'hierarchy_path', 12345, []);
    }
}
