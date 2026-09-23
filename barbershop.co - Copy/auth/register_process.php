<?php

session_start();

require_once "../db/database_connection.php";
require_once "authentication_message.php";


// =====================================================
// MAKE SURE THE FORM WAS SUBMITTED USING POST
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../pages/register.php");

    exit;

}


// =====================================================
// GET FORM DATA
// =====================================================

$fullName = trim($_POST["full_name"] ?? "");

$phoneNumber = trim($_POST["phone_number"] ?? "");

$emailAddress = trim($_POST["email_address"] ?? "");

$dateOfBirth = trim($_POST["date_of_birth"] ?? "");

$customerPassword = $_POST["customer_password"] ?? "";

$confirmCustomerPassword =
    $_POST["confirm_customer_password"] ?? "";

$agreedToTerms =
    isset($_POST["agree_to_terms"]);


// =====================================================
// VALIDATE REQUIRED FIELDS
// =====================================================

if (
    empty($fullName) ||
    empty($phoneNumber) ||
    empty($emailAddress) ||
    empty($dateOfBirth) ||
    empty($customerPassword) ||
    empty($confirmCustomerPassword)
) {

setAuthenticationMessage(
    "error",
    "Please complete all required fields."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// VALIDATE TERMS
// =====================================================

if (!$agreedToTerms) {

setAuthenticationMessage(
    "error",
    "You must agree to the Terms and Conditions."
);

header("Location: ../pages/register.php");

exit;
}


// =====================================================
// VALIDATE FULL NAME
// =====================================================

if (strlen($fullName) < 3) {

setAuthenticationMessage(
    "error",
    "Your full name must contain at least 3 characters."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// VALIDATE PHONE NUMBER
// =====================================================

if (!preg_match("/^09[0-9]{9}$/", $phoneNumber)) {

setAuthenticationMessage(
    "error",
    "Please enter a valid Philippine phone number."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// VALIDATE EMAIL
// =====================================================

if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {

setAuthenticationMessage(
    "error",
    "Please enter a valid email address."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// VALIDATE DATE OF BIRTH
// =====================================================

if (
    !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dateOfBirth, $dateOfBirthParts) ||
    !checkdate((int) $dateOfBirthParts[2], (int) $dateOfBirthParts[3], (int) $dateOfBirthParts[1])
) {

setAuthenticationMessage(
    "error",
    "Please enter a valid date of birth."
);

header("Location: ../pages/register.php");

exit;

}

$dateOfBirthObject = new DateTime($dateOfBirth);
$todayObject = new DateTime("today");

if ($dateOfBirthObject > $todayObject) {

setAuthenticationMessage(
    "error",
    "Your date of birth cannot be in the future."
);

header("Location: ../pages/register.php");

exit;

}

$oldestAllowedDateOfBirth = (clone $todayObject)->modify("-100 years");

if ($dateOfBirthObject < $oldestAllowedDateOfBirth) {

setAuthenticationMessage(
    "error",
    "Please enter a valid date of birth."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// VALIDATE PASSWORD
// =====================================================

if (strlen($customerPassword) < 8) {

setAuthenticationMessage(
    "error",
    "Your password must contain at least 8 characters."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// CHECK PASSWORD CONFIRMATION
// =====================================================

if ($customerPassword !== $confirmCustomerPassword) {

setAuthenticationMessage(
    "error",
    "The passwords do not match."
);

header("Location: ../pages/register.php");

exit;

}


// =====================================================
// CHECK IF PHONE NUMBER ALREADY EXISTS
// =====================================================

$checkPhoneQuery = "
    SELECT customer_id
    FROM customers
    WHERE phone_number = ?
    LIMIT 1
";


$phoneStatement = mysqli_prepare(
    $databaseConnection,
    $checkPhoneQuery
);


mysqli_stmt_bind_param(
    $phoneStatement,
    "s",
    $phoneNumber
);


mysqli_stmt_execute($phoneStatement);

$phoneResult =
    mysqli_stmt_get_result($phoneStatement);


if (mysqli_num_rows($phoneResult) > 0) {

setAuthenticationMessage(
    "error",
    "This phone number is already registered."
);

header("Location: ../pages/register.php");

exit;

}


mysqli_stmt_close($phoneStatement);


// =====================================================
// CHECK IF EMAIL ALREADY EXISTS
// =====================================================

$checkEmailQuery = "
    SELECT customer_id
    FROM customers
    WHERE email_address = ?
    LIMIT 1
";


$emailStatement = mysqli_prepare(
    $databaseConnection,
    $checkEmailQuery
);


mysqli_stmt_bind_param(
    $emailStatement,
    "s",
    $emailAddress
);


mysqli_stmt_execute($emailStatement);

$emailResult =
    mysqli_stmt_get_result($emailStatement);


if (mysqli_num_rows($emailResult) > 0) {

setAuthenticationMessage(
    "error",
    "This email address is already registered."
);

header("Location: ../pages/register.php");

exit;

}


mysqli_stmt_close($emailStatement);


// =====================================================
// HASH PASSWORD
// =====================================================

$hashedCustomerPassword = password_hash(
    $customerPassword,
    PASSWORD_DEFAULT
);


// =====================================================
// INSERT CUSTOMER
// =====================================================

$insertCustomerQuery = "
    INSERT INTO customers
    (
        full_name,
        phone_number,
        email_address,
        date_of_birth,
        password_hash,
        customer_role,
        account_status
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        'Customer',
        'Active'
    )
";


$insertCustomerStatement = mysqli_prepare(
    $databaseConnection,
    $insertCustomerQuery
);


mysqli_stmt_bind_param(
    $insertCustomerStatement,
    "sssss",
    $fullName,
    $phoneNumber,
    $emailAddress,
    $dateOfBirth,
    $hashedCustomerPassword
);


if (mysqli_stmt_execute($insertCustomerStatement)) {

    mysqli_stmt_close($insertCustomerStatement);

    mysqli_close($databaseConnection);

    setAuthenticationMessage(
        "success",
        "Account created successfully! You can now login."
    );

    header("Location: ../pages/login.php");

    exit;

}


// =====================================================
// REGISTRATION FAILED
// =====================================================

mysqli_stmt_close($insertCustomerStatement);

mysqli_close($databaseConnection);

setAuthenticationMessage(
    "error",
    "Registration failed. Please try again."
);

header("Location: ../pages/register.php");

exit;

?>