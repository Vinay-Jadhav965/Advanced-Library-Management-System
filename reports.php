<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Handle report generation
$report_data = [];
$report_title = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['report_type'])) {
    $report_type = $_GET['report_type'];
    $start_date = $_GET['start_date'] ?? date('Y-m-01');
    $end_date = $_GET['end_date'] ?? date('Y-m-d');
    
    switch ($report_type) {
        case 'borrowed':
            $report_title = 'Borrowed Books Report (' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
            $query = "SELECT t.*, m.name as member_name, m.roll_number, m.member_type,
                     b.title, b.isbn, bc.barcode, bc.copy_number
                     FROM transactions t
                     JOIN members m ON t.member_id = m.id
                     JOIN book_copies bc ON t.book_copy_id = bc.id
                     JOIN books b ON bc.book_id = b.id
                     WHERE t.borrow_date BETWEEN '$start_date' AND '$end_date'
                     ORDER BY t.borrow_date DESC";
            $result = mysqli_query($con, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            break;
            
        case 'returned':
            $report_title = 'Returned Books Report (' . date('M d, Y', strtotime($start_date)) . ' - ' . date('M d, Y', strtotime($end_date)) . ')';
            $query = "SELECT t.*, m.name as member_name, m.roll_number, m.member_type,
                     b.title, b.isbn, bc.barcode, bc.copy_number
                     FROM transactions t
                     JOIN members m ON t.member_id = m.id
                     JOIN book_copies bc ON t.book_copy_id = bc.id
                     JOIN books b ON bc.book_id = b.id
                     WHERE t.return_date BETWEEN '$start_date' AND '$end_date'
                     ORDER BY t.return_date DESC";
            $result = mysqli_query($con, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            break;
            
        case 'overdue':
            $report_title = 'Overdue Books Report (as of ' . date('M d, Y') . ')';
            $query = "SELECT t.*, m.name as member_name, m.roll_number, m.member_type,
                     b.title, b.isbn, bc.barcode, bc.copy_number,
                     DATEDIFF(CURDATE(), t.due_date) as days_overdue
                     FROM transactions t
                     JOIN members m ON t.member_id = m.id
                     JOIN book_copies bc ON t.book_copy_id = bc.id
                     JOIN books b ON bc.book_id = b.id
                     WHERE t.status = 'borrowed' AND t.due_date < CURDATE()
                     ORDER BY t.due_date ASC";
            $result = mysqli_query($con, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                // Calculate fine
                $fine_query = "SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'";
                $fine_result = mysqli_query($con, $fine_query);
                $fine_setting = mysqli_fetch_assoc($fine_result);
                $fine_per_day = (float)$fine_setting['setting_value'];
                $row['fine_amount'] = $row['days_overdue'] * $fine_per_day;
                $report_data[] = $row;
            }
            break;
            
        case 'member':
            $member_id = (int)$_GET['member_id'];
            $member_query = "SELECT name, roll_number, member_type FROM members WHERE id = $member_id";
            $member_result = mysqli_query($con, $member_query);
            $member = mysqli_fetch_assoc($member_result);
            
            $report_title = 'Transaction History for ' . htmlspecialchars($member['name']) . 
                           ' (' . htmlspecialchars($member['roll_number']) . ')';
            
            $query = "SELECT t.*, b.title, b.isbn, bc.barcode, bc.copy_number
                     FROM transactions t
                     JOIN book_copies bc ON t.book_copy_id = bc.id
                     JOIN books b ON bc.book_id = b.id
                     WHERE t.member_id = $member_id
                     ORDER BY t.borrow_date DESC";
            $result = mysqli_query($con, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $report_data[] = $row;
            }
            break;
    }
}

// Get members for dropdown
$members_query = "SELECT id, name, roll_number FROM members ORDER BY name";
$members_result = mysqli_query($con, $members_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if (!empty($report_data)): ?>
                            <button onclick="window.print()" class="btn btn-outline-primary me-2">
                                <i class="fas fa-print me-1"></i>Print Report
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Report Generation Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Generate Report
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="reportForm">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="report_type" class="form-label">Report Type *</label>
                                        <select class="form-control" id="report_type" name="report_type" required onchange="toggleReportFields()">
                                            <option value="">Select Report Type</option>
                                            <option value="borrowed">Borrowed Books</option>
                                            <option value="returned">Returned Books</option>
                                            <option value="overdue">Overdue Books</option>
                                            <option value="member">Member History</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div id="dateFields">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                                   value="<?php echo date('Y-m-01'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                                   value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="memberField" style="display: none;">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="member_id" class="form-label">Select Member</label>
                                            <select class="form-control" id="member_id" name="member_id">
                                                <option value="">Select Member</option>
                                                <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                                                    <option value="<?php echo $member['id']; ?>">
                                                        <?php echo htmlspecialchars($member['name']); ?> 
                                                        (<?php echo htmlspecialchars($member['roll_number']); ?>)
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-file-alt me-1"></i>Generate Report
                                            </button>
                                            <?php if (!empty($report_data)): ?>
                                                <a href="reports.php" class="btn btn-secondary ms-2">Clear</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Results -->
                <?php if (!empty($report_data)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo $report_title; ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($report_data)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    No records found for the selected criteria.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <?php if ($_GET['report_type'] == 'member'): ?>
                                                    <th>Book Details</th>
                                                    <th>Borrow Date</th>
                                                    <th>Due Date</th>
                                                    <th>Return Date</th>
                                                    <th>Status</th>
                                                    <th>Fine</th>
                                                <?php else: ?>
                                                    <th>Book Details</th>
                                                    <th>Member</th>
                                                    <?php if ($_GET['report_type'] == 'overdue'): ?>
                                                        <th>Days Overdue</th>
                                                        <th>Fine</th>
                                                    <?php else: ?>
                                                        <th><?php echo $_GET['report_type'] == 'borrowed' ? 'Borrow' : 'Return'; ?> Date</th>
                                                        <th>Due Date</th>
                                                        <th>Status</th>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Barcode: <?php echo htmlspecialchars($row['barcode']); ?> | 
                                                        Copy: <?php echo $row['copy_number']; ?>
                                                    </small>
                                                </td>
                                                
                                                <?php if ($_GET['report_type'] != 'member'): ?>
                                                    <td>
                                                        <?php echo htmlspecialchars($row['member_name']); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($row['roll_number']); ?> | 
                                                            <?php echo ucfirst($row['member_type']); ?>
                                                        </small>
                                                    </td>
                                                <?php endif; ?>
                                                
                                                <?php if ($_GET['report_type'] == 'overdue'): ?>
                                                    <td>
                                                        <span class="badge bg-danger"><?php echo $row['days_overdue']; ?> days</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning">$<?php echo number_format($row['fine_amount'], 2); ?></span>
                                                    </td>
                                                <?php else: ?>
                                                    <td><?php echo date('M d, Y', strtotime($row['borrow_date'])); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                                    
                                                    <?php if ($_GET['report_type'] == 'member'): ?>
                                                        <td>
                                                            <?php echo $row['return_date'] ? date('M d, Y', strtotime($row['return_date'])) : '-'; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    
                                                    <td>
                                                        <span class="badge bg-<?php echo $row['status'] == 'returned' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($row['status']); ?>
                                                        </span>
                                                    </td>
                                                    
                                                    <?php if ($_GET['report_type'] == 'member'): ?>
                                                        <td>
                                                            <?php if ($row['fine_amount'] > 0): ?>
                                                                <span class="badge bg-warning">$<?php echo number_format($row['fine_amount'], 2); ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light text-dark">$0.00</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Summary Statistics -->
                                <div class="row mt-4">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <strong>Summary:</strong> 
                                            Total Records: <?php echo count($report_data); ?>
                                            <?php if ($_GET['report_type'] == 'overdue'): ?>
                                                | Total Fine Amount: $<?php echo number_format(array_sum(array_column($report_data, 'fine_amount')), 2); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleReportFields() {
            const reportType = document.getElementById('report_type').value;
            const dateFields = document.getElementById('dateFields');
            const memberField = document.getElementById('memberField');
            
            if (reportType === 'member') {
                dateFields.style.display = 'none';
                memberField.style.display = 'block';
                document.getElementById('member_id').required = true;
            } else if (reportType === 'overdue') {
                dateFields.style.display = 'none';
                memberField.style.display = 'none';
                document.getElementById('member_id').required = false;
            } else if (reportType) {
                dateFields.style.display = 'block';
                memberField.style.display = 'none';
                document.getElementById('member_id').required = false;
            } else {
                dateFields.style.display = 'block';
                memberField.style.display = 'none';
                document.getElementById('member_id').required = false;
            }
        }

        // Form validation
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const reportType = document.getElementById('report_type').value;
            
            if (!reportType) {
                e.preventDefault();
                alert('Please select a report type');
                return;
            }
            
            if (reportType === 'member') {
                const memberId = document.getElementById('member_id').value;
                if (!memberId) {
                    e.preventDefault();
                    alert('Please select a member');
                    return;
                }
            }
        });
    </script>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            .card {
                box-shadow: none;
                border: 1px solid #dee2e6;
            }
            
            .btn {
                display: none !important;
            }
        }
    </style>
</body>
</html>
