<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories_result = mysqli_query($con, $categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $isbn = mysqli_real_escape_string($con, $_POST['isbn']);
    $publisher = mysqli_real_escape_string($con, $_POST['publisher']);
    $copyright_year = (int)$_POST['copyright_year'];
    $category_id = (int)$_POST['category_id'];
    $total_copies = (int)$_POST['total_copies'];
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $authors = array_filter($_POST['authors']);
    
    // Insert book
    $book_query = "INSERT INTO books (title, isbn, publisher, copyright_year, category_id, total_copies, available_copies, status, description) 
                   VALUES ('$title', '$isbn', '$publisher', $copyright_year, $category_id, $total_copies, $total_copies, '$status', '$description')";
    
    if (mysqli_query($con, $book_query)) {
        $book_id = mysqli_insert_id($con);
        
        // Insert authors
        foreach ($authors as $author) {
            $author = mysqli_real_escape_string($con, $author);
            mysqli_query($con, "INSERT INTO book_authors (book_id, author_name) VALUES ($book_id, '$author')");
        }
        
        // Generate barcodes for each copy
        require_once '../include/barcode_generator.php';
        $barcodeGen = new BarcodeGenerator();
        
        for ($i = 1; $i <= $total_copies; $i++) {
            $barcode = 'LMS-BOOK-' . str_pad($book_id, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            
            // Insert book copy
            mysqli_query($con, "INSERT INTO book_copies (book_id, barcode, copy_number, status) VALUES ($book_id, '$barcode', $i, 'available')");
            
            // Generate barcode image file
            $barcode_filename = "../barcodes/book_{$book_id}_copy_{$i}.png";
            $barcodeGen->saveBarcode($barcode, $barcode_filename);
        }
        
        $_SESSION['success'] = 'Book added successfully!';
        header('Location: books.php');
        exit();
    } else {
        $error = 'Error adding book: ' . mysqli_error($con);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Book - Library Management System</title>
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
                    <h1 class="h2">Add New Book</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="books.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Books
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="bookForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Book Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="isbn" class="form-label">ISBN</label>
                                        <input type="text" class="form-control" id="isbn" name="isbn" 
                                               placeholder="978-0-000-00000-0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="publisher" class="form-label">Publisher</label>
                                        <input type="text" class="form-control" id="publisher" name="publisher">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="copyright_year" class="form-label">Copyright Year</label>
                                        <input type="number" class="form-control" id="copyright_year" name="copyright_year" 
                                               min="1900" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category *</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="total_copies" class="form-label">Total Copies *</label>
                                        <input type="number" class="form-control" id="total_copies" name="total_copies" 
                                               min="1" value="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Book Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="new">New</option>
                                            <option value="good">Good</option>
                                            <option value="damaged">Damaged</option>
                                            <option value="lost">Lost</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Authors</label>
                                <div id="authorsContainer">
                                    <div class="row mb-2">
                                        <div class="col-md-11">
                                            <input type="text" class="form-control" name="authors[]" 
                                                   placeholder="Enter author name">
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removeAuthor(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addAuthor()">
                                    <i class="fas fa-plus me-1"></i>Add Author
                                </button>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="books.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Add Book
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addAuthor() {
            const container = document.getElementById('authorsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'row mb-2';
            newRow.innerHTML = `
                <div class="col-md-11">
                    <input type="text" class="form-control" name="authors[]" placeholder="Enter author name">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAuthor(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }

        function removeAuthor(button) {
            const container = document.getElementById('authorsContainer');
            if (container.children.length > 1) {
                button.closest('.row').remove();
            }
        }

        // Form validation
        document.getElementById('bookForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const categoryId = document.getElementById('category_id').value;
            const totalCopies = document.getElementById('total_copies').value;
            
            if (!title) {
                e.preventDefault();
                alert('Please enter book title');
                return;
            }
            
            if (!categoryId) {
                e.preventDefault();
                alert('Please select a category');
                return;
            }
            
            if (totalCopies < 1) {
                e.preventDefault();
                alert('Total copies must be at least 1');
                return;
            }
        });
    </script>
</body>
</html>
