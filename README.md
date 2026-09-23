# BARBERSHOP.CO — Setup Guide

## 1. Requirements
- XAMPP (Apache + MySQL + PHP 8+)

## 2. Installation Steps

1. Copy the whole `barbershop.co` folder into `C:\xampp\htdocs\` (or your `htdocs` folder).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Click the **SQL** tab.
5. Open the file `mysql/schema.sql` from this project, copy ALL of its contents, paste it into the SQL box, and click **Go**.
   - This creates the `barbershop_database` database with all tables and starter data (5 packages, 3 barbers, 1 admin account).
6. Visit `http://localhost/barbershop.co/` in your browser.

**Already have this project installed and just want the newest update?**
See section 7 (Updating an Existing Installation) below instead of re-importing
`schema.sql` from scratch — that would erase your existing data.

## 3. Default Admin Account

- **Phone Number:** `09999999999`
- **Password:** `Admin123!`

Login at `pages/login.php` — since this account's role is Administrator, it will
automatically redirect to the Admin Dashboard (`admin/dashboard.php`).
**Please change this password after logging in (My Profile → Change Password).**

## 4. Folder Structure

```
barbershop.co/
├── admin/                    → Admin dashboard pages (protected, Administrator only)
├── auth/                     → Form processors (login, register, booking, payment, cancel, refund, etc.)
├── db/                       → Database connection
├── includes/                 → Shared header/footer/config/booking-helpers/guards
├── mysql/
│   ├── schema.sql            → Full database schema + seed data (fresh install)
│   ├── migration_v2.sql      → Run if updating from the very first version
│   ├── migration_v3.sql      → Live Queue columns
│   └── migration_v4.sql      → Refund requests table (run this one if you're up to date otherwise)
├── pages/                    → Customer-facing pages (login, booking, payment, refund request, etc.)
├── scripts/
│   └── live-countdown.js     → Powers every live countdown + page auto-refresh
├── styles/                   → CSS + images
├── uploads/
│   ├── payment_proofs/       → GCash/PayMaya screenshot uploads
│   ├── profile_images/       → Customer profile picture uploads
│   └── refund_proofs/        → Optional proof uploaded with a refund request
└── index.php
```

## 5. What's New in This Version

**Notification bell (customer header)**
Logged-in customers now see a bell icon in the top navigation. It lists:
- their active bookings, with payment status and a live countdown where relevant
- any booking that was Cancelled or marked No Show in the last 7 days
- any refund request they've submitted that's still being reviewed

A red number badge on the bell shows how many items are waiting for their attention.

**Refund requests**
If a customer cancels a booking that was already paid (payment status
Verified), they're now offered a short form — name, email, contact number,
an optional message, and an optional proof-of-payment image — which gets
sent to the admin as a refund request. Skipping is always an option.

**Admin → Requests (new page)**
A new "Requests" item in the admin sidebar (with its own pending-count badge)
lists every refund request with the customer's info, their message, and a
link to their uploaded proof image, plus a "Mark as Resolved" action.

**Barber "No Schedule" warning + one-click fix**
Admin → Barbers now flags any barber who has no weekly schedule at all (which
made them invisible during booking) with a "No Schedule" warning badge and a
"Quick Fix" button that instantly applies the default Mon–Sat, 8AM–7PM
schedule. This also happens automatically now whenever a *new* barber is
added, so this mainly helps fix barbers added before that automatic default
existed.

## 6. Appointment Status Lifecycle (Reference)

```
Pending  →  Confirmed  →  Waiting  →  Completed
   ↓            ↓            ↓
Cancelled    Cancelled    No Show / Cancelled
```

- **Pending** — booked, but payment not yet verified by admin.
- **Confirmed** — payment verified, has a scheduled barber + time, counting
  down to that time on the Live Queue.
- **Waiting** — admin has marked the customer as checked in / their turn has
  come; a 10-minute grace period counts down. If it runs out, the system
  automatically marks it **No Show** and frees the barber.
- **Completed** — admin marks this once the haircut is done. Automatically
  removes it from the Live Queue and frees the barber.
- **Cancelled / No Show** — final. Cannot be changed afterward (the customer
  can always make a brand new booking instead).

## 7. What's Included From Earlier Updates

- **Customers can cancel their own booking** from My Bookings (only while
  Pending/Confirmed/Waiting), with barber auto-release built in.
- **Live countdowns everywhere** (Live Queue, My Bookings, notification bell)
  — formatted cleanly (e.g. `7h 08min : 44sec`), ticking every second, with
  the whole page quietly auto-refreshing every 45 seconds.
- **Type-your-own-time booking** with automatic "closest available time"
  suggestions, and **choose-your-own-barber** with busy barbers disabled.
- **Age verification for online payments** — GCash/PayMaya require 18+,
  checked both client-side and server-side; Cash is used automatically
  otherwise, including for the ₱100 reservation fee.
- **One active booking at a time** per customer.
- Business hours (8AM–7PM), holiday calendar, Reservation fallback (₱100 fee)
  when fully booked, GCash/PayMaya/Cash with proof verification, and the full
  Admin Dashboard (stats, barbers, services, reports, calendar, messages).

## 8. Updating an Existing Installation

Do this instead of re-importing `schema.sql` (which would erase your data):

1. Replace all the project files with this new version.
2. Open phpMyAdmin → SQL tab, and run whichever migration files you haven't
   run yet, **in order**: `migration_v2.sql` → `migration_v3.sql` →
   `migration_v4.sql`. If you're not sure which ones you've already run,
   it's safe to just try each in order — `migration_v4.sql` uses
   `CREATE TABLE IF NOT EXISTS`, so re-running it does nothing harmful.

## 9. Notes for Your Defense / Demo

- **Notification bell demo**: book an appointment, then look at the bell
  icon — it shows "Payment not yet submitted" with a badge count. Verify
  the payment as admin, refresh, and the bell updates to a live countdown.
- **Refund request demo**: verify a payment as admin, then as that customer
  cancel the booking from My Bookings — you'll be taken straight to the
  refund request form. Submit it, then check Admin → Requests to see it
  appear with a pending badge.
- **Barber quick-fix demo**: add a new barber under Admin → Barbers (it will
  already work immediately), or point out the "Quick Fix" button that would
  appear if a barber's schedule were ever accidentally cleared.
- **Payment-gated confirmation demo**: book as a customer — it shows
  "Pending" on My Bookings. Verify the payment as admin — it flips to
  "Confirmed" with a live countdown.
- **Cancel-lock demo**: cancel a booking, then try changing its status from
  the admin side — you'll see it's locked as "Final".
- **Auto no-show demo**: set a booking to "Waiting", then (for a faster demo)
  temporarily edit `WAITING_GRACE_PERIOD_MINUTES` in
  `includes/booking_helpers.php` to `1`, reload after that time passes, and
  watch it auto-flip to "No Show" and free the barber. (Change it back to
  `10` afterward.)
- To simulate a fully-booked day, book several appointments back-to-back for
  one barber, then try booking again at the same time — you'll see the
  automatic Reservation fallback.
- To test the holiday feature, add today's date under Admin → Calendar, then
  try to book — the booking page will show "The Shop Is Closed That Day".
- To test the age restriction, register a test account with a birthdate less
  than 18 years ago, then try to pay — GCash/PayMaya will be disabled.
#   B a r b e r S h o p . C o O n l i n e  
 