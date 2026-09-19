<?php
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname   = trim($_POST['fullname']);
    $email      = trim($_POST['email']);
    $lecturer_id = trim($_POST['lecturer_id']);
    $school     = trim($_POST['school']);
    $department = trim($_POST['department']);
    $password   = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Allowed school values — must match student signup exactly
    $allowed_schools = [
        'University of Energy and Natural Resources',
        'Kwame Nkrumah University of Science and Technology (KNUST)',
        'University of Ghana (UG)',
        'University of Cape Coast (UCC)',
        'General Purpose'
    ];

    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($lecturer_id)) {
        $errors[] = "Lecturer / Staff ID is required.";
    }
    if (!in_array($school, $allowed_schools)) {
        $errors[] = "Please select a valid school from the list.";
    }
    if (empty($department)) {
        $errors[] = "Please select a department.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if (empty($errors)) {
        // Check if email already exists in users or has a pending request
        $check_users    = mysqli_query($conn, "SELECT id FROM users WHERE email='" . mysqli_real_escape_string($conn, $email) . "'");
        $check_requests = mysqli_query($conn, "SELECT id FROM lecturer_requests WHERE email='" . mysqli_real_escape_string($conn, $email) . "' AND status != 'rejected'");

        if (mysqli_num_rows($check_users) > 0 || mysqli_num_rows($check_requests) > 0) {
            $errors[] = "This email already exists or has a pending request.";
        }
    }

    if (empty($errors)) {
        $hashed    = password_hash($password, PASSWORD_DEFAULT);
        $fn        = mysqli_real_escape_string($conn, $fullname);
        $em        = mysqli_real_escape_string($conn, $email);
        $lid       = mysqli_real_escape_string($conn, $lecturer_id);
        $sc        = mysqli_real_escape_string($conn, $school);
        $dp        = mysqli_real_escape_string($conn, $department);

        $query = "INSERT INTO lecturer_requests (fullname, email, lecturer_id, school, department, password_hash) 
                  VALUES ('$fn', '$em', '$lid', '$sc', '$dp', '$hashed')";

        if (mysqli_query($conn, $query)) {
            $success = "Request submitted successfully. An admin will review and approve your account.";
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Access Request - Galorem AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #f5f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding: 30px 20px;
        }

        .request-container {
            max-width: 560px;
            width: 100%;
            background: white;
            border-radius: 14px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
            padding: 35px 30px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .brand-icon {
            background: #2563eb;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .brand-text h2 {
            color: #1e3a8a;
            font-size: 1.2rem;
            margin: 0;
        }
        .brand-text span {
            font-size: 0.75rem;
            color: #64748b;
        }

        h2 { color: #1e3a8a; margin-bottom: 6px; }

        .subtitle {
            color: #4b5563;
            margin-bottom: 24px;
            font-size: 0.88rem;
            border-left: 3px solid #3b82f6;
            padding-left: 10px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #1e40af;
            font-size: 0.83rem;
        }
        label i {
            margin-right: 5px;
            color: #3b82f6;
            width: 14px;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            color: #1e293b;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            appearance: none;
            -webkit-appearance: none;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Custom dropdown arrow */
        .select-wrapper {
            position: relative;
        }
        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            font-size: 0.75rem;
        }
        select:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            padding-right: 42px;
        }
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            font-size: 0.95rem;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: #3b82f6; }

        .hint {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 4px;
        }

        button[type="submit"] {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 6px;
        }
        button[type="submit"] i { margin-right: 8px; }
        button[type="submit"]:hover { background: #1d4ed8; }
        button[type="submit"]:active { transform: scale(0.98); }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            line-height: 1.5;
        }
        .alert i { margin-right: 6px; }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 22px 0;
        }

        .footer-links {
            display: flex;
            flex-direction: column;
            gap: 10px;
            text-align: center;
            font-size: 0.85rem;
            color: #4b5563;
        }
        .footer-links a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .footer-links a:hover { text-decoration: underline; }

        .loading-dept {
            color: #94a3b8;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="request-container">

    <!-- Brand Header -->
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-robot"></i></div>
        <div class="brand-text">
            <h2>Galorem AI</h2>
            <span>Smart Study Platform</span>
        </div>
    </div>

    <h2><i class="fas fa-chalkboard-user"></i> Lecturer Access Request</h2>
    <div class="subtitle">
        Fill in the form below to request access to the lecturer portal. 
        Your request will be reviewed and approved by an admin before you can log in.
    </div>

    <!-- Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="post" id="requestForm" novalidate>

        <!-- Full Name -->
        <div class="form-group">
            <label><i class="fas fa-user"></i> Full Name *</label>
            <input type="text" name="fullname" placeholder="e.g., Dr. Kwame Mensah"
                   value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email Address *</label>
            <input type="email" name="email" placeholder="e.g., kwame.mensah@uenr.edu.gh"
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
        </div>

        <!-- Lecturer / Staff ID -->
        <div class="form-group">
            <label><i class="fas fa-id-card"></i> Lecturer / Staff ID *</label>
            <input type="text" name="lecturer_id" placeholder="e.g., UENR/IT/2025"
                   value="<?php echo isset($_POST['lecturer_id']) ? htmlspecialchars($_POST['lecturer_id']) : ''; ?>" required>
        </div>

        <!-- School Dropdown -->
        <div class="form-group">
            <label><i class="fas fa-university"></i> University / School *</label>
            <div class="select-wrapper">
                <select name="school" id="schoolSelect" required>
                    <option value="" disabled selected>-- Select your University / School --</option>

                    <option value="University of Energy and Natural Resources"
                        <?php echo (isset($_POST['school']) && $_POST['school'] === 'University of Energy and Natural Resources') ? 'selected' : ''; ?>>
                        University of Energy and Natural Resources (UENR)
                    </option>

                    <option value="Kwame Nkrumah University of Science and Technology (KNUST)"
                        <?php echo (isset($_POST['school']) && $_POST['school'] === 'Kwame Nkrumah University of Science and Technology (KNUST)') ? 'selected' : ''; ?>>
                        Kwame Nkrumah University of Science and Technology (KNUST)
                    </option>

                    <option value="University of Ghana (UG)"
                        <?php echo (isset($_POST['school']) && $_POST['school'] === 'University of Ghana (UG)') ? 'selected' : ''; ?>>
                        University of Ghana (UG)
                    </option>

                    <option value="University of Cape Coast (UCC)"
                        <?php echo (isset($_POST['school']) && $_POST['school'] === 'University of Cape Coast (UCC)') ? 'selected' : ''; ?>>
                        University of Cape Coast (UCC)
                    </option>

                    <option value="General Purpose"
                        <?php echo (isset($_POST['school']) && $_POST['school'] === 'General Purpose') ? 'selected' : ''; ?>>
                        General Purpose
                    </option>

                    <!-- =============================================
                         ADD MORE SCHOOLS HERE — copy the block below:

                    <option value="EXACT_VALUE_MATCHING_STUDENT_SIGNUP">
                        Display Name
                    </option>

                    Make sure the `value` is identical to what students see
                    in their signup dropdown, character for character.
                    ============================================= -->
                </select>
            </div>
        </div>

        <!-- Department Dropdown (populated dynamically) -->
        <div class="form-group">
            <label><i class="fas fa-building"></i> Department *</label>
            <div class="select-wrapper">
                <select name="department" id="departmentSelect" required disabled>
                    <option value="" disabled selected>-- Select school first --</option>
                </select>
            </div>
            <p class="hint" id="deptHint">Choose a school above to load its departments.</p>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Create Password * <span style="font-weight:400;color:#64748b;">(min. 6 characters)</span></label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label><i class="fas fa-lock"></i> Confirm Password *</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" required>
                <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
            </div>
        </div>

        <button type="submit"><i class="fas fa-paper-plane"></i> Submit Request</button>
    </form>

    <hr class="divider">

    <div class="footer-links">
        <div>
            <i class="fas fa-sign-in-alt"></i>
            Already approved? <a href="lecturer_login.php">Lecturer Login</a>
        </div>
        <div>
            <a href="../signup.html"><i class="fas fa-user-graduate"></i> Student Signup</a>
        </div>
    </div>

</div>

<script>
    // ========== PASSWORD VISIBILITY TOGGLE ==========
    function togglePassword(fieldId, icon) {
        const field = document.getElementById(fieldId);
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    // ========== DYNAMIC DEPARTMENT LOADING ==========
    const schoolSelect     = document.getElementById('schoolSelect');
    const departmentSelect = document.getElementById('departmentSelect');
    const deptHint         = document.getElementById('deptHint');

    schoolSelect.addEventListener('change', function () {
        const selectedSchool = this.value;

        if (!selectedSchool) {
            departmentSelect.innerHTML = '<option value="" disabled selected>-- Select school first --</option>';
            departmentSelect.disabled = true;
            deptHint.textContent = 'Choose a school above to load its departments.';
            return;
        }

        // Show loading state
        departmentSelect.innerHTML = '<option value="" disabled selected>Loading departments...</option>';
        departmentSelect.disabled = true;
        deptHint.textContent = 'Fetching departments...';

        // Fetch departments — same endpoint as student signup
        fetch(`../get_departments.php?school=${encodeURIComponent(selectedSchool)}`)
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                departmentSelect.innerHTML = '<option value="" disabled selected>-- Select Department --</option>';

                if (data.length === 0) {
                    departmentSelect.innerHTML = '<option value="" disabled selected>No departments found</option>';
                    deptHint.textContent = 'No departments are listed for this school yet.';
                } else {
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept;
                        option.textContent = dept;
                        departmentSelect.appendChild(option);
                    });
                    departmentSelect.disabled = false;
                    deptHint.textContent = `${data.length} department(s) loaded. Please select yours.`;
                }

                // Re-select if form was submitted with errors (preserve selection)
                <?php if (isset($_POST['department'])): ?>
                const savedDept = <?php echo json_encode($_POST['department']); ?>;
                if (savedDept) {
                    departmentSelect.value = savedDept;
                }
                <?php endif; ?>
            })
            .catch(err => {
                console.error('Department load error:', err);
                departmentSelect.innerHTML = '<option value="" disabled selected>Error loading departments</option>';
                departmentSelect.disabled = true;
                deptHint.textContent = 'Could not load departments. Please try again.';
            });
    });

    // ========== AUTO-TRIGGER IF SCHOOL WAS ALREADY SELECTED (after form error) ==========
    <?php if (isset($_POST['school']) && !empty($_POST['school'])): ?>
    window.addEventListener('DOMContentLoaded', () => {
        schoolSelect.value = <?php echo json_encode($_POST['school']); ?>;
        schoolSelect.dispatchEvent(new Event('change'));
    });
    <?php endif; ?>
</script>

</body>
</html>