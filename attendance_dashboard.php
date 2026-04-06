<?php
    session_start();

    include 'database/db.php';

    // Check if the user is not logged in or is not an administrator
    if (! isset($_SESSION['username']) || $_SESSION['user_type_id'] != 1) {
    header('Location: index.php');
    exit();
    }

    // Fetch user details of the current administrator from the database
    $username = $_SESSION['username'];
    $query    = "SELECT * FROM users WHERE username = '$username' AND user_type_id = 1";
    $result   = mysqli_query($conn, $query);

    if ($result) {
    $row       = mysqli_fetch_assoc($result);
    $id        = $row['id'];
    $firstname = $row['firstname'];
    $lastname  = $row['lastname'];
    $email     = $row['email'];
    $username  = $row['username'];
    } else {
    echo "Error: " . mysqli_error($conn);
    }

                                                                           // Get filter parameters
    $studentType = isset($_GET['studentType']) ? $_GET['studentType'] : 'all'; // all, college, seniorhigh
    $section     = isset($_GET['section']) ? $_GET['section'] : '';

    // Get total events for college students
    $collegeTotalEventsQuery = "
    SELECT COUNT(DISTINCT a.event_id) as total_events
    FROM attendance a
    WHERE a.college_student_id IS NOT NULL
    ";
    $collegeTotalEventsResult = mysqli_query($conn, $collegeTotalEventsQuery);
    $collegeTotalEventsRow    = mysqli_fetch_assoc($collegeTotalEventsResult);
    $collegeTotalEvents       = $collegeTotalEventsRow['total_events'];

    // Get total events for senior high students
    $seniorHighTotalEventsQuery = "
    SELECT COUNT(DISTINCT a.event_id) as total_events
    FROM attendance a
    WHERE a.senior_high_student_id IS NOT NULL
    ";
    $seniorHighTotalEventsResult = mysqli_query($conn, $seniorHighTotalEventsQuery);
    $seniorHighTotalEventsRow    = mysqli_fetch_assoc($seniorHighTotalEventsResult);
    $seniorHighTotalEvents       = $seniorHighTotalEventsRow['total_events'];

    // Build query for college students with attendance count by section
    $collegeAttendanceQuery = "
SELECT
    cs.ID as student_id,
    cs.FirstName,
    cs.LastName,
    cs.IdentificationNumber,
    c.course_name,
    l.level_name,
    COUNT(DISTINCT a.event_id) as present_days,
    COALESCE($collegeTotalEvents - COUNT(DISTINCT a.event_id), $collegeTotalEvents) as absent_days
FROM collegestudents cs
LEFT JOIN courses c ON cs.CourseID = c.id
LEFT JOIN levels l ON cs.LevelID = l.id
LEFT JOIN attendance a ON cs.ID = a.college_student_id
GROUP BY cs.ID, cs.FirstName, cs.LastName, cs.IdentificationNumber, c.course_name, l.level_name
ORDER BY cs.FirstName, cs.LastName
";

    // Build query for senior high students with attendance count by section
    $seniorHighAttendanceQuery = "
SELECT
    shs.ID as student_id,
    shs.FirstName,
    shs.LastName,
    shs.IdentificationNumber,
    st.strand_name,
    g.grade_name,
    sec.section_name,
    COUNT(DISTINCT a.event_id) as present_days,
    COALESCE($seniorHighTotalEvents - COUNT(DISTINCT a.event_id), $seniorHighTotalEvents) as absent_days
FROM seniorhighstudents shs
LEFT JOIN strands st ON shs.StrandID = st.id
LEFT JOIN grades g ON shs.GradeID = g.id
LEFT JOIN sections sec ON shs.SectionID = sec.id
LEFT JOIN attendance a ON shs.ID = a.senior_high_student_id
";

    // Add section filter if selected
    if ($studentType === 'seniorhigh' && $section !== '') {
    $seniorHighAttendanceQuery .= " WHERE sec.section_name = '$section'";
    }

    $seniorHighAttendanceQuery .= " GROUP BY shs.ID, shs.FirstName, shs.LastName, shs.IdentificationNumber, st.strand_name, g.grade_name, sec.section_name
