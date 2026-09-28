<?php
/**
 * Tests for includes/import_response_helper.php.
 *
 * Run from the coop_admin directory:  php tests/import_response_test.php
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../includes/import_response_helper.php';

function captureImportFailure($statusCode, $reason)
{
    ob_start();
    reportImportFailure($statusCode, $reason);

    return ob_get_clean();
}

test('writes the reason to the information element', function () {
    // Arrange
    $reason = 'Invalid or missing PeriodID.';

    // Act
    $output = captureImportFailure(400, $reason);

    // Assert
    assertSameValue(
        '<script>parent.document.getElementById("information").innerHTML="Invalid or missing PeriodID.";</script>',
        $output
    );
});

test('never writes the completion message, so the page cannot read it as success', function () {
    $output = captureImportFailure(500, 'Error during import: boom');

    assertSameValue(false, strpos($output, 'getElementById("message")'));
});

test('escapes quotes so the reason cannot break out of the script string', function () {
    $output = captureImportFailure(500, 'Bad value "x" in row 3');

    assertContains('innerHTML="Bad value \"x\" in row 3";', $output);
});

test('escapes a closing script tag inside the reason', function () {
    $output = captureImportFailure(500, 'oops </script><script>alert(1)</script>');

    assertSameValue(1, substr_count($output, '</script>'));
});

test('still reports the reason when the response has already started', function () {
    // The test runner has printed output by now, so headers are already sent,
    // as they are once the import has streamed its first progress update.
    assertSameValue(true, headers_sent());

    $output = captureImportFailure(500, 'Error during import: late failure');

    assertContains('Error during import: late failure', $output);
});

finishTests();
