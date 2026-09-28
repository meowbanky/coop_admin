<?php
/**
 * Minimal test helpers shared by the scripts in this directory.
 * Each script is run on its own from the coop_admin directory, e.g.
 *     php tests/member_identity_test.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$failures = [];

function test($name, callable $body)
{
    global $failures;

    try {
        $body();
        echo "PASS  {$name}\n";
    } catch (Throwable $e) {
        $failures[] = $name;
        echo "FAIL  {$name}\n      " . $e->getMessage() . "\n";
    }
}

function assertSameValue($expected, $actual)
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
        );
    }
}

function assertContains($needle, $haystack)
{
    if (!is_string($haystack) || strpos($haystack, $needle) === false) {
        throw new RuntimeException(
            'Expected text containing ' . var_export($needle, true) . ', got ' . var_export($haystack, true)
        );
    }
}

/**
 * Prints the summary and exits non-zero when any test failed.
 */
function finishTests()
{
    global $failures;

    echo "\n" . (count($failures) === 0 ? 'All tests passed' : count($failures) . ' test(s) failed') . "\n";
    exit(count($failures) === 0 ? 0 : 1);
}
