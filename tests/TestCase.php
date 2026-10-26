<?php

namespace Tests;

use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Berechtigungen werden beim App-Start synchronisiert – bei der Test-Datenbank also VOR der Migration, daher hier nachholen.
        if (\Illuminate\Support\Facades\Schema::hasTable('permissions')) {
            \CisFoundation\CisPermissionManager\CisPermissionManager::syncToDatabase();
        }

        // Der Workflow-Cache ist statisch und überlebt sonst die per Transaktion zurückgerollten Testdaten.
        WorkflowRegistry::flush();
    }

    protected function tearDown(): void
    {
        WorkflowRegistry::flush();
        \Carbon\Carbon::setTestNow();

        parent::tearDown();
    }
}
