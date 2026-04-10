<?php
// Add this file to config/notification_helper.php

function createNotification($conn, $user_id, $booking_id, $type, $title, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, booking_id, type, title, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $booking_id, $type, $title, $message);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function sendBookingConfirmationNotification($conn, $booking_id, $user_id, $studio_name, $booking_date, $booking_time) {
    $title = "Booking Confirmed!";
    $message = "Your booking for {$studio_name} on " . date('F j, Y', strtotime($booking_date)) . " at " . date('g:i A', strtotime($booking_time)) . " has been confirmed.";
    return createNotification($conn, $user_id, $booking_id, 'booking_confirmed', $title, $message);
}

function sendPaymentConfirmationNotification($conn, $booking_id, $user_id, $amount) {
    $title = "Payment Confirmed";
    $message = "Your payment of $" . number_format($amount, 2) . " has been received. Thank you!";
    return createNotification($conn, $user_id, $booking_id, 'payment_confirmed', $title, $message);
}

function sendBookingReminderNotification($conn, $booking_id, $user_id, $studio_name, $booking_date, $booking_time) {
    $title = "Booking Reminder";
    $message = "Reminder: You have a booking for {$studio_name} tomorrow at " . date('g:i A', strtotime($booking_time)) . ".";
    return createNotification($conn, $user_id, $booking_id, 'booking_reminder', $title, $message);
}

function sendBookingCompletedNotification($conn, $booking_id, $user_id, $studio_name) {
    $title = "Booking Completed";
    $message = "Your booking for {$studio_name} has been completed. We'd love to hear your feedback!";
    return createNotification($conn, $user_id, $booking_id, 'booking_completed', $title, $message);
}

function sendBookingCancelledNotification($conn, $booking_id, $user_id, $studio_name, $booking_date) {
    $title = "Booking Cancelled";
    $message = "Your booking for {$studio_name} on " . date('F j, Y', strtotime($booking_date)) . " has been cancelled.";
    return createNotification($conn, $user_id, $booking_id, 'booking_cancelled', $title, $message);
}

// Function to send reminders for bookings happening tomorrow
function sendUpcomingBookingReminders($conn) {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $stmt = $conn->query("
        SELECT b.id, b.user_id, b.booking_time, s.name as studio_name, b.booking_date
        FROM bookings b
        JOIN studios s ON b.studio_id = s.id
        WHERE b.booking_date = '{$tomorrow}'
        AND b.status = 'confirmed'
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.booking_id = b.id 
            AND n.type = 'booking_reminder'
        )
    ");
    
    while ($booking = $stmt->fetch_assoc()) {
        sendBookingReminderNotification(
            $conn, 
            $booking['id'], 
            $booking['user_id'], 
            $booking['studio_name'],
            $booking['booking_date'],
            $booking['booking_time']
        );
    }
}

// Function to mark completed bookings and send completion notifications
function processCompletedBookings($conn) {
    $now = date('Y-m-d H:i:s');
    
    $stmt = $conn->query("
        SELECT b.id, b.user_id, b.booking_date, b.booking_time, b.duration, s.name as studio_name
        FROM bookings b
        JOIN studios s ON b.studio_id = s.id
        WHERE b.status = 'confirmed'
        AND DATE_ADD(CONCAT(b.booking_date, ' ', b.booking_time), INTERVAL b.duration HOUR) < '{$now}'
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.booking_id = b.id 
            AND n.type = 'booking_completed'
        )
    ");
    
    while ($booking = $stmt->fetch_assoc()) {
        sendBookingCompletedNotification(
            $conn,
            $booking['id'],
            $booking['user_id'],
            $booking['studio_name']
        );
    }
}
?>