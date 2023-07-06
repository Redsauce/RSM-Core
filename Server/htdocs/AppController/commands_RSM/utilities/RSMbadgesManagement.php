<?php
// Database connection startup
require_once "RSdatabase.php";

// Functions in this file related with the use of badges in RSM
// - RSbadgeExists
// - RSupdateBadgeForUser
// - RScreateBadge
// - RSRS_BADGE

// Check if the badge already exists or not in the database.
function RSbadgeExists($RSbadge, $RSclientID = null)
{
  $query = "SELECT 'RS_BADGE'
    FROM rs_users
    WHERE RS_BADGE = '" . $RSbadge . "'";

  if ($RSclientID != null) {
    // We can limit the search to a single customer.
    $query .= " AND RS_CLIENT_ID = '$RSclientID'";
  }

  $results = RSquery($query);
  if ($results->num_rows > 0) {
    return true;
  }

  return false;
}


// Update the badge for a specific customer of a given user.
function RSupdateBadgeForUser($userID, $RSclientID)
{
  $uniqueBadge = RScreateBadge($RSclientID);
  return RSquery("UPDATE rs_users SET RS_BADGE = '" . $uniqueBadge . "'
    WHERE RS_USER_ID = " . $userID . " AND
    RS_CLIENT_ID = " . $RSclientID . ";");
}


// We can avoid repetition for a specific customer.
function RScreateBadge($RSclientID = null)
{
  do {
    // Compose the badge based on the current time in milliseconds.
    $badge = md5(microtime(true));
  } while (RSbadgeExists($badge, $RSclientID));

  // Return the new badge
  return $badge;
}



// Retrieve the badge of a user from a specific client.
function RSgetBadgeFromUser($userID, $RSclientID)
{
  $results = RSquery("SELECT RS_BADGE FROM rs_users'
    WHERE RS_USER_ID = " . $userID . " AND
    RS_CLIENT_ID = " . $RSclientID . ";");

  // Analyze results
  if ($results && $results->num_rows > 0) {
    $row = $results->fetch_assoc();
    return $row['RS_BADGE'];
  }

  // Query failed or badge not found
  return "";
}