ORDER BY sec.section_name, shs.FirstName, shs.LastName";

    // Execute queries based on filter
    if ($studentType === 'college') {
    $attendanceResult = mysqli_query($conn, $collegeAttendanceQuery);
    $attendanceData   = [];
    while ($row = mysqli_fetch_assoc($attendanceResult)) {
        $attendanceData[] = $row;
    }
    } elseif ($studentType === 'seniorhigh') {
    $attendanceResult = mysqli_query($conn, $seniorHighAttendanceQuery);
    $attendanceData   = [];
    while ($row = mysqli_fetch_assoc($attendanceResult)) {
        $attendanceData[] = $row;
    }
    } else {
    // Get both college and senior high data
    $collegeResult = mysqli_query($conn, $collegeAttendanceQuery);
    $collegeData   = [];
    while ($row = mysqli_fetch_assoc($collegeResult)) {
        $row['type']   = 'College';
        $collegeData[] = $row;
    }

    $seniorHighResult = mysqli_query($conn, $seniorHighAttendanceQuery);
    $seniorHighData   = [];
    while ($row = mysqli_fetch_assoc($seniorHighResult)) {
        $row['type']      = 'Senior High';
        $seniorHighData[] = $row;
    }

    $attendanceData = array_merge($collegeData, $seniorHighData);
    }

    // Get attendance summary by section (only for senior high)
    $sectionSummaryQuery = "
SELECT
    sec.section_name,
    COUNT(DISTINCT shs.ID) as total_students,
    COUNT(DISTINCT a.id) as total_attendance_records,
    ROUND(AVG(student_events.present_days), 2) as avg_present_days,
    ROUND(AVG(student_events.absent_days), 2) as avg_absent_days
FROM sections sec
LEFT JOIN seniorhighstudents shs ON shs.SectionID = sec.id
LEFT JOIN (
    SELECT
        shs2.ID,
        COUNT(DISTINCT a2.event_id) as present_days,
        ($seniorHighTotalEvents - COUNT(DISTINCT a2.event_id)) as absent_days
    FROM seniorhighstudents shs2
    LEFT JOIN attendance a2 ON shs2.ID = a2.senior_high_student_id
    GROUP BY shs2.ID
) student_events ON shs.ID = student_events.ID
LEFT JOIN attendance a ON shs.ID = a.senior_high_student_id
GROUP BY sec.section_name, sec.id
ORDER BY sec.section_name
";

    $sectionSummaryResult = mysqli_query($conn, $sectionSummaryQuery);
    $sectionSummaryData   = [];
    while ($row = mysqli_fetch_assoc($sectionSummaryResult)) {
    $sectionSummaryData[] = $row;
    }

    // Get total event count for "absent" calculation
    $totalEventsQuery  = "SELECT COUNT(*) as total_events FROM events";
    $totalEventsResult = mysqli_query($conn, $totalEventsQuery);
    $totalEventsRow    = mysqli_fetch_assoc($totalEventsResult);
    $totalEvents       = $totalEventsRow['total_events'];

?>

