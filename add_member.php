<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../include/dbcon.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_type = mysqli_real_escape_string($con, $_POST['member_type']);
    $roll_number = mysqli_real_escape_string($con, $_POST['roll_number']);
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $branch = mysqli_real_escape_string($con, $_POST['branch']);
    $semester = !empty($_POST['semester']) ? (int)$_POST['semester'] : NULL;
    $address = mysqli_real_escape_string($con, $_POST['address']);
    $registration_date = $_POST['registration_date'];
    $status = mysqli_real_escape_string($con, $_POST['status']);
    
    // Check if roll number or email already exists
    $check_query = "SELECT id FROM members WHERE roll_number = '$roll_number' OR email = '$email'";
    $check_result = mysqli_query($con, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = 'Roll number or email already exists!';
    } else {
        // Insert member
        $query = "INSERT INTO members (member_type, roll_number, name, email, phone, branch, semester, address, registration_date, status) 
                  VALUES ('$member_type', '$roll_number', '$name', '$email', '$phone', '$branch', $semester, '$address', '$registration_date', '$status')";
        
        if (mysqli_query($con, $query)) {
            $_SESSION['success'] = 'Member added successfully!';
            header('Location: members.php');
            exit();
        } else {
            $error = 'Error adding member: ' . mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Member - Library Management System</title>
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
                    <h1 class="h2">Add New Member</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="members.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Members
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="memberForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="member_type" class="form-label">Member Type *</label>
                                        <select class="form-control" id="member_type" name="member_type" required onchange="toggleStudentFields()">
                                            <option value="">Select Type</option>
                                            <option value="student">Student</option>
                                            <option value="teacher">Teacher</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="roll_number" class="form-label">Roll Number / Employee ID *</label>
                                        <input type="text" class="form-control" id="roll_number" name="roll_number" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               placeholder="9876543210">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="registration_date" class="form-label">Registration Date *</label>
                                        <input type="date" class="form-control" id="registration_date" name="registration_date" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Student-specific fields -->
                            <div id="studentFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="branch" class="form-label">Branch/Department</label>
                                            <input type="text" class="form-control" id="branch" name="branch" 
                                                   placeholder="Computer Science">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="semester" class="form-label">Semester</label>
                                            <select class="form-control" id="semester" name="semester">
                                                <option value="">Select Semester</option>
                                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?><?php echo ($i == 1) ? 'st' : (($i == 2) ? 'nd' : (($i == 3) ? 'rd' : 'th')); ?> Semester</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="members.php" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Add Member
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
        function toggleStudentFields() {
            const memberType = document.getElementById('member_type').value;
            const studentFields = document.getElementById('studentFields');
            
            if (memberType === 'student') {
                studentFields.style.display = 'block';
                document.getElementById('branch').required = true;
                document.getElementById('semester').required = true;
            } else {
                studentFields.style.display = 'none';
                document.getElementById('branch').required = false;
                document.getElementById('semester').required = false;
                document.getElementById('branch').value = '';
                document.getElementById('semester').value = '';
            }
        }

        // Form validation
        document.getElementById('memberForm').addEventListener('submit', function(e) {
            const memberType = document.getElementById('member_type').value;
            const rollNumber = document.getElementById('roll_number').value.trim();
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const registrationDate = document.getElementById('registration_date').value;
            
            if (!memberType) {
                e.preventDefault();
                alert('Please select member type');
                return;
            }
            
            if (!rollNumber) {
                e.preventDefault();
                alert('Please enter roll number/employee ID');
                return;
            }
            
            if (!name) {
                e.preventDefault();
                alert('Please enter full name');
                return;
            }
            
            if (!email) {
                e.preventDefault();
                alert('Please enter email address');
                return;
            }
            
            if (!registrationDate) {
                e.preventDefault();
                alert('Please select registration date');
                return;
            }
            
            // Email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });
    </script>
</body>
</html>
