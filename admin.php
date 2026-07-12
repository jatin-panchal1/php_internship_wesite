<?php
session_start();
require_once 'connection.php'; 

if (!isset($_SESSION['user_email'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'] ?? '';
if ($role !== 'admin') {

    if ($role === 'superadmin') {
        header("Location: superadmin.php");
    } else {
        header("Location: home.php"); 
    }
    exit();
}


$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $name     = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');
    $age      = (int) ($_POST['age'] ?? 0);
  
    $new_role = 'member';

    if (empty($email) || empty($name) || empty($password) || empty($phone) || $age < 1) {
        $message = "All fields are required and age must be valid.";
        $message_type = 'danger';
    } else {
  
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $message = "Email already registered.";
            $message_type = 'danger';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, name, password_hash, phone_number, age, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssis", $email, $name, $hashed, $phone, $age, $new_role);
            if ($stmt->execute()) {
                $message = "New member added successfully.";
                $message_type = 'success';
            } else {
                $message = "Error adding user: " . $stmt->error;
                $message_type = 'danger';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Appointment flash messages (only if no POST action already set a message)
if (empty($message) && isset($_SESSION['appt_message'])) {
    $message = $_SESSION['appt_message'];
    $message_type = $_SESSION['appt_message_type'];
    unset($_SESSION['appt_message'], $_SESSION['appt_message_type']);
}

$users = [];
$result = $conn->query("SELECT email, name, phone_number, age, role, created_at FROM users ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}

// Fetch all appointments
$appointments = [];
$appt_result = $conn->query("SELECT a.id, a.user_email, a.title, a.description, a.appointment_date, a.appointment_time, a.status, a.admin_remarks, a.created_at FROM appointments a ORDER BY a.created_at DESC");
if ($appt_result) {
    while ($row = $appt_result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $appt_result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #f7f8fa;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1d1d1f;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e9eaee;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .navbar-brand {
            font-weight: 600;
            letter-spacing: -0.3px;
        }
        .main-container {
            flex: 1;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .card {
            border: 1px solid #e9eaee;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
            background: #ffffff;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e9eaee;
            font-weight: 600;
            padding: 1.2rem 1.5rem;
        }
        .table th {
            font-weight: 500;
            color: #3a3a3c;
            border-bottom: 1px solid #dcdde3;
        }
        .table td {
            vertical-align: middle;
        }
        .btn-primary {
            background: #1d1d1f;
            border-color: #1d1d1f;
        }
        .btn-primary:hover {
            background: #2c2c2e;
            border-color: #2c2c2e;
        }
        .btn-outline-secondary {
            color: #1d1d1f;
            border-color: #dcdde3;
        }
        .btn-outline-secondary:hover {
            background: #f1f1f3;
        }
        .footer {
            background: #ffffff;
            border-top: 1px solid #e9eaee;
            padding: 1.2rem 0;
            color: #86868b;
            font-size: 0.85rem;
            text-align: center;
        }
        .badge-role {
            font-weight: 500;
            padding: 0.35rem 0.65rem;
            border-radius: 30px;
        }
        .badge-superadmin { background: #c0392b; color: #fff; }
        .badge-admin      { background: #2980b9; color: #fff; }
        .badge-member     { background: #7f8c8d; color: #fff; }
        .modal-content {
            border-radius: 14px;
            border: 1px solid #e9eaee;
        }
        .modal-header {
            border-bottom: 1px solid #e9eaee;
        }
        .modal-footer {
            border-top: 1px solid #e9eaee;
        }
        .badge-status {
            font-weight: 500;
            padding: 0.3rem 0.6rem;
            border-radius: 30px;
            font-size: 0.78rem;
        }
        .badge-pending   { background: #7f8c8d; color: #fff; }
        .badge-approved  { background: #2980b9; color: #fff; }
        .badge-rejected  { background: #c0392b; color: #fff; }
        .badge-completed { background: #27ae60; color: #fff; }
        .nav-card {
            cursor: pointer;
            transition: border-color 0.2s ease;
            border: 2px solid transparent;
        }
        .nav-card:hover { border-color: #dcdde3; }
        .nav-card.active { border-color: #1d1d1f; }
        .nav-card i { color: #86868b; }
        .nav-card.active i { color: #1d1d1f; }
        .search-box { max-width: 260px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-user-cog me-2" style="color: #1d1d1f;"></i>Admin Panel
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="me-3 text-secondary" style="font-size:0.9rem;">
                <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['user_email']); ?>
                <span class="badge bg-dark ms-1">admin</span>
            </span>
            <a href="logout.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container main-container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Navigation Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-6">
            <div class="card nav-card active" onclick="switchSection('users')" id="navCardUsers">
                <div class="card-body text-center py-3">
                    <i class="fas fa-users d-block mb-2" style="font-size:1.5rem;"></i>
                    <h6 class="mb-1">Users</h6>
                    <span class="text-muted" style="font-size:0.85rem;"><?php echo count($users); ?> total</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card nav-card" onclick="switchSection('appointments')" id="navCardAppointments">
                <div class="card-body text-center py-3">
                    <i class="fas fa-calendar-alt d-block mb-2" style="font-size:1.5rem;"></i>
                    <h6 class="mb-1">Appointments</h6>
                    <span class="text-muted" style="font-size:0.85rem;"><?php echo count($appointments); ?> total</span>
                </div>
            </div>
        </div>
    </div>

    <div id="usersSection">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>All Users</span>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm search-box">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" id="userSearch" placeholder="Search by email..." oninput="searchTable('userSearch', 'usersTable')">
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus"></i> Add
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Age</th>
                            <th>Role</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['phone_number']); ?></td>
                                    <td><?php echo (int) $u['age']; ?></td>
                                    <td>
                                        <?php
                                        $role_class = '';
                                        if ($u['role'] === 'superadmin') $role_class = 'badge-superadmin';
                                        elseif ($u['role'] === 'admin') $role_class = 'badge-admin';
                                        else $role_class = 'badge-member';
                                        ?>
                                        <span class="badge-role <?php echo $role_class; ?>">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <div id="appointmentsSection" style="display:none;">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-calendar-alt me-2"></i>Appointment Requests</span>
            <div class="input-group input-group-sm search-box">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="apptSearch" placeholder="Search by email..." oninput="searchTable('apptSearch', 'appointmentsTable')">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($appointments) > 0): ?>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['user_email']); ?></td>
                                    <td><?php echo htmlspecialchars($a['title']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($a['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($a['appointment_time'])); ?></td>
                                    <td>
                                        <span class="badge-status badge-<?php echo $a['status']; ?>">
                                            <?php echo ucfirst($a['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($a['admin_remarks'] ?? '—'); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#statusModal"
                                                data-id="<?php echo $a['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($a['title']); ?>"
                                                data-status="<?php echo $a['status']; ?>"
                                                data-remarks="<?php echo htmlspecialchars($a['admin_remarks'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        &copy; <?php echo date('Y'); ?> Your Company. All rights reserved.
    </div>
</footer>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="addEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="addEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="addName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="addName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="addPassword" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="addPhone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="addPhone" name="phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="addAge" class="form-label">Age</label>
                        <input type="number" class="form-control" id="addAge" name="age" min="1" max="120" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="member" disabled>
                        <small class="text-muted">Only members can be created by admin.</small>
                        <input type="hidden" name="role" value="member">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="appointment_actions.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" id="statusApptId">
                <input type="hidden" name="redirect" value="admin.php">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>Update Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Appointment: <strong id="statusApptTitle"></strong></p>
                    <div class="mb-3">
                        <label for="statusSelect" class="form-label">Status</label>
                        <select class="form-select" id="statusSelect" name="status" required>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusRemarks" class="form-label">Remarks <small class="text-muted">(optional)</small></label>
                        <textarea class="form-control" id="statusRemarks" name="admin_remarks" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchSection(section) {
    document.getElementById('usersSection').style.display = section === 'users' ? '' : 'none';
    document.getElementById('appointmentsSection').style.display = section === 'appointments' ? '' : 'none';
    document.getElementById('navCardUsers').classList.toggle('active', section === 'users');
    document.getElementById('navCardAppointments').classList.toggle('active', section === 'appointments');
}

function searchTable(inputId, tableId) {
    const query = document.getElementById(inputId).value.toLowerCase();
    const rows = document.getElementById(tableId).querySelectorAll('tbody tr');
    rows.forEach(row => {
        const emailCell = row.querySelector('td:first-child');
        if (emailCell) {
            row.style.display = emailCell.textContent.toLowerCase().includes(query) ? '' : 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('statusModal');
    statusModal.addEventListener('show.bs.modal', function(event) {
        const btn = event.relatedTarget;
        document.getElementById('statusApptId').value = btn.getAttribute('data-id');
        document.getElementById('statusApptTitle').textContent = btn.getAttribute('data-title');
        document.getElementById('statusRemarks').value = btn.getAttribute('data-remarks');
        const currentStatus = btn.getAttribute('data-status');
        const sel = document.getElementById('statusSelect');
        if (currentStatus === 'pending') sel.value = 'approved';
        else sel.value = currentStatus;
    });
});
</script>

</body>
</html>