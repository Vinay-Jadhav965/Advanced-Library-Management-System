<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Handle barcode search for quick transaction
$barcode_book = null;
if (isset($_GET['barcode']) && !empty($_GET['barcode'])) {
    $barcode = mysqli_real_escape_string($con, $_GET['barcode']);
    $barcode_query = "SELECT bc.*, b.title, b.isbn, c.category_name,
                      GROUP_CONCAT(ba.author_name SEPARATOR ', ') as authors
                      FROM book_copies bc
                      JOIN books b ON bc.book_id = b.id
                      LEFT JOIN categories c ON b.category_id = c.id
                      LEFT JOIN book_authors ba ON b.id = ba.book_id
                      WHERE bc.barcode = '$barcode'
                      GROUP BY bc.id";
    $barcode_result = mysqli_query($con, $barcode_query);
    $barcode_book = mysqli_fetch_assoc($barcode_result);
}

// Handle borrow transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'borrow') {
    $member_id = (int)$_POST['member_id'];
    $book_copy_id = (int)$_POST['book_copy_id'];
    
    // Get member type and settings for due date calculation
    $member_query = "SELECT member_type FROM members WHERE id = $member_id";
    $member_result = mysqli_query($con, $member_query);
    $member = mysqli_fetch_assoc($member_result);
    
    $settings_query = "SELECT setting_value FROM settings WHERE setting_key = '" . 
                     ($member['member_type'] == 'student' ? 'student_loan_days' : 'teacher_loan_days') . "'";
    $settings_result = mysqli_query($con, $settings_query);
    $setting = mysqli_fetch_assoc($settings_result);
    $loan_days = (int)$setting['setting_value'];
    
    $due_date = date('Y-m-d', strtotime("+$loan_days days"));
    
    // Insert transaction
    $borrow_query = "INSERT INTO transactions (member_id, book_copy_id, borrow_date, due_date, status) 
                     VALUES ($member_id, $book_copy_id, CURDATE(), '$due_date', 'borrowed')";
    
    if (mysqli_query($con, $borrow_query)) {
        // Update book copy status
        mysqli_query($con, "UPDATE book_copies SET status = 'borrowed' WHERE id = $book_copy_id");
        
        // Update available copies
        $book_copy_query = "SELECT book_id FROM book_copies WHERE id = $book_copy_id";
        $book_copy_result = mysqli_query($con, $book_copy_query);
        $book_copy = mysqli_fetch_assoc($book_copy_result);
        
        mysqli_query($con, "UPDATE books SET available_copies = available_copies - 1 WHERE id = " . $book_copy['book_id']);
        
        $_SESSION['success'] = 'Book borrowed successfully!';
        header('Location: transactions.php');
        exit();
    }
}

// Handle return transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'return') {
    $transaction_id = (int)$_POST['transaction_id'];
    
    // Get transaction details
    $trans_query = "SELECT * FROM transactions WHERE id = $transaction_id";
    $trans_result = mysqli_query($con, $trans_query);
    $transaction = mysqli_fetch_assoc($trans_result);
    
    // Calculate fine if overdue
    $fine = 0;
    if ($transaction['due_date'] < date('Y-m-d')) {
        $days_overdue = (strtotime(date('Y-m-d')) - strtotime($transaction['due_date'])) / (60 * 60 * 24);
        $fine_query = "SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'";
        $fine_result = mysqli_query($con, $fine_query);
        $fine_setting = mysqli_fetch_assoc($fine_result);
        $fine_per_day = (float)$fine_setting['setting_value'];
        $fine = $days_overdue * $fine_per_day;
    }
    
    // Update transaction
    $return_query = "UPDATE transactions SET return_date = CURDATE(), status = 'returned', fine_amount = $fine WHERE id = $transaction_id";
    
    if (mysqli_query($con, $return_query)) {
        // Update book copy status
        mysqli_query($con, "UPDATE book_copies SET status = 'available' WHERE id = " . $transaction['book_copy_id']);
        
        // Update available copies
        $book_copy_query = "SELECT book_id FROM book_copies WHERE id = " . $transaction['book_copy_id'];
        $book_copy_result = mysqli_query($con, $book_copy_query);
        $book_copy = mysqli_fetch_assoc($book_copy_result);
        
        mysqli_query($con, "UPDATE books SET available_copies = available_copies + 1 WHERE id = " . $book_copy['book_id']);
        
        $_SESSION['success'] = 'Book returned successfully!' . ($fine > 0 ? " Fine: $" . number_format($fine, 2) : '');
        header('Location: transactions.php');
        exit();
    }
}

// Get current transactions
$transactions_query = "SELECT t.*, m.name as member_name, m.roll_number, m.member_type,
                       b.title, b.isbn, bc.barcode, bc.copy_number
                       FROM transactions t
                       JOIN members m ON t.member_id = m.id
                       JOIN book_copies bc ON t.book_copy_id = bc.id
                       JOIN books b ON bc.book_id = b.id
                       WHERE t.status = 'borrowed'
                       ORDER BY t.due_date ASC";
