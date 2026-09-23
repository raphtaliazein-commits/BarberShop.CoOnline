<?php

// =====================================================
// SHARED BOOKING AVAILABILITY LOGIC
// Used by both pages/booking.php and pages/confirm_booking.php
// so the two pages can never disagree on what counts as
// "available".
// =====================================================


/**
 * Figure out which exact time slots are open for a given date
 * and service duration.
 *
 * @return array{
 *     status: "available"|"fully_booked"|"closed"|"no_schedule",
 *     holiday_name: string|null,
 *     slots: array<string, string>  "H:i:s" => "h:i A", sorted ascending
 * }
 */
function getBookingAvailability(
    mysqli $databaseConnection,
    string $selectedDate,
    int $serviceDurationMinutes
): array {

    $currentDate = date("Y-m-d");
    $currentTime = date("H:i:s");

    $isToday = ($selectedDate === $currentDate);

    $businessStartTime = BUSINESS_OPENING_TIME;
    $businessEndTime = BUSINESS_CLOSING_TIME;
    $timeIntervalMinutes = BOOKING_TIME_INTERVAL_MINUTES;

    $currentDateTime = new DateTime($currentDate . " " . $currentTime);
    $businessStartDateTime = new DateTime($selectedDate . " " . $businessStartTime);
    $businessEndDateTime = new DateTime($selectedDate . " " . $businessEndTime);


    // =================================================
    // CHECK HOLIDAY
    // =================================================

    $isHoliday = false;
    $holidayName = null;

    $holidayQuery = "SELECT holiday_name FROM holidays WHERE holiday_date = ? LIMIT 1";
    $holidayStatement = mysqli_prepare($databaseConnection, $holidayQuery);
    mysqli_stmt_bind_param($holidayStatement, "s", $selectedDate);
    mysqli_stmt_execute($holidayStatement);
    $holidayResult = mysqli_stmt_get_result($holidayStatement);

    if ($holidayRow = mysqli_fetch_assoc($holidayResult)) {
        $isHoliday = true;
        $holidayName = $holidayRow["holiday_name"];
    }

    mysqli_stmt_close($holidayStatement);


    $bookingStatus = "no_schedule";

    if ($isHoliday) {
        $bookingStatus = "closed";
    } elseif ($isToday && $currentDateTime >= $businessEndDateTime) {
        $bookingStatus = "closed";
    }

    if ($bookingStatus === "closed") {
        return [
            "status" => "closed",
            "holiday_name" => $holidayName,
            "slots" => [],
        ];
    }


    // =================================================
    // GET BARBER SCHEDULES FOR THIS DAY OF THE WEEK
    // =================================================

    $selectedDateObject = new DateTime($selectedDate);
    $selectedDay = $selectedDateObject->format("l");

    $barberSchedules = [];

    $scheduleQuery = "
        SELECT barber_id, schedule_day, start_time, end_time, schedule_status
        FROM barber_schedules
        WHERE schedule_day = ? AND schedule_status = 'Available'
        ORDER BY barber_id ASC
    ";

    $scheduleStatement = mysqli_prepare($databaseConnection, $scheduleQuery);
    mysqli_stmt_bind_param($scheduleStatement, "s", $selectedDay);
    mysqli_stmt_execute($scheduleStatement);
    $scheduleResult = mysqli_stmt_get_result($scheduleStatement);

    while ($barberSchedule = mysqli_fetch_assoc($scheduleResult)) {
        $barberSchedules[] = $barberSchedule;
    }

    mysqli_stmt_close($scheduleStatement);

    if (empty($barberSchedules)) {
        return [
            "status" => "no_schedule",
            "holiday_name" => null,
            "slots" => [],
        ];
    }


    // =================================================
    // GET EXISTING APPOINTMENTS FOR THIS DATE
    // =================================================

    $appointmentQuery = "
        SELECT barber_id, appointment_start_time, appointment_end_time
        FROM appointments
        WHERE appointment_date = ?
        AND appointment_status IN ('Pending', 'Confirmed', 'Waiting')
        AND barber_id IS NOT NULL
    ";

    $appointmentStatement = mysqli_prepare($databaseConnection, $appointmentQuery);
    mysqli_stmt_bind_param($appointmentStatement, "s", $selectedDate);
    mysqli_stmt_execute($appointmentStatement);
    $appointmentResult = mysqli_stmt_get_result($appointmentStatement);

    $existingAppointments = [];

    while ($appointment = mysqli_fetch_assoc($appointmentResult)) {
        $existingAppointments[] = $appointment;
    }

    mysqli_stmt_close($appointmentStatement);


    // =================================================
    // GENERATE TIME SLOTS
    // =================================================

    $availableTimeSlots = [];
    $bookingStatus = "fully_booked";

    foreach ($barberSchedules as $barberSchedule) {

        $barberId = (int) $barberSchedule["barber_id"];

        $scheduleStart = new DateTime($selectedDate . " " . $barberSchedule["start_time"]);
        $scheduleEnd = new DateTime($selectedDate . " " . $barberSchedule["end_time"]);

        if ($scheduleStart < $businessStartDateTime) {
            $scheduleStart = clone $businessStartDateTime;
        }

        if ($scheduleEnd > $businessEndDateTime) {
            $scheduleEnd = clone $businessEndDateTime;
        }

        if ($scheduleStart >= $scheduleEnd) {
            continue;
        }

        $slotStart = clone $scheduleStart;

        if ($isToday && $slotStart <= $currentDateTime) {

            $minutesNow = ((int) $currentDateTime->format("H") * 60) + (int) $currentDateTime->format("i");

            $nextSlotMinutes = ceil($minutesNow / $timeIntervalMinutes) * $timeIntervalMinutes;

            $nextSlotHour = (int) floor($nextSlotMinutes / 60);
            $nextSlotMinute = $nextSlotMinutes % 60;

            if ($nextSlotMinutes >= 19 * 60) {
                continue;
            }

            $slotStart = new DateTime(
                $selectedDate . " " . sprintf("%02d:%02d:00", $nextSlotHour, $nextSlotMinute)
            );
        }

        while (true) {

            $slotEnd = clone $slotStart;
            $slotEnd->modify("+" . $serviceDurationMinutes . " minutes");

            if ($slotEnd > $businessEndDateTime) {
                break;
            }

            if ($slotEnd > $scheduleEnd) {
                break;
            }

            if ($isToday && $slotStart <= $currentDateTime) {
                $slotStart->modify("+" . $timeIntervalMinutes . " minutes");
                continue;
            }

            $barberIsAvailable = true;

            foreach ($existingAppointments as $existingAppointment) {

                if ((int) $existingAppointment["barber_id"] !== $barberId) {
                    continue;
                }

                $existingStart = new DateTime($selectedDate . " " . $existingAppointment["appointment_start_time"]);
                $existingEnd = new DateTime($selectedDate . " " . $existingAppointment["appointment_end_time"]);

                if ($slotStart < $existingEnd && $slotEnd > $existingStart) {
                    $barberIsAvailable = false;
                    break;
                }
            }

            if ($barberIsAvailable) {

                $timeValue = $slotStart->format("H:i:s");
                $timeDisplay = $slotStart->format("h:i A");

                if (!isset($availableTimeSlots[$timeValue])) {
                    $availableTimeSlots[$timeValue] = $timeDisplay;
                }

                $bookingStatus = "available";
            }

            $slotStart->modify("+" . $timeIntervalMinutes . " minutes");
        }
    }

    ksort($availableTimeSlots);

    if (empty($availableTimeSlots)) {
        $bookingStatus = "fully_booked";
    }

    return [
        "status" => $bookingStatus,
        "holiday_name" => null,
        "slots" => $availableTimeSlots,
    ];
}


