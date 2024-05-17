<?php
// Include database connection code
include 'database/db.php';
include 'phpqrcode/qrlib.php';

// Fetch data from Faculty table along with DepartmentName and PositionName
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $query = "SELECT f.ID, f.IdentificationNumber, f.FirstName, f.LastName, f.Email, d.DepartmentName, p.PositionName 
              FROM Faculties f
              INNER JOIN Departments d ON f.DepartmentID = d.ID
              INNER JOIN Positions p ON f.PositionID = p.ID
              ORDER BY f.ID DESC"; // Order by ID in descending order
    $result = mysqli_query($conn, $query);

    if ($result) {
        $faculties = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $faculties[] = $row;
        }
        echo json_encode(array("data" => $faculties));
        http_response_code(200); // OK
    } else {
        echo json_encode(array("error" => "Failed to fetch faculties"));
        http_response_code(500); // Internal Server Error
    }
}

// Add a new faculty to the database
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['identificationNumber'])) {
    $identificationNumber = $_POST['identificationNumber'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $departmentName = $_POST['departmentName'];
    $positionName = $_POST['positionName'];

    // Check for duplicate identification number
    $checkDuplicateIdQuery = "SELECT * FROM Faculties WHERE IdentificationNumber = ?";
    $stmt = $conn->prepare($checkDuplicateIdQuery);
    $stmt->bind_param("s", $identificationNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Duplicate identification number found
        echo json_encode(array("status" => "error", "error" => "Duplicate identification number"));
        exit; // Stop further execution
    }

    // Check for duplicate email
    $checkDuplicateEmailQuery = "SELECT * FROM Faculties WHERE Email = ?";
    $stmt = $conn->prepare($checkDuplicateEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Duplicate email found
        echo json_encode(array("status" => "error", "error" => "Duplicate email"));
        exit; // Stop further execution
    }

    // Prepare SELECT statements to fetch IDs for department and position names
    $deptQuery = "SELECT ID FROM Departments WHERE DepartmentName = ?";
    $posQuery = "SELECT ID FROM Positions WHERE PositionName = ?";
    
    // Prepare and execute statements
    $deptStmt = $conn->prepare($deptQuery);
    $deptStmt->bind_param("s", $departmentName);
    $deptStmt->execute();
    $deptResult = $deptStmt->get_result();
    
    $posStmt = $conn->prepare($posQuery);
    $posStmt->bind_param("s", $positionName);
    $posStmt->execute();
    $posResult = $posStmt->get_result();
    
    // Check if department and position names exist
    if ($deptResult->num_rows > 0 && $posResult->num_rows > 0) {
        // Fetch IDs
        $deptRow = $deptResult->fetch_assoc();
        $deptID = $deptRow['ID'];
        
        $posRow = $posResult->fetch_assoc();
        $posID = $posRow['ID'];

        // Prepare INSERT statement with QR code image data
        $stmt = $conn->prepare("INSERT INTO Faculties (IdentificationNumber, FirstName, LastName, Email, DepartmentID, PositionID, QRCodeImage) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Generate QR code
        $data = $identificationNumber; // Or any unique data
        $errorCorrectionLevel = 'L'; // QR code error correction level
        $matrixPointSize = 26; // QR code point size
        ob_start(); // Start output buffering
        QRcode::png($data, null, $errorCorrectionLevel, $matrixPointSize, 4); // Generate QR code without saving it to a file
        $qrCodeImageBinary = ob_get_contents(); // Get the binary image data
        ob_end_clean(); // End output buffering and discard output
        
        // Bind parameters
        $stmt->bind_param("ssssiis", $identificationNumber, $firstName, $lastName, $email, $deptID, $posID, $qrCodeImageBinary);
        
        // Execute statement
        if ($stmt->execute()) {
            echo json_encode(array("status" => "success"));
            http_response_code(200); // OK
        } else {
            echo json_encode(array("error" => "Failed to add faculty"));
            http_response_code(500); // Internal Server Error
        }
    } else {
        echo json_encode(array("error" => "Department or position does not exist"));
        http_response_code(400); // Bad Request
    }
}



// Update faculty
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editFacultyId'])) {
    $facultyId = $_POST['editFacultyId'];
    $newIdentificationNumber = $_POST['editIdentificationNumber'];
    
    // Fetch the current identification number
    $currentQuery = "SELECT IdentificationNumber FROM Faculties WHERE ID = ?";
    $currentStmt = $conn->prepare($currentQuery);
    $currentStmt->bind_param("i", $facultyId);
    $currentStmt->execute();
    $result = $currentStmt->get_result();
    $currentData = $result->fetch_assoc();
    $currentIdentificationNumber = $currentData['IdentificationNumber'];

    // Get DepartmentID from the Departments table based on DepartmentName
    $queryDepartment = "SELECT ID FROM Departments WHERE DepartmentName = ?";
    $stmtDepartment = $conn->prepare($queryDepartment);
    $stmtDepartment->bind_param("s", $_POST['editDepartmentName']); // Use $_POST['editDepartmentName']
    $stmtDepartment->execute();
    $resultDepartment = $stmtDepartment->get_result();
    
    if ($resultDepartment->num_rows > 0) {
        // Department exists, get DepartmentID
        $rowDepartment = $resultDepartment->fetch_assoc();
        $departmentId = $rowDepartment['ID'];

        // Get PositionID from the Positions table based on PositionName
        $queryPosition = "SELECT ID FROM Positions WHERE PositionName = ?";
        $stmtPosition = $conn->prepare($queryPosition);
        $stmtPosition->bind_param("s", $_POST['editPositionName']); // Use $_POST['editPositionName']
        $stmtPosition->execute();
        $resultPosition = $stmtPosition->get_result();
        
        if ($resultPosition->num_rows > 0) {
            // Position exists, get PositionID
            $rowPosition = $resultPosition->fetch_assoc();
            $positionId = $rowPosition['ID'];

            // Prepare UPDATE statement
            $stmtUpdate = $conn->prepare("UPDATE Faculties SET IdentificationNumber=?, FirstName=?, LastName=?, Email=?, DepartmentID=?, PositionID=? WHERE ID=?");
            $stmtUpdate->bind_param("ssssiii", $newIdentificationNumber, $_POST['editFirstName'], $_POST['editLastName'], $_POST['editEmail'], $departmentId, $positionId, $facultyId);

            // Execute statement
            if ($stmtUpdate->execute()) {
                // If the identification number has changed, update the QR code in the database
                if ($currentIdentificationNumber !== $newIdentificationNumber) {
                    // Set the error correction level and matrix point size
                    $errorCorrectionLevel = 'L'; // QR code error correction level
                    $matrixPointSize = 26; // Increase the point size for higher resolution

                    // Generate a new QR code with the new identification number
                    ob_start(); // Start output buffering
                    QRcode::png($newIdentificationNumber, null, $errorCorrectionLevel, $matrixPointSize, 4); // Generate QR code without saving it
                    $qrCodeImageBinary = ob_get_contents(); // Get the binary image data
                    ob_end_clean(); // End output buffering and discard output

                    // Update the QR code image in the database
                    $stmtUpdateQrCode = $conn->prepare("UPDATE Faculties SET QRCodeImage=? WHERE ID=?");
                    $stmtUpdateQrCode->bind_param("si", $qrCodeImageBinary, $facultyId);
                    $stmtUpdateQrCode->execute();
                }

                echo json_encode(array("status" => "success"));
                http_response_code(200); // OK
            } else {
                echo json_encode(array("error" => "Failed to update faculty"));
                http_response_code(500); // Internal Server Error
            }
        } else {
            echo json_encode(array("error" => "Position does not exist"));
            http_response_code(400); // Bad Request
        }
    } else {
        echo json_encode(array("error" => "Department does not exist"));
        http_response_code(400); // Bad Request
    }
}

// Delete faculty
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['faculty_id'])) {
    $faculty_id = $_POST['faculty_id'];
    
    // Fetch the identification number before deletion
    $fetchQuery = "SELECT IdentificationNumber FROM Faculties WHERE ID = ?";
    $fetchStmt = $conn->prepare($fetchQuery);
    $fetchStmt->bind_param("i", $faculty_id);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    
    if ($result->num_rows > 0) {
        $faculty = $result->fetch_assoc();
        $identificationNumber = $faculty['IdentificationNumber'];
        
        // Prepare DELETE statement
        $stmt = $conn->prepare("DELETE FROM Faculties WHERE ID = ?");
        $stmt->bind_param("i", $faculty_id);

        if ($stmt->execute()) {
            // After successful deletion, delete the associated QR code if it exists from the database
            $deleteQRStmt = $conn->prepare("UPDATE Faculties SET QRCodeImage = NULL WHERE ID = ?");
            $deleteQRStmt->bind_param("i", $faculty_id);
            $deleteQRStmt->execute();

            echo json_encode(array('status' => 'success', 'message' => 'Faculty deleted successfully'));
            http_response_code(200); // OK
        } else {
            echo json_encode(array('error' => 'Failed to delete faculty'));
            http_response_code(500); // Internal Server Error
        }
    } else {
        echo json_encode(array('error' => 'Faculty not found'));
        http_response_code(404); // Not Found
    }
}


