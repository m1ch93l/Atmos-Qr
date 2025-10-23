<?php
// Note: This script assumes your database connection ($conn) in 'database/db.php'
// and the database itself are configured to use UTF-8 (specifically utf8mb4)
// to correctly store special characters like 'ñ'.

// Include database connection code
include 'database/db.php';
include 'phpqrcode/qrlib.php';

// ** FIX: Ensure the MySQL connection is set to handle UTF-8 data **
// This prevents characters like 'ñ' from being converted to '?' during transmission.
if (isset($conn)) {
    // This is the critical line that ensures the client/server communication uses UTF-8
    $conn->set_charset("utf8mb4");
}

// Ensure Multibyte String functions are available and use UTF-8 internally
if (extension_loaded('mbstring')) {
    mb_internal_encoding('UTF-8');
    mb_regex_encoding('UTF-8');
} else {
    // Optional: Log an error or return a message if mbstring is not loaded, 
    // as it is essential for reliable character encoding conversion.
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvFile"])) {
    $file = $_FILES["csvFile"];

    // Check if file is a valid CSV
    $allowedMimeTypes = ["text/csv", "application/vnd.ms-excel", "text/plain"];
    if (in_array($file["type"], $allowedMimeTypes)) {
        $handle = fopen($file["tmp_name"], "r");
        if (!$handle) {
            $response = ["status" => "error", "message" => "Could not open the uploaded file."];
            echo json_encode($response);
            exit;
        }

        $successCount     = 0;
        $errorCount       = 0;
        $duplicationCount = 0;
        $headerSkipped    = false;

        // Use an array to store row-specific error messages
        $rowErrors = [];

        // 1. Prepare statement outside the loop for better performance
        $stmt = $conn->prepare("INSERT INTO collegestudents (IdentificationNumber, FirstName, LastName, Email, CourseID, LevelID) VALUES (?, ?, ?, ?, ?, ?)");
        // sssiii is correct: 3 strings, 3 integers
        $stmt->bind_param("sssiii", $identificationNumber, $firstName, $lastName, $email, $courseId, $levelId);

        // 2. Fetch valid courses/levels into Name => ID maps for efficient lookup

        // Course Map: Name (Key) => ID (Value)
        $courseNamesToId = [];
        $courseQuery     = "SELECT ID, course_name FROM courses";
        $courseResult    = $conn->query($courseQuery);
        while ($row = $courseResult->fetch_assoc()) {
            $courseNamesToId[trim($row['course_name'])] = $row['ID'];
        }

        // Level Map: Name (Key) => ID (Value)
        $levelNamesToId = [];
        $levelQuery     = "SELECT ID, level_name FROM levels";
        $levelResult    = $conn->query($levelQuery);
        while ($row = $levelResult->fetch_assoc()) {
            $levelNamesToId[trim($row['level_name'])] = $row['ID'];
        }

        $rowNumber = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rowNumber++;

            // Skip the header row
            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }

            // Check for correct field count
            if (count($data) < 6) {
                $rowErrors[] = "Row $rowNumber: Skipped. Expected 6 fields, found " . count($data) . ".";
                $errorCount++;
                continue;
            }

            // 3. Process CSV data and enforce UTF-8 encoding for special characters ('ñ', 'Ñ')
            // Using 'auto, Windows-1252, ISO-8859-1' provides robust source encoding detection.
            $encodingList = 'auto, Windows-1252, ISO-8859-1';

            $identificationNumber = trim($data[0]);
            $firstName            = trim(mb_convert_encoding($data[1], 'UTF-8', $encodingList));
            $lastName             = trim(mb_convert_encoding($data[2], 'UTF-8', $encodingList));
            $email                = trim(mb_convert_encoding($data[3], 'UTF-8', $encodingList));
            $courseName           = trim(mb_convert_encoding($data[4], 'UTF-8', $encodingList));
            $levelName            = trim(mb_convert_encoding($data[5], 'UTF-8', $encodingList));

            // Basic validation for required fields
            if (empty($identificationNumber) || empty($firstName) || empty($lastName) || empty($courseName) || empty($levelName)) {
                $rowErrors[] = "Row $rowNumber: Skipped. One or more required fields (ID, Name, Course, Level) are empty.";
                $errorCount++;
                continue;
            }

            // Set default email address if it is not provided
            if (empty($email)) {
                // Ensure default email also uses cleaned identification number
                $email = strtolower($identificationNumber) . "@student.example.com";
            }

            // 4. Look up CourseID and LevelID using the efficient Name => ID maps
            $courseId = $courseNamesToId[$courseName] ?? null;
            $levelId  = $levelNamesToId[$levelName] ?? null;

            if (!$courseId || !$levelId) {
                $rowErrors[] = "Row $rowNumber: Skipped. Invalid Course ('$courseName') or Level ('$levelName') value.";
                $errorCount++;
                continue;
            }

            // 5. Check if the student already exists in the database
            $existingStmt = $conn->prepare("SELECT COUNT(*) FROM collegestudents WHERE IdentificationNumber = ? OR Email = ?");
            $existingStmt->bind_param("ss", $identificationNumber, $email);
            $existingStmt->execute();
            $existingStmt->bind_result($count);
            $existingStmt->fetch();
            $existingStmt->close();

            if ($count > 0) {
                // Skip inserting this record if the student already exists
                $duplicationCount++;
                continue;
            }

            // 6. Execute the prepared statement
            if ($stmt->execute()) {
                $successCount++;
                // Generate and save the QR code
                $qrCodePath = 'qr_codes/' . $identificationNumber . '.png'; // Make sure the qr_codes directory exists and is writable
                // Use the cleaned identification number for the QR code content
                QRcode::png($identificationNumber, $qrCodePath, QR_ECLEVEL_L, 4);
            } else {
                // MySQL error during insertion
                $rowErrors[] = "Row $rowNumber: Failed to insert. Database error: " . $stmt->error;
                $errorCount++;
            }
        }

        // Close file handle and statement
        fclose($handle);
        $stmt->close();
        $conn->close();

        // 7. Generate consolidated response message
        $messages = [];
        if ($successCount > 0) {
            $messages[] = "$successCount students imported successfully.";
        }
        if ($duplicationCount > 0) {
            $messages[] = "$duplicationCount records were skipped due to duplication (ID or Email already exists).";
        }
        if ($errorCount > 0) {
            $messages[] = "$errorCount records failed validation or insertion. Check logs for details.";
        }

        if (!empty($messages)) {
            $status   = ($errorCount > 0) ? "warning" : "success";
            $response = ["status" => $status, "message" => implode(" ", $messages), "errors" => $rowErrors];
        } else {
            // Handle case where no data was processed after headers (e.g., empty file)
            $response = ["status" => "warning", "message" => "The CSV file was empty or only contained a header row."];
        }

    } else {
        $response = ["status" => "error", "message" => "Invalid file format. Please upload a CSV file."];
    }

    header('Content-Type: application/json');
    echo json_encode($response);

} else {
    // If request is not POST or file is missing
    header('Content-Type: application/json');
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Invalid request. Please upload a CSV file using POST method."]);
}

?>