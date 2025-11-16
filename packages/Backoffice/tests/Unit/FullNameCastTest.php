<?php
namespace Nexus\Backoffice\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\Model;
use Nexus\Backoffice\Casts\FullName;
use Nexus\Backoffice\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Nexus\Backoffice\Models\StaffTransfer;
use Nexus\Backoffice\BackOfficeServiceProvider;

#[CoversClass(FullName::class)]
#[CoversClass(BackOfficeServiceProvider::class)]
#[CoversClass(StaffTransfer::class)]
class FullNameCastTest extends TestCase
{
    protected FullName $cast;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cast = new FullName();
    }

    #[Test]
    public function test_get_returns_full_name()
    {
        $model = $this->createMock(Model::class);
        $attributes = ['first_name' => 'Azahari', 'last_name' => 'Zaman'];

        $result = $this->cast->get($model, 'full_name', null, $attributes);

        $this->assertEquals('Azahari Zaman', $result);
    }

    #[Test]
    public function test_get_handles_missing_last_name()
    {
        $model = $this->createMock(Model::class);
        $attributes = ['first_name' => 'Azahari'];

        $result = $this->cast->get($model, 'full_name', null, $attributes);

        $this->assertEquals('Azahari', $result);
    }

    #[Test]
    public function test_get_returns_null_if_both_names_missing()
    {
        $model = $this->createMock(Model::class);
        $attributes = [];

        $result = $this->cast->get($model, 'full_name', null, $attributes);

        $this->assertNull($result);
    }

    #[Test]
    public function test_set_returns_original_value()
    {
        $model = $this->createMock(Model::class);
        $value = 'Should not be stored';

        $result = $this->cast->set($model, 'full_name', $value, []);

        $this->assertEquals($value, $result);
    }
}
