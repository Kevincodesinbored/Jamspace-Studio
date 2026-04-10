<?php
// This file should be run periodically using a cron job
// Example cron: */30 * * * * /usr/bin/php /path/to/cron_notifications.php

require_once 'config/database.php';
require_once 'config/notification_helper.php';

$conn = getDBConnection();

echo "Starting notification cron job...\n";

// Send reminders for bookings happening tomorrow
echo "Checking for upcoming bookings...\n";
sendUpcomingBookingReminders($conn);

// Process completed bookings
echo "Checking for completed bookings...\n";
processCompletedBookings($conn);

echo "Notification cron job completed.\n";

$conn->close();
?>