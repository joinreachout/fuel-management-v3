<?php
/**
 * Unit tests for UnitConverter
 */

require_once __DIR__ . '/../src/Utils/UnitConverter.php';

use App\Utils\UnitConverter;

// Simple test runner
class TestRunner {
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function test(string $name, callable $fn) {
        $this->tests[] = ['name' => $name, 'fn' => $fn];
    }

    public function run() {
        echo "\n=== Running Unit Tests ===\n\n";

        foreach ($this->tests as $test) {
            try {
                $test['fn']();
                $this->passed++;
                echo "✅ {$test['name']}\n";
            } catch (\Exception $e) {
                $this->failed++;
                echo "❌ {$test['name']}\n";
                echo "   Error: {$e->getMessage()}\n";
            }
        }

        echo "\n=== Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . count($this->tests) . "\n\n";

        return $this->failed === 0;
    }
}

// Helper assertion functions
function assertEquals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new \Exception($message ?: "Expected $expected but got $actual");
    }
}

function assertThrows(callable $fn, string $message = '') {
    try {
        $fn();
        throw new \Exception($message ?: "Expected exception but none was thrown");
    } catch (\InvalidArgumentException $e) {
        // Expected - test passes
    }
}

// Create test runner
$t = new TestRunner();

// =============================================
// TESTS: litersToTons()
// =============================================

$t->test('litersToTons: 1000 liters of gasoline (0.75) = 0.75 tons', function() {
    $result = UnitConverter::litersToTons(1000, 0.75);
    assertEquals(0.75, $result);
});

$t->test('litersToTons: 1000 liters of diesel (0.84) = 0.84 tons', function() {
    $result = UnitConverter::litersToTons(1000, 0.84);
    assertEquals(0.84, $result);
});

$t->test('litersToTons: 50000 liters of gasoline = 37.5 tons', function() {
    $result = UnitConverter::litersToTons(50000, 0.75);
    assertEquals(37.5, $result);
});

$t->test('litersToTons: 0 liters = 0 tons', function() {
    $result = UnitConverter::litersToTons(0, 0.75);
    assertEquals(0.0, $result);
});

$t->test('litersToTons: throws on negative liters', function() {
    assertThrows(function() {
        UnitConverter::litersToTons(-100, 0.75);
    });
});

$t->test('litersToTons: throws on zero density', function() {
    assertThrows(function() {
        UnitConverter::litersToTons(1000, 0);
    });
});

$t->test('litersToTons: throws on negative density', function() {
    assertThrows(function() {
        UnitConverter::litersToTons(1000, -0.75);
    });
});

// =============================================
// TESTS: tonsToLiters()
// =============================================

$t->test('tonsToLiters: 1 ton of gasoline (0.75) = 1333.33 liters', function() {
    $result = UnitConverter::tonsToLiters(1, 0.75);
    assertEquals(1333.33, $result);
});

$t->test('tonsToLiters: 0.75 tons of gasoline = 1000 liters', function() {
    $result = UnitConverter::tonsToLiters(0.75, 0.75);
    assertEquals(1000.0, $result);
});

$t->test('tonsToLiters: 10 tons of diesel (0.84) = 11904.76 liters', function() {
    $result = UnitConverter::tonsToLiters(10, 0.84);
    assertEquals(11904.76, $result);
});

$t->test('tonsToLiters: 0 tons = 0 liters', function() {
    $result = UnitConverter::tonsToLiters(0, 0.75);
    assertEquals(0.0, $result);
});

$t->test('tonsToLiters: throws on negative tons', function() {
    assertThrows(function() {
        UnitConverter::tonsToLiters(-5, 0.75);
    });
});

// =============================================
// TESTS: Reversibility
// =============================================

$t->test('Reversibility: liters -> tons -> liters = original', function() {
    $original = 5000.0;
    $tons = UnitConverter::litersToTons($original, 0.75);
    $back = UnitConverter::tonsToLiters($tons, 0.75);
    assertEquals($original, $back);
});

$t->test('Reversibility: tons -> liters -> tons = original', function() {
    $original = 10.5;
    $liters = UnitConverter::tonsToLiters($original, 0.84);
    $back = UnitConverter::litersToTons($liters, 0.84);
    assertEquals($original, $back);
});

// =============================================
// TESTS: getDensity()
// =============================================

$t->test('getDensity: returns correct density for gasoline', function() {
    $result = UnitConverter::getDensity('gasoline');
    assertEquals(0.75, $result);
});

$t->test('getDensity: returns correct density for diesel', function() {
    $result = UnitConverter::getDensity('diesel');
    assertEquals(0.84, $result);
});

$t->test('getDensity: case insensitive', function() {
    $result = UnitConverter::getDensity('GASOLINE');
    assertEquals(0.75, $result);
});

$t->test('getDensity: returns default 0.75 for unknown fuel', function() {
    $result = UnitConverter::getDensity('unknown_fuel');
    assertEquals(0.75, $result);
});

// Run all tests
$success = $t->run();

exit($success ? 0 : 1);