// Bulk delete faculties
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bulkDelete'])) {
    $faculty_ids = $_POST['faculty_ids']; // Assume this is an array of faculties IDs
    
    if (is_array($faculty_ids)) {
        // Fetch the identification numbers for all faculties to be deleted
        $placeholders = implode(',', array_fill(0, count($faculty_ids), '?'));
        $idQuery = "SELECT IdentificationNumber FROM Faculties WHERE ID IN ($placeholders)";
        $idStmt = $conn->prepare($idQuery);
        
        // Dynamically bind parameters for faculty IDs
        $idStmt->bind_param(str_repeat('i', count($faculty_ids)), ...$faculty_ids);
        $idStmt->execute();
        $result = $idStmt->get_result();
        $identificationNumbers = [];
        while ($row = $result->fetch_assoc()) {
            $identificationNumbers[] = $row['IdentificationNumber'];
        }
        $idStmt->close();
        
        // Proceed with deleting the faculties from the database
        $deleteStmt = $conn->prepare("DELETE FROM Faculties WHERE ID IN ($placeholders)");
        
        // Dynamically bind parameters for faculty IDs again
        $deleteStmt->bind_param(str_repeat('i', count($faculty_ids)), ...$faculty_ids);
        
        if ($deleteStmt->execute()) {
            // Delete QR code data for each faculty
            foreach ($identificationNumbers as $idNum) {
                // Update QR code image to NULL in the database
                $updateQRStmt = $conn->prepare("UPDATE Faculties SET QRCodeImage = NULL WHERE IdentificationNumber = ?");
                $updateQRStmt->bind_param("s", $idNum);
                $updateQRStmt->execute();
            }
            
            echo json_encode(array('status' => 'success', 'message' => 'Faculties and their QR codes deleted successfully'));
            http_response_code(200); // OK
        } else {
            echo json_encode(array('error' => 'Failed to delete faculties'));
            http_response_code(500); // Internal Server Error
        }
        $deleteStmt->close();
    } else {
        echo json_encode(array('error' => 'Invalid faculty IDs'));
        http_response_code(400); // Bad Request
    }
}
?>
