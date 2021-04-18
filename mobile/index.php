<?php
/* Copyright (c) Anuko International Ltd. https://www.anuko.com
License: See license.txt */

require_once('../initialize.php');

// Mobile pages are no longer separate. Redirect to main page.
header('Location: ../index.php');
exit();
// Below is no longer used code.

// Redirects for admin and client roles.
if ($auth->isAuthenticated()) {
  if ($user->can('administer_site')) {
    header('Location: ../admin_groups.php');
    exit();
  } elseif ($user->isClient()) {
    header('Location: ../reports.php');
    exit();
  }
}
// Redirect to time.php or mobile/time.php for other roles.
?>

<html>
  <script src="../js/strftime.js"></script>
  <script>
    location.href = "../time.php?date="+(new Date()).strftime('<?php print DB_DATEFORMAT;?>');
  </script>
  <noscript>
    <p>Your browser does not support JavaScript. This application will not work without it.</p>
  </noscript>
</html>