<?php include 'includes/header.php'; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <?php
            include 'includes/navbar.php';
            include 'includes/sidebar.php';
        ?>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Attendance Overview</h1>
                        </div><!-- /.col -->
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Attendance Overview</li>
                            </ol>
                        </div><!-- /.col -->
                    </div><!-- /.row -->
                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->

            <!-- Main content -->
            <div class="content">
                <div class="container-fluid">

                    <!-- Summary Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Attendance Summary by Section</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Section</th>
                                                    <th>Total Students</th>
                                                    <th>Avg. Present Days</th>
                                                    <th>Avg. Absent Days</th>
                                                    <th>Total Attendance Records</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    if (count($sectionSummaryData) > 0) {
                                                        foreach ($sectionSummaryData as $summary) {
                                                            echo "<tr>";
                                                            echo "<td><strong>" . htmlspecialchars($summary['section_name']) . "</strong></td>";
                                                            echo "<td>" . $summary['total_students'] . "</td>";
                                                            echo "<td><span class='badge badge-success'>" . $summary['avg_present_days'] . " days</span></td>";
                                                            echo "<td><span class='badge badge-danger'>" . $summary['avg_absent_days'] . " days</span></td>";
                                                            echo "<td>" . $summary['total_attendance_records'] . "</td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='5' class='text-center'>No data available</td></tr>";
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                    <!-- /.row -->

                    <!-- Filter Section -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">Filter Attendance</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group mr-3">
                                            <label for="studentType" class="mr-2">Student Type:</label>
                                            <select name="studentType" id="studentType" class="form-control"
                                                onchange="this.form.submit()">
                                                <option value="all"
                                                    <?php echo $studentType === 'all' ? 'selected' : ''; ?>>All Students
                                                </option>
                                                <option value="college"
                                                    <?php echo $studentType === 'college' ? 'selected' : ''; ?>>College
                                                    Students</option>
                                                <option value="seniorhigh"
                                                    <?php echo $studentType === 'seniorhigh' ? 'selected' : ''; ?>>
                                                    Senior High Students</option>
                                            </select>
                                        </div>

                                        <?php if ($studentType === 'seniorhigh'): ?>
                                        <div class="form-group">
                                            <label for="section" class="mr-2">Section:</label>
                                            <select name="section" id="section" class="form-control"
                                                onchange="this.form.submit()">
                                                <option value="">All Sections</option>
                                                <option value="A" <?php echo $section === 'A' ? 'selected' : ''; ?>>
                                                    Section A</option>
                                                <option value="B" <?php echo $section === 'B' ? 'selected' : ''; ?>>
                                                    Section B</option>
                                                <option value="C" <?php echo $section === 'C' ? 'selected' : ''; ?>>
                                                    Section C</option>
                                                <option value="D" <?php echo $section === 'D' ? 'selected' : ''; ?>>
                                                    Section D</option>
                                            </select>
                                        </div>
                                        <?php endif; ?>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                    <!-- /.row -->

                    <!-- Student Attendance List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Student Attendance Details</h3>
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="attendanceTable" class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID Number</th>
                                                    <th>Name</th>
                                                    <?php if ($studentType === 'college'): ?>
                                                    <th>Course</th>
                                                    <th>Level</th>
                                                    <?php elseif ($studentType === 'seniorhigh'): ?>
                                                    <th>Strand</th>
                                                    <th>Grade</th>
                                                    <th>Section</th>
                                                    <?php else: ?>
                                                    <th>Type</th>
                                                    <?php endif; ?>
                                                    <th>Present Days</th>
                                                    <th>Absent Days</th>
                                                    <th>Events Attended</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                    if (count($attendanceData) > 0) {
                                                        foreach ($attendanceData as $student) {
                                                            echo "<tr>";
                                                            echo "<td>" . htmlspecialchars($student['IdentificationNumber']) . "</td>";
                                                            echo "<td>" . htmlspecialchars($student['FirstName'] . " " . $student['LastName']) . "</td>";

                                                            if ($studentType === 'college') {
                                                                echo "<td>" . htmlspecialchars($student['course_name']) . "</td>";
                                                                echo "<td>" . htmlspecialchars($student['level_name']) . "</td>";
                                                            } elseif ($studentType === 'seniorhigh') {
                                                                echo "<td>" . htmlspecialchars($student['strand_name']) . "</td>";
                                                                echo "<td>" . htmlspecialchars($student['grade_name']) . "</td>";
                                                                echo "<td>" . htmlspecialchars($student['section_name']) . "</td>";
                                                            } else {
                                                                echo "<td>" . htmlspecialchars($student['type']) . "</td>";
                                                            }

                                                            echo "<td><span class='badge badge-success'>" . $student['present_days'] . " days</span></td>";
                                                            echo "<td><span class='badge badge-danger'>" . $student['absent_days'] . " days</span></td>";
                                                            echo "<td><span class='badge badge-primary'>" . $student['present_days'] . " event(s)</span></td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='8' class='text-center'>No attendance data available</td></tr>";
                                                    }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                    <!-- /.row -->

                </div><!-- /.container-fluid -->
            </div>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <?php include 'includes/footer.php'; ?>

    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>

    <script>
    $(function() {
        $('#attendanceTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    });
    </script>

</body>

</html>