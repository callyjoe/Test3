<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header('Location: signup.html');
    exit;
}
include '../config.php';

$lecturer_id = $_SESSION['user_id'];
$school = $_SESSION['school'];
$department = $_SESSION['department'];

// Handle resource upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_resource'])) {
    $title = $_POST['title'];
    $type = $_POST['type'];
    $url = $_POST['url'];

    // For PDF upload, handle file
    if ($type == 'pdf' && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = time() . '_' . basename($_FILES['pdf_file']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target)) {
            $url = $target; // store relative path
        } else {
            $error = "File upload failed.";
        }
    }

    // Validation
    if ($type == 'youtube' && empty($url)) {
        $error = "YouTube URL is required for YouTube resources.";
    } elseif ($type == 'pdf' && empty($_FILES['pdf_file']['name'])) {
        $error = "Please select a PDF file to upload.";
    }

    if (!isset($error)) {
        $query = "INSERT INTO learning_resources (title, type, url, school, department, uploaded_by) 
                  VALUES ('$title', '$type', '$url', '$school', '$department', '$lecturer_id')";
        if (mysqli_query($conn, $query)) {
            $success = "Resource uploaded successfully.";
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

// Handle resource deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $check = mysqli_query($conn, "SELECT * FROM learning_resources WHERE resource_id=$id AND uploaded_by=$lecturer_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM learning_resources WHERE resource_id=$id");
        $success = "Resource deleted.";
    } else {
        $error = "Unauthorized or resource not found.";
    }
    header('Location: lecturer_dashboard.php');
    exit;
}

// Fetch lecturer's resources
$resources = mysqli_query($conn, "SELECT * FROM learning_resources WHERE uploaded_by=$lecturer_id ORDER BY uploaded_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing: border-box; }
        body {
            background: #f5f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            padding: 30px;
        }
        h1 { color: #1e3a8a; margin-bottom: 5px; }
        .subtitle { color: #4b5563; margin-bottom: 25px; border-left: 3px solid #3b82f6; padding-left: 12px; }
        .welcome { background: #e0f2fe; padding: 12px 18px; border-radius: 12px; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #1e40af; }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
        }
        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .resource-list { margin-top: 30px; }
        .resource-item {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 12px;
            margin: 10px 0;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .resource-info i { margin-right: 8px; color: #3b82f6; }
        .delete-btn { color: #dc2626; text-decoration: none; margin-left: 15px; }
        hr { margin: 20px 0; }
        .logout { text-align: right; margin-bottom: 20px; }
        .logout a { color: #64748b; text-decoration: none; }
        .logout a:hover { color: #ef4444; }
        .card { background: #f1f5f9; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="logout">
        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <h1><i class="fas fa-chalkboard-user"></i> Lecturer Dashboard</h1>
    <div class="subtitle">Manage your learning resources and monitor student performance</div>
    <div class="welcome">
        <i class="fas fa-university"></i> <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> | 
        School: <?php echo htmlspecialchars($school); ?> | 
        Department: <?php echo htmlspecialchars($department); ?>
    </div>

    <?php if (isset($success)) echo "<p style='color:green;'><i class='fas fa-check-circle'></i> $success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red;'><i class='fas fa-exclamation-triangle'></i> $error</p>"; ?>

    <!-- Upload Resource Form -->
    <div class="card">
        <h3><i class="fas fa-cloud-upload-alt"></i> Upload Learning Resource</h3>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title / Topic *</label>
                <input type="text" name="title" placeholder="e.g., Introduction to SQL" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" id="resourceType" onchange="toggleFileUrl()">
                    <option value="youtube">YouTube Video</option>
                    <option value="pdf">PDF File</option>
                </select>
            </div>
            <div class="form-group" id="urlGroup">
                <label>YouTube URL</label>
                <input type="url" name="url" placeholder="https://www.youtube.com/watch?v=...">
            </div>
            <div class="form-group" id="fileGroup" style="display:none;">
                <label>PDF File</label>
                <input type="file" name="pdf_file" accept=".pdf">
            </div>
            <button type="submit" name="upload_resource"><i class="fas fa-upload"></i> Upload</button>
        </form>
    </div>

    <!-- My Resources -->
    <div class="resource-list">
        <h3><i class="fas fa-book"></i> My Resources</h3>
        <?php if (mysqli_num_rows($resources) == 0): ?>
            <p>No resources uploaded yet.</p>
        <?php else: ?>
            <?php while ($r = mysqli_fetch_assoc($resources)): ?>
                <div class="resource-item">
                    <div class="resource-info">
                        <i class="fas <?php echo $r['type'] == 'youtube' ? 'fa-youtube' : 'fa-file-pdf'; ?>"></i>
                        <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                        <br><small><?php echo htmlspecialchars($r['url']); ?></small>
                    </div>
                    <div>
                        <a href="view_performance.php?resource_id=<?php echo $r['resource_id']; ?>" style="margin-right:15px;"><i class="fas fa-chart-line"></i> Performance</a>
                        <a href="?delete=<?php echo $r['resource_id']; ?>" class="delete-btn" onclick="return confirm('Delete this resource?')"><i class="fas fa-trash"></i> Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleFileUrl() {
        const type = document.getElementById('resourceType').value;
        const urlGroup = document.getElementById('urlGroup');
        const fileGroup = document.getElementById('fileGroup');
        if (type === 'youtube') {
            urlGroup.style.display = 'block';
            fileGroup.style.display = 'none';
        } else {
            urlGroup.style.display = 'none';
            fileGroup.style.display = 'block';
        }
    }
    // Initial call
    toggleFileUrl();
</script>
</body>
</html>