/**
 * Given the list of open slots (from getBookingAvailability) and
 * the time the customer typed in, find the closest actual open
 * slot. Returns null if there are no open slots at all.
 */
function findClosestAvailableTime(array $availableTimeSlots, string $preferredTime): ?string
{
    if (empty($availableTimeSlots)) {
        return null;
    }

    $preferredSeconds = strtotime("2000-01-01 " . $preferredTime);

    $closestTime = null;
    $closestDifference = PHP_INT_MAX;

    foreach ($availableTimeSlots as $timeValue => $timeDisplay) {

        $slotSeconds = strtotime("2000-01-01 " . $timeValue);
        $difference = abs($slotSeconds - $preferredSeconds);

        if ($difference < $closestDifference) {
            $closestDifference = $difference;
            $closestTime = $timeValue;
        }
    }

    return $closestTime;
}


/**
 * A customer is only allowed ONE active appointment at a time.
 * "Active" means anything that hasn't reached a final state yet
 * (Completed, Cancelled, No Show). Returns the active appointment
 * row (with a bit of extra info for a friendly message) or null
 * if the customer is free to book.
 */
function getCustomerActiveAppointment(mysqli $databaseConnection, int $customerId): ?array
{
    $query = "
        SELECT
            a.appointment_id, a.appointment_code, a.appointment_date,
            a.appointment_status, s.service_name
        FROM appointments a
        INNER JOIN services s ON s.service_id = a.service_id
        WHERE a.customer_id = ?
        AND a.appointment_status NOT IN ('Completed', 'Cancelled', 'No Show')
        ORDER BY a.created_at DESC
        LIMIT 1
    ";

    $statement = mysqli_prepare($databaseConnection, $query);
    mysqli_stmt_bind_param($statement, "i", $customerId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $activeAppointment = mysqli_fetch_assoc($result);
    mysqli_stmt_close($statement);

    return $activeAppointment ?: null;
}


/**
 * For a specific date + exact time slot, work out which barbers
 * are free and which are busy. Used so the customer can pick a
 * specific barber instead of letting the system auto-assign one.
 *
 * @return array<int, array{barber_id:int, barber_name:string, is_available:bool}>
 */
function getBarberAvailabilityAtTime(
    mysqli $databaseConnection,
    string $selectedDate,
    string $timeValue,
    int $serviceDurationMinutes
): array {

    $selectedDateObject = new DateTime($selectedDate);
    $selectedDay = $selectedDateObject->format("l");

    $slotStart = new DateTime($selectedDate . " " . $timeValue);
    $slotEnd = clone $slotStart;
    $slotEnd->modify("+" . $serviceDurationMinutes . " minutes");


    // =================================================
    // ONLY BARBERS SCHEDULED TO WORK THAT DAY, AND WHOSE
    // SHIFT ACTUALLY COVERS THIS TIME RANGE, ARE LISTED
    // =================================================

    $barberQuery = "
        SELECT b.barber_id, b.barber_name
        FROM barbers b
        INNER JOIN barber_schedules bs ON bs.barber_id = b.barber_id
        WHERE b.barber_status = 'Available'
        AND bs.schedule_day = ?
        AND bs.schedule_status = 'Available'
        AND bs.start_time <= ?
        AND bs.end_time >= ?
        ORDER BY b.barber_name ASC
    ";

    $statement = mysqli_prepare($databaseConnection, $barberQuery);

    $slotStartTime = $slotStart->format("H:i:s");
    $slotEndTime = $slotEnd->format("H:i:s");

    mysqli_stmt_bind_param($statement, "sss", $selectedDay, $slotStartTime, $slotEndTime);
    mysqli_stmt_execute($statement);
    $barberResult = mysqli_stmt_get_result($statement);

    $barbers = [];

    while ($barber = mysqli_fetch_assoc($barberResult)) {
        $barbers[] = $barber;
    }

    mysqli_stmt_close($statement);


    // =================================================
    // EXISTING APPOINTMENTS THAT DAY (TO DETECT CONFLICTS)
    // =================================================

    $appointmentQuery = "
        SELECT barber_id, appointment_start_time, appointment_end_time
        FROM appointments
        WHERE appointment_date = ?
        AND appointment_status IN ('Pending', 'Confirmed', 'Waiting')
        AND barber_id IS NOT NULL
    ";

    $statement = mysqli_prepare($databaseConnection, $appointmentQuery);
    mysqli_stmt_bind_param($statement, "s", $selectedDate);
    mysqli_stmt_execute($statement);
    $appointmentResult = mysqli_stmt_get_result($statement);

    $existingAppointments = [];

    while ($appointment = mysqli_fetch_assoc($appointmentResult)) {
        $existingAppointments[] = $appointment;
    }

    mysqli_stmt_close($statement);


    // =================================================
    // MARK EACH BARBER AS AVAILABLE OR BUSY FOR THIS SLOT
    // =================================================

    $result = [];

    foreach ($barbers as $barber) {

        $barberId = (int) $barber["barber_id"];
        $isAvailable = true;

        foreach ($existingAppointments as $existingAppointment) {

            if ((int) $existingAppointment["barber_id"] !== $barberId) {
                continue;
            }

            $existingStart = new DateTime($selectedDate . " " . $existingAppointment["appointment_start_time"]);
            $existingEnd = new DateTime($selectedDate . " " . $existingAppointment["appointment_end_time"]);

            if ($slotStart < $existingEnd && $slotEnd > $existingStart) {
                $isAvailable = false;
                break;
            }
        }

        $result[] = [
            "barber_id" => $barberId,
            "barber_name" => $barber["barber_name"],
            "is_available" => $isAvailable,
        ];
    }

    return $result;
}


/**
 * Minutes a "Waiting" appointment is given before it's treated as
 * a no-show.
 */
define("WAITING_GRACE_PERIOD_MINUTES", 10);


/**
 * If this barber has no other currently-Waiting appointment, free
 * them back up to "Available". Never touches a barber who has been
 * manually marked "Offline" for the day.
 */
function releaseBarberIfFree(mysqli $databaseConnection, ?int $barberId): void
{
    if (!$barberId) {
        return;
    }

    $stillWaitingQuery = "
        SELECT appointment_id FROM appointments
        WHERE barber_id = ? AND appointment_status = 'Waiting'
        LIMIT 1
    ";

    $statement = mysqli_prepare($databaseConnection, $stillWaitingQuery);
    mysqli_stmt_bind_param($statement, "i", $barberId);
    mysqli_stmt_execute($statement);
    $stillHasWaitingCustomer = mysqli_num_rows(mysqli_stmt_get_result($statement)) > 0;
    mysqli_stmt_close($statement);

    if ($stillHasWaitingCustomer) {
        return;
    }

    $releaseQuery = "
        UPDATE barbers SET barber_status = 'Available'
        WHERE barber_id = ? AND barber_status != 'Offline'
    ";

    $statement = mysqli_prepare($databaseConnection, $releaseQuery);
    mysqli_stmt_bind_param($statement, "i", $barberId);
    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
}


/**
 * "Lazy cron": call this at the top of any page that shows live
 * queue/appointment data. Any appointment that has been sitting in
 * "Waiting" longer than the grace period (customer never showed up)
 * is automatically marked "No Show", and its barber is freed up
 * again — no manual admin action or real cron job required.
 */
function autoExpireOverdueWaitingAppointments(mysqli $databaseConnection): void
{
    $overdueQuery = "
        SELECT appointment_id, barber_id
        FROM appointments
        WHERE appointment_status = 'Waiting'
        AND status_updated_at <= (NOW() - INTERVAL " . WAITING_GRACE_PERIOD_MINUTES . " MINUTE)
    ";

    $overdueResult = mysqli_query($databaseConnection, $overdueQuery);
    $overdueAppointments = mysqli_fetch_all($overdueResult, MYSQLI_ASSOC);

    foreach ($overdueAppointments as $overdueAppointment) {

        $updateQuery = "
            UPDATE appointments
            SET appointment_status = 'No Show', status_updated_at = NOW()
            WHERE appointment_id = ?
        ";

        $statement = mysqli_prepare($databaseConnection, $updateQuery);
        mysqli_stmt_bind_param($statement, "i", $overdueAppointment["appointment_id"]);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);

        releaseBarberIfFree($databaseConnection, (int) $overdueAppointment["barber_id"]);
    }
}


