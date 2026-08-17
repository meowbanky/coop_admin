<?php
/**
 * Shared CoopID generation and validation.
 *
 * CoopIDs follow the format COOP-NNNNN (zero-padded to 5 digits). Legacy IDs
 * that do not match this format are ignored when computing the next value.
 */

const COOP_ID_PREFIX = 'COOP-';
const COOP_ID_DIGITS = 5;

/**
 * Builds a CoopID string from its numeric part.
 */
function formatCoopId($number)
{
    return COOP_ID_PREFIX . str_pad((string) $number, COOP_ID_DIGITS, '0', STR_PAD_LEFT);
}

/**
 * Returns the next CoopID in sequence.
 *
 * Ordering is numeric rather than lexicographic so the sequence stays correct
 * past the zero-padding width. Pass $lockForUpdate inside a transaction to make
 * concurrent creates serialise on the highest existing row.
 *
 * @param PDO  $conn
 * @param bool $lockForUpdate
 * @return string
 */
function generateNextCoopID($conn, $lockForUpdate = false)
{
    $sql = "SELECT CoopID FROM tblemployees
            WHERE CoopID LIKE '" . COOP_ID_PREFIX . "%'
            ORDER BY CAST(SUBSTRING(CoopID, " . (strlen(COOP_ID_PREFIX) + 1) . ") AS UNSIGNED) DESC
            LIMIT 1";

    if ($lockForUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $lastCoopID = $stmt->fetchColumn();

    if ($lastCoopID === false) {
        return formatCoopId(1);
    }

    $lastNumber = (int) substr($lastCoopID, strlen(COOP_ID_PREFIX));

    return formatCoopId($lastNumber + 1);
}
