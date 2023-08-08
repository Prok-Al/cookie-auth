<?php
setcookie("id", '', time() - 3600 * 24 * 30 * 12, "/juicedev/", "prokal.tyt.su", true, true);
setcookie("hash", '', time() - 3600 * 24 * 30 * 12, "/juicedev/", "prokal.tyt.su", true, true);
header("Location: login/");
exit;