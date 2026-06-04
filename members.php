<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Handle member deletion
if (isset($_GET['delete'])) {
    $member_id = $_GET['delete'];
    mysqli_query($con, "DELETE FROM members WHERE id = $member_id");
    header('Location: members.php');
    exit();
}

// Handle search and pagination
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$member_type = isset($_GET['member_type']) ? mysqli_real_escape_string($con, $_GET['member_type']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
if ($search) {
    $where_conditions[] = "(m.name LIKE '%$search%' OR m.roll_number LIKE '%$search%' OR m.email LIKE '%$search%')";
}
if ($member_type) {
    $where_conditions[] = "m.member_type = '$member_type'";
}
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total members count
$count_query = "SELECT COUNT(*) as total FROM members m $where_clause";
$count_result = mysqli_query($con, $count_query);
$total_members = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_members / $limit);

// Get members with current borrowed books count
$members_query = "SELECT m.*, 
                 (SELECT COUNT(*) FROM transactions t 
                  WHERE t.member_id = m.id AND t.status = 'borrowed') as current_borrows
                 FROM members m 
                 $where_clause
                 ORDER BY m.created_at DESC 
                 LIMIT $limit OFFSET $offset";
$members_result = mysqli_query($con, $members_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Management - Library Management System</title>
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
                    <h1 class="h2">Member Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_member.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add New Member
                        </a>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <form method="GET" class="search-box">
                            <div class="input-group">
                                <span class="input-group-text search-icon">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search members by name, roll number, or email..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <select class="form-control" name="member_type" style="max-width: 150px;">
                                    <option value="">All Types</option>
                                    <option value="student" <?php echo $member_type == 'student' ? 'selected' : ''; ?>>Students</option>
                                    <option value="teacher" <?php echo $member_type == 'teacher' ? 'selected' : ''; ?>>Teachers</option>
                                </select>
                                <button class="btn btn-outline-secondary" type="submit">Search</button>
                                <?php if ($search || $member_type): ?>
                                    <a href="members.php" class="btn btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Members Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Member Details</th>
                                        <th>Contact</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Current Borrows</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($member = mysqli_fetch_assoc($members_result)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                ID: <?php echo $member['roll_number']; ?>
                                                <?php if ($member['member_type'] == 'student'): ?>
                                                    | Semester: <?php echo $member['semester']; ?> | Branch: <?php echo $member['branch']; ?>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($member['email']); ?>
                                            <br>
                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($member['phone']); ?>
                                        </td>
                                        <td>
                                            <?php if ($member['member_type'] == 'student'): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-graduation-cap me-1"></i>Student
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-chalkboard-teacher me-1"></i>Teacher
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($member['status']) {
                                                case 'active':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'inactive':
                                                    $status_class = 'bg-secondary';
                                                    break;
                                                case 'suspended':
                                                    $status_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($member['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $member['current_borrows'] > 0 ? 'bg-warning' : 'bg-light text-dark'; ?>">
                                                <?php echo $member['current_borrows']; ?> books
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view_member.php?id=<?php echo $member['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_member.php?id=<?php echo $member['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="member_history.php?id=<?php echo $member['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" title="History">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                                <a href="members.php?delete=<?php echo $member['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this member?')" 
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Members pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&member_type=<?php echo urlencode($member_type); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&member_type=<?php echo urlencode($member_type); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&member_type=<?php echo urlencode($member_type); ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
