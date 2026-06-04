<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

$book_id = $_GET['id'];

// Get book details
$book_query = "SELECT b.*, c.category_name FROM books b 
               LEFT JOIN categories c ON b.category_id = c.id 
               WHERE b.id = $book_id";
$book_result = mysqli_query($con, $book_query);
$book = mysqli_fetch_assoc($book_result);

// Get book copies with barcodes
$copies_query = "SELECT * FROM book_copies WHERE book_id = $book_id ORDER BY copy_number";
$copies_result = mysqli_query($con, $copies_query);

// Get authors
$authors_query = "SELECT author_name FROM book_authors WHERE book_id = $book_id";
$authors_result = mysqli_query($con, $authors_query);
$authors = [];
while ($author = mysqli_fetch_assoc($authors_result)) {
    $authors[] = $author['author_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Barcodes - Library Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: white;
        }
        
        .barcode-label {
            width: 300px;
            height: 150px;
            border: 2px solid #000;
            padding: 10px;
            margin: 10px;
            float: left;
            page-break-inside: avoid;
            text-align: center;
        }
        
        .barcode-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            height: 30px;
            overflow: hidden;
        }
        
        .barcode-author {
            font-size: 10px;
            color: #666;
            margin-bottom: 10px;
            height: 15px;
            overflow: hidden;
        }
        
        .barcode-image {
            margin: 10px 0;
        }
        
        .barcode-number {
            font-size: 11px;
            font-family: 'Courier New', monospace;
            margin-top: 5px;
        }
        
        .no-print {
            display: block;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                margin: 0;
                padding: 0;
            }
            
            .barcode-label {
                margin: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <h2>Print Barcodes for: <?php echo htmlspecialchars($book['title']); ?></h2>
        <p>
            <strong>Authors:</strong> <?php echo htmlspecialchars(implode(', ', $authors)); ?><br>
            <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?><br>
            <strong>Category:</strong> <?php echo htmlspecialchars($book['category_name']); ?>
        </p>
        
        <div class="mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Barcodes
            </button>
            <a href="books.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Books
            </a>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            These barcode labels are designed to print on standard label paper (3" x 1.5" labels).
            Adjust your printer settings accordingly for best results.
        </div>
    </div>

    <?php
    require_once '../include/barcode_generator.php';
    $barcodeGen = new BarcodeGenerator();
    
    while ($copy = mysqli_fetch_assoc($copies_result)) {
        echo '<div class="barcode-label">';
        echo '<div class="barcode-title">' . htmlspecialchars(substr($book['title'], 0, 50)) . '</div>';
        echo '<div class="barcode-author">' . htmlspecialchars(implode(', ', array_slice($authors, 0, 2))) . '</div>';
        echo '<div class="barcode-image">';
        echo $barcodeGen->getBarcodeHTML($copy['barcode'], 2, 40);
        echo '</div>';
        echo '<div class="barcode-number">' . htmlspecialchars($copy['barcode']) . '</div>';
        echo '</div>';
    }
    ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
