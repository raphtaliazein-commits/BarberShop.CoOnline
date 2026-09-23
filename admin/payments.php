<?php

require_once __DIR__ . "/../includes/admin_guard.php";
require_once __DIR__ . "/../db/database_connection.php";

$pageTitle = "Payments";
$activeAdminPage = "payments";

$statusFilter = $_GET["status"] ?? "Pending";

$whereSql = "";
$params = [];
$paramTypes = "";

if ($statusFilter !== "All") {
    $whereSql = "WHERE p.payment_status = ?";
    $params[] = $statusFilter;
    $paramTypes .= "s";
}

$paymentsQuery = "
    SELECT
        p.payment_id, p.payment_method, p.payment_purpose, p.payment_amount,
        p.payment_reference_number, p.payment_proof_image, p.payment_status,
        p.created_at,
        a.appointment_code, a.appointment_date,
        c.full_name AS customer_name, c.phone_number
    FROM payments p
    INNER JOIN appointments a ON a.appointment_id = p.appointment_id
    INNER JOIN customers c ON c.customer_id = a.customer_id
    $whereSql
    ORDER BY p.created_at DESC
    LIMIT 150
";

$statement = mysqli_prepare($databaseConnection, $paymentsQuery);

if (!empty($params)) {
    mysqli_stmt_bind_param($statement, $paramTypes, ...$params);
}

mysqli_stmt_execute($statement);
$paymentsResult = mysqli_stmt_get_result($statement);

require_once __DIR__ . "/../includes/admin_header.php";
?>

<div class="admin-panel">

    <div class="admin-panel-header">
        <h3><i class="bi bi-funnel"></i> Filter by Status</h3>
    </div>

    <div class="admin-filter-tabs">
        <?php foreach (["Pending", "Verified", "Rejected", "All"] as $option): ?>
            <a href="payments.php?status=<?php echo $option; ?>" class="admin-filter-tab <?php echo $statusFilter === $option ? "active" : ""; ?>">
                <?php echo $option; ?>
            </a>
        <?php endforeach; ?>
    </div>

</div>


<div class="admin-panel mt-4">

    <div class="admin-panel-header">
        <h3><i class="bi bi-credit-card"></i> Payments</h3>
    </div>

    <div class="admin-table-wrapper">

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Appointment</th>
                    <th>Method</th>
                    <th>Purpose</th>
                    <th>Amount</th>
                    <th>Reference</th>
                    <th>Proof</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php if (mysqli_num_rows($paymentsResult) === 0): ?>
                    <tr><td colspan="10" class="text-center">No payments found.</td></tr>
                <?php endif; ?>

                <?php while ($payment = mysqli_fetch_assoc($paymentsResult)): ?>

                    <tr>
                        <td><?php echo date("M d, Y h:i A", strtotime($payment["created_at"])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($payment["customer_name"]); ?>
                            <br><small style="color:#888;"><?php echo htmlspecialchars($payment["phone_number"]); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($payment["appointment_code"]); ?></td>
                        <td><?php echo htmlspecialchars($payment["payment_method"]); ?></td>
                        <td><?php echo htmlspecialchars($payment["payment_purpose"]); ?></td>
                        <td>&#8369;<?php echo number_format((float) $payment["payment_amount"], 2); ?></td>
                        <td><?php echo $payment["payment_reference_number"] ? htmlspecialchars($payment["payment_reference_number"]) : "&mdash;"; ?></td>
                        <td>

                            <?php if ($payment["payment_proof_image"]): ?>

                                <a
                                    href="../uploads/payment_proofs/<?php echo htmlspecialchars($payment["payment_proof_image"]); ?>"
                                    target="_blank"
                                    class="admin-btn admin-btn-small"
                                >
                                    <i class="bi bi-image"></i> View
                                </a>

                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>

                        </td>
                        <td>
                            <span class="status-badge <?php echo $payment["payment_status"] === "Verified" ? "status-confirmed" : ($payment["payment_status"] === "Rejected" ? "status-cancelled" : "status-pending"); ?>">
                                <?php echo htmlspecialchars($payment["payment_status"]); ?>
                            </span>
                        </td>
                        <td>

                            <?php if ($payment["payment_status"] === "Pending"): ?>

                                <form method="POST" action="../auth/admin_manage_payment.php" class="d-inline">
                                    <input type="hidden" name="payment_id" value="<?php echo (int) $payment["payment_id"]; ?>">
                                    <input type="hidden" name="decision" value="Verified">
                                    <button type="submit" class="admin-btn admin-btn-small admin-btn-primary">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </form>

                                <form method="POST" action="../auth/admin_manage_payment.php" class="d-inline">
                                    <input type="hidden" name="payment_id" value="<?php echo (int) $payment["payment_id"]; ?>">
                                    <input type="hidden" name="decision" value="Rejected">
                                    <button type="submit" class="admin-btn admin-btn-small admin-btn-danger">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>

                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endwhile; ?>

            </tbody>
        </table>

    </div>

</div>

<?php require_once __DIR__ . "/../includes/admin_footer.php"; ?>
