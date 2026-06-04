<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Get statistics
$total_books_query = "SELECT COUNT(*) as count FROM books";
$total_books_result = mysqli_query($con, $total_books_query);
$total_books = mysqli_fetch_assoc($total_books_result)['count'];

$total_members_query = "SELECT COUNT(*) as count FROM members WHERE status = 'active'";
$total_members_result = mysqli_query($con, $total_members_query);
$total_members = mysqli_fetch_assoc($total_members_result)['count'];

$books_on_loan_query = "SELECT COUNT(*) as count FROM transactions WHERE status = 'borrowed'";
$books_on_loan_result = mysqli_query($con, $books_on_loan_query);
$books_on_loan = mysqli_fetch_assoc($books_on_loan_result)['count'];

$overdue_books_query = "SELECT COUNT(*) as count FROM transactions WHERE status = 'borrowed' AND due_date < CURDATE()";
$overdue_books_result = mysqli_query($con, $overdue_books_query);
$overdue_books = mysqli_fetch_assoc($overdue_books_result)['count'];

// Get books by category for chart
$category_query = "SELECT c.category_name, COUNT(b.id) as book_count 
                   FROM categories c 
                   LEFT JOIN books b ON c.id = b.category_id 
                   GROUP BY c.id, c.category_name";
$category_result = mysqli_query($con, $category_query);

// Get member types for chart
$member_type_query = "SELECT member_type, COUNT(*) as count 
                      FROM members 
                      WHERE status = 'active' 
                      GROUP BY member_type";
$member_type_result = mysqli_query($con, $member_type_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library Management System</title>
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
                    <h1 class="h2">Dashboard</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Books</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_books; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Members</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_members; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Books on Loan</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $books_on_loan; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-hand-holding fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Overdue Books</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overdue_books; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Books by Category</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Member Types</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="memberChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Books by Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php 
                    $categories = [];
                    $counts = [];
                    while ($row = mysqli_fetch_assoc($category_result)) {
                        $categories[] = $row['category_name'];
                        $counts[] = $row['book_count'];
                    }
                    echo json_encode($categories);
                ?>,
                datasets: [{
                    label: 'Number of Books',
                    data: <?php echo json_encode($counts); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Member Types Chart
        const memberCtx = document.getElementById('memberChart').getContext('2d');
        const memberChart = new Chart(memberCtx, {
            type: 'pie',
            data: {
                labels: <?php 
                    $types = [];
                    $type_counts = [];
                    while ($row = mysqli_fetch_assoc($member_type_result)) {
                        $types[] = ucfirst($row['member_type']);
                        $type_counts[] = $row['count'];
                    }
                    echo json_encode($types);
                ?>,
                datasets: [{
                    data: <?php echo json_encode($type_counts); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>