/**
 * Builds the list of items shown in the customer's notification bell:
 *   - active bookings (Pending/Confirmed/Waiting), with payment status
 *     and a live countdown where relevant
 *   - recently Cancelled/No Show bookings (last 7 days), so the customer
 *     still sees the notice even after it becomes final
 *   - their own refund requests still awaiting review
 *
 * Each item: icon, title, subtitle, countdown_target (unix timestamp or
 * null), countdown_done_label, badge_class.
 */
function getCustomerNotifications(mysqli $databaseConnection, int $customerId): array
{
    $notifications = [];


    // =================================================
    // ACTIVE BOOKINGS
    // =================================================

    $activeQuery = "
        SELECT
            a.appointment_code, a.appointment_status, a.status_updated_at,
            a.appointment_date, a.appointment_start_time,
            s.service_name,
            (
                SELECT p.payment_status FROM payments p
                WHERE p.appointment_id = a.appointment_id
                ORDER BY p.created_at DESC LIMIT 1
            ) AS latest_payment_status
        FROM appointments a
        INNER JOIN services s ON s.service_id = a.service_id
        WHERE a.customer_id = ?
        AND a.appointment_status IN ('Pending', 'Confirmed', 'Waiting')
        ORDER BY a.appointment_date ASC, a.created_at DESC
        LIMIT 5
    ";

    $statement = mysqli_prepare($databaseConnection, $activeQuery);
    mysqli_stmt_bind_param($statement, "i", $customerId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    while ($row = mysqli_fetch_assoc($result)) {

        $paymentLabel = match ($row["latest_payment_status"]) {
            "Verified" => "Payment confirmed",
            "Pending" => "Payment awaiting verification",
            "Rejected" => "Payment was rejected — please pay again",
            default => "Payment not yet submitted",
        };

        $countdownTarget = null;
        $countdownDoneLabel = null;
        $subtitle = $paymentLabel;

        if ($row["appointment_status"] === "Waiting") {

            $countdownTarget = strtotime($row["status_updated_at"]) + (WAITING_GRACE_PERIOD_MINUTES * 60);
            $countdownDoneLabel = "Time's up";
            $subtitle = "It's your turn — please arrive within";

        } elseif ($row["appointment_status"] === "Confirmed" && $row["appointment_start_time"]) {

            $countdownTarget = strtotime($row["appointment_date"] . " " . $row["appointment_start_time"]);
            $countdownDoneLabel = "Starting now";
            $subtitle = $paymentLabel . " — starts in";
        }

        $notifications[] = [
            "icon" => "bi-calendar-check",
            "title" => $row["service_name"] . " (" . $row["appointment_code"] . ")",
            "subtitle" => $subtitle,
            "countdown_target" => $countdownTarget,
            "countdown_done_label" => $countdownDoneLabel,
            "badge_class" => "status-pending",
        ];
    }

    mysqli_stmt_close($statement);


    // =================================================
    // RECENTLY CANCELLED / NO SHOW (LAST 7 DAYS)
    // =================================================

    $recentTerminalQuery = "
        SELECT a.appointment_code, a.appointment_status, s.service_name
        FROM appointments a
        INNER JOIN services s ON s.service_id = a.service_id
        WHERE a.customer_id = ?
        AND a.appointment_status IN ('Cancelled', 'No Show')
        AND a.status_updated_at >= (NOW() - INTERVAL 7 DAY)
        ORDER BY a.status_updated_at DESC
        LIMIT 5
    ";

    $statement = mysqli_prepare($databaseConnection, $recentTerminalQuery);
    mysqli_stmt_bind_param($statement, "i", $customerId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    while ($row = mysqli_fetch_assoc($result)) {

        $isCancelled = $row["appointment_status"] === "Cancelled";

        $notifications[] = [
            "icon" => $isCancelled ? "bi-x-circle" : "bi-exclamation-circle",
            "title" => $row["service_name"] . " (" . $row["appointment_code"] . ")",
            "subtitle" => $isCancelled
                ? "This booking was cancelled."
                : "Cooldown time ended — please book again or visit us for a walk-in.",
            "countdown_target" => null,
            "countdown_done_label" => null,
            "badge_class" => "status-cancelled",
        ];
    }

    mysqli_stmt_close($statement);


    // =================================================
    // REFUND REQUESTS STILL PENDING REVIEW
    // =================================================

    $refundQuery = "
        SELECT refund_request_id
        FROM refund_requests
        WHERE customer_id = ? AND request_status = 'Pending'
        ORDER BY created_at DESC
        LIMIT 3
    ";

    $statement = mysqli_prepare($databaseConnection, $refundQuery);
    mysqli_stmt_bind_param($statement, "i", $customerId);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);

    while ($row = mysqli_fetch_assoc($result)) {

        $notifications[] = [
            "icon" => "bi-cash-coin",
            "title" => "Refund Request Submitted",
            "subtitle" => "Our staff is reviewing your refund request.",
            "countdown_target" => null,
            "countdown_done_label" => null,
            "badge_class" => "status-pending",
        ];
    }

    mysqli_stmt_close($statement);

    return $notifications;
}

?>
