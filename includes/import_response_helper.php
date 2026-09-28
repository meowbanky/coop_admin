<?php
/**
 * Failure reporting for the Excel contribution import.
 *
 * The import answers with scripts that write into the page's "information" and
 * "message" elements, and upload.php reads those scripts out of the response.
 * A failure writes only "information": the page treats the missing completion
 * "message" as the sign that the import did not finish.
 */

const IMPORT_FAILED_MESSAGE = 'The import could not be completed. Please try again or contact support.';
const IMPORT_UNREADABLE_FILE_MESSAGE = 'The file could not be read. Please upload a valid Excel file.';

/**
 * Returns the message to show the uploader for a failed import. It is always
 * one of the fixed messages above: the error's own text can name tables, file
 * paths or queries, so it belongs in the server log only.
 */
function describeImportFailure(Throwable $error)
{
    if ($error instanceof \PhpOffice\PhpSpreadsheet\Exception) {
        return IMPORT_UNREADABLE_FILE_MESSAGE;
    }

    return IMPORT_FAILED_MESSAGE;
}

/**
 * Reports a failed import: sets the HTTP status when the response has not
 * started yet, and always writes the reason for the page to show.
 *
 * Once the import has streamed a progress update the status can no longer be
 * changed, which is why the page does not rely on the status alone.
 */
function reportImportFailure($statusCode, $reason)
{
    if (!headers_sent()) {
        http_response_code($statusCode);
    }

    echo '<script>parent.document.getElementById("information").innerHTML="'
        . escapeForInlineScript($reason)
        . '";</script>';
}

/**
 * Escapes text for a double-quoted JavaScript string inside a <script> block:
 * quotes and backslashes, line breaks, and "</" so that the text can neither
 * end the string nor close the script element.
 */
function escapeForInlineScript($text)
{
    $singleLine = str_replace(["\r\n", "\r", "\n"], ' ', (string) $text);

    return str_replace('</', '<\/', addslashes($singleLine));
}
