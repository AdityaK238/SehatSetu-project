<?php
session_start();

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "" || $_SESSION['usertype'] != 'a') {
        header("location: ../login.php");
    }
} else {
    header("location: ../login.php");
}

include("../connection.php");

// Handle session schedulings
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_submit'])) {
    $title = $_POST["title"];
    $docid = $_POST["docid"];
    $nop = $_POST["nop"];
    $date = $_POST["date"];
    $time = $_POST["time"];
    
    $sql = "INSERT INTO schedule (docid, title, scheduledate, scheduletime, nop) VALUES (?, ?, ?, ?, ?)";
    $stmt = $database->prepare($sql);
    $stmt->bind_param("isssi", $docid, $title, $date, $time, $nop);
    $stmt->execute();

    header("location: schedule.php?action=session-added&title=" . urlencode($title));
    exit();
}

// Handle reminder sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reminder_submit'])) {
    $patient_id = $_POST["patient_id"];
    $message = $_POST["message"];

    // Assuming a table `reminders` with (id, patient_id, message, sent_time)
    $sql = "INSERT INTO reminders (patient_id, message, sent_time) VALUES (?, ?, NOW())";
    $stmt = $database->prepare($sql);
    // $stmt->bind_param("is", $patient_id, $message);
    // $stmt->execute();

    // Prepare JSON message for GET request as query parameter
    $dataToSend = [
        'patient_id' => $patient_id,
        'message' => $message
    ];
    // $encodedPayload = urlencode($jsonPayload);

    // Endpoint URL with JSON payload as query parameter
    $pythonApiUrl = "https://d08a-34-106-56-39.ngrok-free.app/message"; // Replace with your Python API endpoint
    $ch = curl_init($pythonApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataToSend));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    echo "<script>alert('Reminder sent successfully!');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule and Reminders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

<h2 class="mb-4">Schedule a Session</h2>
<form method="POST" class="mb-5">
    <input type="hidden" name="schedule_submit" value="1">
    <div class="mb-3">
        <label class="form-label">Doctor ID</label>
        <input type="number" name="docid" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Session Title</label>
        <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Number of Patients</label>
        <input type="number" name="nop" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Date</label>
        <input type="date" name="date" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Time</label>
        <input type="time" name="time" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Add Session</button>
</form>

<hr>

<h2 class="mb-4">Send Reminder to Patient</h2>
<form method="POST">
    <input type="hidden" name="reminder_submit" value="1">
    <div class="mb-3">
        <label class="form-label">Search Patient</label>
        <input type="text" name="search" class="form-control mb-2" placeholder="Search by name or email">
        <button type="submit" class="btn btn-secondary mb-3">Search</button>
    </div>

    <div class="mb-3">
        <label class="form-label">Select Patient</label>
        <select name="patient_id" class="form-select" required>
            <option value="">-- Select Patient --</option>
            <?php
                include("../connection.php");

                if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["search"])) {
                    $keyword = $database->real_escape_string($_POST["search"]);
                    $sqlmain = "SELECT pid, pname FROM patient 
                                WHERE pemail LIKE '%$keyword%' 
                                   OR pname LIKE '%$keyword%' 
                                ORDER BY pid DESC";
                } else {
                    $sqlmain = "SELECT pid, pname FROM patient ORDER BY pid DESC";
                }

                $result = $database->query($sqlmain);
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['pid']}'>{$row['pname']} (ID: {$row['pid']})</option>";
                    }
                } else {
                    echo "<option disabled>No patients found</option>";
                }
            ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Message</label>
        <textarea name="message" class="form-control" rows="4" required></textarea>
    </div>

    <button type="submit" class="btn btn-success">Send Reminder</button>
</form>

</body>
</html>
