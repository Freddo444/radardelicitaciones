<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            return;
        }

        // Production runs MySQL; the sqlite test database diverges in two ways
        // the app depends on, so mirror them here to keep the whole app testable:
        // 1) enum-widening migrations are MySQL-only (guarded to no-op on sqlite),
        //    leaving narrow CHECK constraints — relax enforcement so real states
        //    like a 'trialing' subscription can be inserted.
        $connection->statement('PRAGMA ignore_check_constraints = ON');

        // 2) FIELD() (used to order by enum priority) is a MySQL builtin; shim it.
        $connection->getPdo()->sqliteCreateFunction('field', function ($value, ...$list) {
            foreach ($list as $index => $candidate) {
                if ((string) $candidate === (string) $value) {
                    return $index + 1;
                }
            }

            return 0;
        });
    }
}
