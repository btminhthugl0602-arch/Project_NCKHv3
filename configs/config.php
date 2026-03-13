<?php

// Notification feature flags by business cluster.
// Set to false to stop emitting notifications for that cluster
// without affecting core business flows.
if (!defined('NOTIFICATION_FLAG_EVENT_CLUSTER')) {
	define('NOTIFICATION_FLAG_EVENT_CLUSTER', true);
}

if (!defined('NOTIFICATION_FLAG_GROUP_CLUSTER')) {
	define('NOTIFICATION_FLAG_GROUP_CLUSTER', true);
}

if (!defined('NOTIFICATION_FLAG_SCORING_CLUSTER')) {
	define('NOTIFICATION_FLAG_SCORING_CLUSTER', true);
}