$transactions_result = mysqli_query($con, $transactions_query);

// Get members for dropdown
$members_query = "SELECT id, name, roll_number, member_type FROM members WHERE status = 'active' ORDER BY name";
$members_result = mysqli_query($con, $members_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Library Management System</title>
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
                    <h1 class="h2">Book Transactions</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="transaction_history.php" class="btn btn-outline-primary">
                            <i class="fas fa-history me-1"></i>Transaction History
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Barcode Scanner Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="fas fa-barcode me-2"></i>Quick Barcode Scanner
                                </h5>
                                <form method="GET" class="d-flex">
                                    <input type="text" class="form-control me-2" name="barcode" 
                                           placeholder="Scan or enter barcode..." 
                                           value="<?php echo isset($_GET['barcode']) ? htmlspecialchars($_GET['barcode']) : ''; ?>"
                                           autofocus>
                                    <button type="submit" class="btn btn-primary">Search</button>
                                    <?php if (isset($_GET['barcode'])): ?>
                                        <a href="transactions.php" class="btn btn-secondary ms-2">Clear</a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Book Found by Barcode -->
                <?php if ($barcode_book): ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-check-circle me-2"></i>Book Found
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <h6><?php echo htmlspecialchars($barcode_book['title']); ?></h6>
                                            <p class="mb-1">
                                                <strong>Authors:</strong> <?php echo htmlspecialchars($barcode_book['authors'] ?: 'N/A'); ?><br>
                                                <strong>ISBN:</strong> <?php echo htmlspecialchars($barcode_book['isbn']); ?><br>
                                                <strong>Barcode:</strong> <?php echo htmlspecialchars($barcode_book['barcode']); ?><br>
                                                <strong>Copy:</strong> <?php echo $barcode_book['copy_number']; ?><br>
                                                <strong>Status:</strong> 
                                                <span class="badge <?php echo $barcode_book['status'] == 'available' ? 'bg-success' : 'bg-warning'; ?>">
                                                    <?php echo ucfirst($barcode_book['status']); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-4">
                                            <?php if ($barcode_book['status'] == 'available'): ?>
                                                <button class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#borrowModal" 
                                                        data-book-copy-id="<?php echo $barcode_book['id']; ?>">
                                                    <i class="fas fa-hand-holding me-1"></i>Borrow This Book
                                                </button>
                                            <?php else: ?>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    This book is currently borrowed
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif (isset($_GET['barcode']) && !$barcode_book): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-1"></i>
                        No book found with barcode: <?php echo htmlspecialchars($_GET['barcode']); ?>
                    </div>
                <?php endif; ?>

                <!-- Current Transactions Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-exchange-alt me-2"></i>Current Borrowed Books
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Details</th>
                                        <th>Member</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                    <tr class="<?php echo $transaction['due_date'] < date('Y-m-d') ? 'table-warning' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($transaction['title']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Barcode: <?php echo htmlspecialchars($transaction['barcode']); ?> | 
                                                Copy: <?php echo $transaction['copy_number']; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($transaction['member_name']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($transaction['roll_number']); ?> | 
                                                <?php echo ucfirst($transaction['member_type']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($transaction['borrow_date'])); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($transaction['due_date'])); ?>
                                            <?php if ($transaction['due_date'] < date('Y-m-d')): ?>
                                                <br>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['due_date'] < date('Y-m-d') ? 'danger' : 'warning'; ?>">
                                                <?php echo $transaction['due_date'] < date('Y-m-d') ? 'Overdue' : 'Borrowed'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Return this book?')">
                                                <input type="hidden" name="action" value="return">
                                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo me-1"></i>Return
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Borrow Modal -->
    <div class="modal fade" id="borrowModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Borrow Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="borrow">
                        <input type="hidden" name="book_copy_id" id="modalBookCopyId">
                        
                        <div class="mb-3">
                            <label for="member_id" class="form-label">Select Member *</label>
                            <select class="form-control" id="member_id" name="member_id" required>
                                <option value="">Select Member</option>
                                <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['name']); ?> 
                                        (<?php echo htmlspecialchars($member['roll_number']); ?> - <?php echo ucfirst($member['member_type']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-1"></i>Borrow Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pass book copy ID to modal
        document.getElementById('borrowModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const bookCopyId = button.getAttribute('data-book-copy-id');
            document.getElementById('modalBookCopyId').value = bookCopyId;
        });

        // Auto-focus on barcode input
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.querySelector('input[name="barcode"]');
            if (barcodeInput && !barcodeInput.value) {
                barcodeInput.focus();
            }
        });
    </script>
</body>
</html>
