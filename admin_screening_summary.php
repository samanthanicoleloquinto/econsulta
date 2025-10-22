<?php
session_start();

// DB connection
$conn = new mysqli("localhost", "root", "", "econsulta");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user_id from URL (admin selects user to view the summary)
$user_id = $_GET['user_id'] ?? null;

if ($user_id === null) {
    echo "No user ID provided.";
    exit();
}

// Fetch latest screening data for the user
$screening_stmt = $conn->prepare("SELECT * FROM screening_data WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$screening_stmt->bind_param("i", $user_id);
$screening_stmt->execute();
$screening_result = $screening_stmt->get_result();
$latest_screening = $screening_result->fetch_assoc();

// Handle form submission (if applicable)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        // Update user data in the database
        $first_name = $_POST['first_name'];
        $middle_initial = $_POST['middle_initial'];
        $last_name = $_POST['last_name'];
        $suffix = $_POST['suffix'];
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $email = $_POST['email'];
        $marital_status = $_POST['marital_status'];
        $contact_number = $_POST['contact_number'];
        $emergency_contact = $_POST['emergency_contact'];
        $relationship = $_POST['relationship'];
        $address = $_POST['address'];
        $guardian_name = $_POST['guardian_name'];
        $guardian_relationship = $_POST['guardian_relationship'];
        $guardian_contact = $_POST['guardian_contact'];
        $medical_conditions = $_POST['medical_conditions'];
        $medications = $_POST['medications'];
        $allergies_meds = $_POST['allergies_meds'];
        $allergies_foods = $_POST['allergies_foods'];
        $foods_allergy_details = $_POST['foods_allergy_details'];
        $symptoms_selected = $_POST['symptoms_selected'];
        $symptoms_others = $_POST['symptoms_others'];
        $temperature = $_POST['temperature'];
        $blood_pressure = $_POST['blood_pressure'];
        $height = $_POST['height'];
        $weight = $_POST['weight'];
        $bmi = $_POST['bmi'];
        $medical_concern = $_POST['medical_concern'];
        $returning_patient = $_POST['returning_patient'];
        $preferred_schedule = $_POST['preferred_schedule'];
        $health_concern = $_POST['health_concern'];
        $status = $_POST['status'];

        $update_stmt = $conn->prepare("UPDATE screening_data SET first_name = ?, middle_initial = ?, last_name = ?, suffix = ?, dob = ?, gender = ?, email = ?, marital_status = ?, contact_number = ?, emergency_contact = ?, relationship = ?, address = ?, guardian_name = ?, guardian_relationship = ?, guardian_contact = ?, medical_conditions = ?, medications = ?, allergies_meds = ?, allergies_foods = ?, foods_allergy_details = ?, symptoms_selected = ?, symptoms_others = ?, temperature = ?, blood_pressure = ?, height = ?, weight = ?, bmi = ?, medical_concern = ?, returning_patient = ?, preferred_schedule = ?, health_concern = ?, status = ? WHERE user_id = ?");

        $update_stmt->bind_param("ssssssssssssssssssssssssssssssssi", 
            $first_name, $middle_initial, $last_name, $suffix, $dob, $gender, $email, $marital_status, 
            $contact_number, $emergency_contact, $relationship, $address, $guardian_name, 
            $guardian_relationship, $guardian_contact, $medical_conditions, $medications, 
            $allergies_meds, $allergies_foods, $foods_allergy_details, $symptoms_selected, 
            $symptoms_others, $temperature, $blood_pressure, $height, $weight, $bmi, $medical_concern, 
            $returning_patient, $preferred_schedule, $health_concern, $status, $user_id);

        if ($update_stmt->execute()) {
            echo "<script>alert('User details updated successfully.');</script>";
        } else {
            echo "<script>alert('Failed to update user details.');</script>";
        }
    } elseif ($action === 'back') {
        header("Location: admin_residents.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Screening Summary</title>
<style>
    /* Reset default styling */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        background-color: #f5f7fa;
        color: #333;
        padding: 40px 20px;
    }

    h2 {
        text-align: center;
        font-size: 2.5rem;
        font-weight: bold;
        color: #1f3a56;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 3px solid #007bff;
    }

    form {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-width: 1000px;
        margin: auto;
    }

    fieldset {
        border: none;
        margin-bottom: 25px;
    }

    legend {
        font-weight: bold;
        font-size: 1.3rem;
        color: #007bff;
        padding: 5px 0;
        border-bottom: 2px solid #007bff;
    }

    label {
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        display: block;
        font-size: 1.1rem;
    }

    input[type="text"],
    input[type="email"],
    input[type="date"],
    input[type="datetime-local"],
    textarea,
    select {
        width: 100%;
        padding: 14px;
        margin-bottom: 20px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1.1rem;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="date"]:focus,
    input[type="datetime-local"]:focus,
    textarea:focus,
    select:focus {
        border-color: #007bff;
        outline: none;
    }

    textarea {
        height: 120px;
        resize: vertical;
    }

    input[type="submit"],
    button {
        background-color: #007bff;
        color: #ffffff;
        font-size: 1rem;
        padding: 12px 25px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-weight: bold;
    }

    input[type="submit"]:hover,
    button:hover {
        background-color: #0056b3;
    }

    .form-actions {
        text-align: center;
    }

    .btn-back {
        background-color: #6c757d;
        font-weight: bold;
    }

    .btn-back:hover {
        background-color: #5a6268;
    }

    /* Focus effect for input fields */
    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="date"]:focus,
    input[type="datetime-local"]:focus,
    select:focus {
        border-color: #0056b3;
    }

    /* Style for the back button */
    .btn-back {
        background-color: #ffc800ff;
        font-weight: bold;
        color: black;
    }

    .btn-back:hover {
        background-color: #5a6268;
        color: white;
    }

    /* Responsive design for smaller screens */
    @media (max-width: 768px) {
        body {
            padding: 20px;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        form {
            padding: 25px;
        }

        .form-actions {
            margin-top: 20px;
        }

        .form-actions button {
            font-size: 0.9rem;
            padding: 10px 20px;
        }

        fieldset {
            padding: 15px;
        }
    }
</style>

</head>

<body>

    <h2>Admin Screening Summary for User ID: <?= htmlspecialchars($user_id) ?></h2>

    <form action="admin_screening_summary.php?user_id=<?= $user_id ?>" method="POST">
        <!-- Personal Information -->
        <fieldset>
            <legend>Personal Information </legend><br>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($latest_screening['first_name'] ?? '') ?>"><br><br>

            <label for="middle_initial">Middle Initial:</label>
            <input type="text" id="middle_initial" name="middle_initial" value="<?= htmlspecialchars($latest_screening['middle_initial'] ?? '') ?>"><br><br>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($latest_screening['last_name'] ?? '') ?>"><br><br>

            <label for="suffix">Suffix:</label>
            <input type="text" id="suffix" name="suffix" value="<?= htmlspecialchars($latest_screening['suffix'] ?? '') ?>"><br><br>

            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" value="<?= htmlspecialchars($latest_screening['dob'] ?? '') ?>"><br><br>

            <label for="gender">Gender:</label>
            <input type="text" id="gender" name="gender" value="<?= htmlspecialchars($latest_screening['gender'] ?? '') ?>"><br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($latest_screening['email'] ?? '') ?>"><br><br>

            <label for="marital_status">Marital Status:</label>
            <input type="text" id="marital_status" name="marital_status" value="<?= htmlspecialchars($latest_screening['marital_status'] ?? '') ?>"><br><br>

            <label for="contact_number">Contact Number:</label>
            <input type="text" id="contact_number" name="contact_number" value="<?= htmlspecialchars($latest_screening['contact_number'] ?? '') ?>"><br><br>

            <label for="emergency_contact">Emergency Contact:</label>
            <input type="text" id="emergency_contact" name="emergency_contact" value="<?= htmlspecialchars($latest_screening['emergency_contact'] ?? '') ?>"><br><br>

            <label for="relationship">Relationship:</label>
            <input type="text" id="relationship" name="relationship" value="<?= htmlspecialchars($latest_screening['relationship'] ?? '') ?>"><br><br>

            <label for="address">Address:</label>
            <textarea id="address" name="address"><?= htmlspecialchars($latest_screening['address'] ?? '') ?></textarea><br><br>
        </fieldset>

        <!-- Guardian Information -->
        <fieldset>
            <legend>Guardian Information</legend><br>
            <label for="guardian_name">Guardian Name:</label>
            <input type="text" id="guardian_name" name="guardian_name" value="<?= htmlspecialchars($latest_screening['guardian_name'] ?? '') ?>"><br><br>

            <label for="guardian_contact">Guardian Contact:</label>
            <input type="text" id="guardian_contact" name="guardian_contact" value="<?= htmlspecialchars($latest_screening['guardian_contact'] ?? '') ?>"><br><br>

            <label for="guardian_relationship">Guardian Relationship:</label>
            <input type="text" id="guardian_relationship" name="guardian_relationship" value="<?= htmlspecialchars($latest_screening['guardian_relationship'] ?? '') ?>"><br><br>
        </fieldset>

        <!-- Medical Information -->
        <fieldset>
            <legend>Medical Information</legend><br>
            <label for="medical_conditions">Medical Conditions:</label>
            <textarea id="medical_conditions" name="medical_conditions"><?= htmlspecialchars($latest_screening['medical_conditions'] ?? '') ?></textarea><br><br>

            <label for="medications">Medications:</label>
            <textarea id="medications" name="medications"><?= htmlspecialchars($latest_screening['medications'] ?? '') ?></textarea><br><br>

            <label for="allergies_meds">Allergies to Medications:</label>
            <textarea id="allergies_meds" name="allergies_meds"><?= htmlspecialchars($latest_screening['allergies_meds'] ?? '') ?></textarea><br><br>

            <label for="allergies_foods">Food Allergies:</label>
            <textarea id="allergies_foods" name="allergies_foods"><?= htmlspecialchars($latest_screening['allergies_foods'] ?? '') ?></textarea><br><br>

            <label for="foods_allergy_details">Food Allergy Details:</label>
            <textarea id="foods_allergy_details" name="foods_allergy_details"><?= htmlspecialchars($latest_screening['foods_allergy_details'] ?? '') ?></textarea><br><br>

            <label for="symptoms_selected">Symptoms:</label>
            <textarea id="symptoms_selected" name="symptoms_selected"><?= htmlspecialchars($latest_screening['symptoms_selected'] ?? '') ?></textarea><br><br>

            <label for="symptoms_others">Other Symptoms:</label>
            <textarea id="symptoms_others" name="symptoms_others"><?= htmlspecialchars($latest_screening['symptoms_others'] ?? '') ?></textarea><br><br>
        </fieldset>

        <!-- Additional Information -->
        <fieldset>
            <legend>Additional Information</legend><br>
            <label for="temperature">Temperature:</label>
            <input type="text" id="temperature" name="temperature" value="<?= htmlspecialchars($latest_screening['temperature'] ?? '') ?>"><br><br>

            <label for="blood_pressure">Blood Pressure:</label>
            <input type="text" id="blood_pressure" name="blood_pressure" value="<?= htmlspecialchars($latest_screening['blood_pressure'] ?? '') ?>"><br><br>

            <label for="height">Height (cm):</label>
            <input type="text" id="height" name="height" value="<?= htmlspecialchars($latest_screening['height'] ?? '') ?>" oninput="calculateBMI()"><br><br>

            <label for="weight">Weight (kg):</label>
            <input type="text" id="weight" name="weight" value="<?= htmlspecialchars($latest_screening['weight'] ?? '') ?>" oninput="calculateBMI()"><br><br>

            <label for="bmi">BMI:</label>
            <input type="text" id="bmi" name="bmi" value="<?= htmlspecialchars($latest_screening['bmi'] ?? '') ?>" readonly><br><br>

            <label for="medical_concern">Medical Concerns:</label>
            <input type="text" id="medical_concern" name="medical_concern" value="<?= htmlspecialchars($latest_screening['medical_concern'] ?? '') ?>"><br><br>

            <label for="returning_patient">Returning Patient:</label>
            <input type="text" id="returning_patient" name="returning_patient" value="<?= htmlspecialchars($latest_screening['returning_patient'] ?? '') ?>"><br><br>

            <label for="preferred_schedule">Preferred Schedule:</label>
            <input type="datetime-local" id="preferred_schedule" name="preferred_schedule" value="<?= htmlspecialchars($latest_screening['preferred_schedule'] ?? '') ?>"><br>

            <label for="health_concern">Health Concern:</label>
            <textarea id="health_concern" name="health_concern"><?= htmlspecialchars($latest_screening['health_concern'] ?? '') ?></textarea><br><br>

            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="pending" <?= ($latest_screening['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= ($latest_screening['status'] == 'approved') ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= ($latest_screening['status'] == 'rejected') ? 'selected' : '' ?>>Rejected</option>
            </select><br>
        </fieldset>

        <!-- Confirmation Buttons -->
        <div class="form-actions">
            <button type="submit" name="action" value="update" class="btn-submit">Update</button>
            <button type="submit" name="action" value="back" class="btn-back">Back to Dashboard</button>
        </div>
    </form>

    <script>
        function calculateBMI() {
            var height = parseFloat(document.getElementById("height").value);
            var weight = parseFloat(document.getElementById("weight").value);

            if (!isNaN(height) && !isNaN(weight) && height > 0) {
                var bmi = weight / ((height / 100) * (height / 100)); // Convert height to meters
                document.getElementById("bmi").value = bmi.toFixed(2); // Display BMI with 2 decimal places
            } else {
                document.getElementById("bmi").value = ''; // Clear BMI if invalid input
            }
        }
    </script>

</body>
</html>

<?php $conn->close(); ?>
