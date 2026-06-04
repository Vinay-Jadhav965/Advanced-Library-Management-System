<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Handle book deletion
if (isset($_GET['delete'])) {
    $book_id = $_GET['delete'];
    mysqli_query($con, "DELETE FROM books WHERE id = $book_id");
    header('Location: books.php');
    exit();
}

// Handle search and pagination
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$where_clause = $search ? "WHERE b.title LIKE '%$search%' OR b.isbn LIKE '%$search%' OR ba.author_name LIKE '%$search%'" : "";

// Get total books count
$count_query = "SELECT COUNT(DISTINCT b.id) as total FROM books b 
                LEFT JOIN book_authors ba ON b.id = ba.book_id $where_clause";
$count_result = mysqli_query($con, $count_query);
$total_books = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_books / $limit);

// Get books with authors
$books_query = "SELECT DISTINCT b.*, c.category_name, 
                GROUP_CONCAT(ba.author_name SEPARATOR ', ') as authors
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN book_authors ba ON b.id = ba.book_id 
                $where_clause
                GROUP BY b.id 
                ORDER BY b.created_at DESC 
                LIMIT $limit OFFSET $offset";
$books_result = mysqli_query($con, $books_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management - Library Management System</title>
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
                    <h1 class="h2">Book Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_book.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add New Book
                        </a>
                    </div>
                </div>

                <!-- Search Box -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="search-box">
                            <div class="input-group">
                                <span class="input-group-text search-icon">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search books by title, ISBN, or author..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">Search</button>
                                <?php if ($search): ?>
                                    <a href="books.php" class="btn btn-outline-secondary">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Books Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Authors</th>
                                        <th>ISBN</th>
                                        <th>Category</th>
                                        <th>Copies</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($book = mysqli_fetch_assoc($books_result)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($book['publisher']); ?> (<?php echo $book['copyright_year']; ?>)</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['authors'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                        <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                                        <td>
                                            <?php echo $book['available_copies']; ?> / <?php echo $book['total_copies']; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($book['status']) {
                                                case 'new':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'good':
                                                    $status_class = 'bg-primary';
                                                    break;
                                                case 'damaged':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'lost':
                                                    $status_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($book['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="view_book.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_book.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="print_barcodes.php?id=<?php echo $book['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" title="Print Barcodes">
                                                    <i class="fas fa-barcode"></i>
                                                </a>
                                                <a href="books.php?delete=<?php echo $book['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this book?')" 
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
                        <nav aria-label="Books pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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
