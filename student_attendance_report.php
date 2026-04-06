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
    $studentType = isset($_GET['studentType']) ? $_GET['studentType'] : 'seniorhigh'; // seniorhigh or college
    $section     = isset($_GET['section']) ? $_GET['section'] : '';
    $strand      = isset($_GET['strand']) ? $_GET['strand'] : '';

    // Get total events for each student type (based on attendance records)
    // For Senior High: events where senior high students attended
    $seniorHighTotalEventsQuery = "
    SELECT COUNT(DISTINCT a.event_id) as total_events
    FROM attendance a
    WHERE a.senior_high_student_id IS NOT NULL
    ";
    $seniorHighTotalEventsResult = mysqli_query($conn, $seniorHighTotalEventsQuery);
    $seniorHighTotalEventsRow    = mysqli_fetch_assoc($seniorHighTotalEventsResult);
    $seniorHighTotalEvents       = $seniorHighTotalEventsRow['total_events'];

    // For College: events where college students attended
    $collegeTotalEventsQuery = "
    SELECT COUNT(DISTINCT a.event_id) as total_events
    FROM attendance a
    WHERE a.college_student_id IS NOT NULL
    ";
    $collegeTotalEventsResult = mysqli_query($conn, $collegeTotalEventsQuery);
    $collegeTotalEventsRow    = mysqli_fetch_assoc($collegeTotalEventsResult);
    $collegeTotalEvents       = $collegeTotalEventsRow['total_events'];

    // Set which total to use based on student type
    $totalEvents = ($studentType === 'seniorhigh') ? $seniorHighTotalEvents : $collegeTotalEvents;

    // Build detailed student attendance query
    if ($studentType === 'seniorhigh') {
    $studentQuery = "
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

    $whereConditions = [];
    if ($section !== '') {
        $whereConditions[] = "sec.section_name = '$section'";
    }
    if ($strand !== '') {
        $whereConditions[] = "st.id = '$strand'";
    }

    if (count($whereConditions) > 0) {
        $studentQuery .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $studentQuery .= " GROUP BY shs.ID, shs.FirstName, shs.LastName, shs.IdentificationNumber, st.strand_name, g.grade_name, sec.section_name
    ORDER BY sec.section_name, shs.FirstName, shs.LastName";

    } else { // college
    $studentQuery = "
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
    }

    $studentResult = mysqli_query($conn, $studentQuery);
    $studentData   = [];
    while ($row = mysqli_fetch_assoc($studentResult)) {
    $studentData[] = $row;
    }

    // Get strand list for filter
    $strandQuery  = "SELECT id, strand_name FROM strands ORDER BY strand_name";
    $strandResult = mysqli_query($conn, $strandQuery);
    $strands      = [];
    while ($row = mysqli_fetch_assoc($strandResult)) {
    $strands[] = $row;
    }

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
          <h1 class="m-0">Student Attendance Report</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="admin_dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Student Attendance Report</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <div class="content">
    <div class="container-fluid">

      <!-- Statistics Section -->
      <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Students</span>
              <span class="info-box-number"><?php echo count($studentData); ?></span>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-calendar-check"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Total Events</span>
              <span class="info-box-number"><?php echo $totalEvents; ?></span>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-chart-pie"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Avg. Present Days</span>
              <span class="info-box-number">
                <?php
                    $totalPresent = 0;
                    foreach ($studentData as $student) {
                        $totalPresent += $student['present_days'];
                    }
                    echo count($studentData) > 0 ? round($totalPresent / count($studentData), 2) : 0;
                ?>
              </span>
            </div>
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <div class="info-box">
            <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-list"></i></span>
            <div class="info-box-content">
              <span class="info-box-text">Avg. Absent Days</span>
              <span class="info-box-number">
                <?php
                    $totalAbsent = 0;
                    foreach ($studentData as $student) {
                        $totalAbsent += $student['absent_days'];
                    }
                    echo count($studentData) > 0 ? round($totalAbsent / count($studentData), 2) : 0;
                ?>
              </span>
            </div>
          </div>
        </div>
      </div>
      <!-- /.row -->

      <!-- Filter Section -->
      <div class="row">
        <div class="col-12">
          <div class="card card-secondary">
            <div class="card-header">
              <h3 class="card-title">Filter Report</h3>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
              <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                  <label for="studentType" class="mr-2">Student Type:</label>
                  <select name="studentType" id="studentType" class="form-control" onchange="this.form.submit()">
                    <option value="seniorhigh" <?php echo $studentType === 'seniorhigh' ? 'selected' : ''; ?>>Senior High Students</option>
                    <option value="college" <?php echo $studentType === 'college' ? 'selected' : ''; ?>>College Students</option>
                  </select>
                </div>

                <?php if ($studentType === 'seniorhigh'): ?>
                <div class="form-group mr-3">
                  <label for="section" class="mr-2">Section:</label>
                  <select name="section" id="section" class="form-control" onchange="this.form.submit()">
                    <option value="">All Sections</option>
                    <option value="A" <?php echo $section === 'A' ? 'selected' : ''; ?>>Section A</option>
                    <option value="B" <?php echo $section === 'B' ? 'selected' : ''; ?>>Section B</option>
                    <option value="C" <?php echo $section === 'C' ? 'selected' : ''; ?>>Section C</option>
                    <option value="D" <?php echo $section === 'D' ? 'selected' : ''; ?>>Section D</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="strand" class="mr-2">Strand:</label>
                  <select name="strand" id="strand" class="form-control" onchange="this.form.submit()">
                    <option value="">All Strands</option>
                    <?php foreach ($strands as $s) {?>
                      <option value="<?php echo $s['id']; ?>" <?php echo $strand === (string) $s['id'] ? 'selected' : ''; ?>><?php echo $s['strand_name']; ?></option>
                    <?php }?>
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

      <!-- Student Report Table -->
      <div class="row">
        <div class="col-12">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Student Attendance Details</h3>
              <div class="card-tools">
                <button type="button" class="btn btn-tool btn-sm" onclick="printTable()">
                  <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-tool btn-sm" onclick="exportToCSV()">
                  <i class="fas fa-download"></i> Export CSV
                </button>
              </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body" id="reportTable">
              <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                  <thead style="background-color: #007bff; color: white;">
                    <tr>
                      <th>ID Number</th>
                      <th>Name</th>
                      <?php if ($studentType === 'seniorhigh'): ?>
                        <th>Strand</th>
                        <th>Grade</th>
                        <th>Section</th>
                      <?php else: ?>
                        <th>Course</th>
                        <th>Level</th>
                      <?php endif; ?>
                      <th style="text-align: center;">
                        <i class="fas fa-check text-success"></i> Present Days
                      </th>
                      <th style="text-align: center;">
                        <i class="fas fa-times text-danger"></i> Absent Days
                      </th>
                      <th style="text-align: center;">Attendance Rate</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                        if (count($studentData) > 0) {
                            foreach ($studentData as $student) {
                                $attendanceRate = $totalEvents > 0 ? round(($student['present_days'] / $totalEvents) * 100, 2) : 0;
                                $rateColor      = $attendanceRate >= 75 ? 'success' : ($attendanceRate >= 50 ? 'warning' : 'danger');

                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($student['IdentificationNumber']) . "</td>";
                                echo "<td>" . htmlspecialchars($student['FirstName'] . " " . $student['LastName']) . "</td>";

                                if ($studentType === 'seniorhigh') {
                                    echo "<td>" . htmlspecialchars($student['strand_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($student['grade_name']) . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($student['section_name']) . "</strong></td>";
                                } else {
                                    echo "<td>" . htmlspecialchars($student['course_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($student['level_name']) . "</td>";
                                }

                                echo "<td style='text-align: center;'>";
                                echo "<span class='badge badge-success'>" . $student['present_days'] . "</span>";
                                echo "</td>";

                                echo "<td style='text-align: center;'>";
                                echo "<span class='badge badge-danger'>" . $student['absent_days'] . "</span>";
                                echo "</td>";

                                echo "<td style='text-align: center;'>";
                                echo "<div class='progress progress-sm'>";
                                echo "<div class='progress-bar bg-" . $rateColor . "' role='progressbar' style='width: " . $attendanceRate . "%' aria-valuenow='" . $attendanceRate . "' aria-valuemin='0' aria-valuemax='100'></div>";
                                echo "</div>";
                                echo "<small>" . $attendanceRate . "%</small>";
                                echo "</td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>No attendance data available</td></tr>";
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
<script src="dist/js/adminlte.min.js"></script>

<script>
function printTable() {
    var printContents = document.getElementById("reportTable").innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
}

function exportToCSV() {
    var csv = [];
    var rows = document.querySelectorAll("table tr");

    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");

        for (var j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }

        csv.push(row.join(","));
    }

    var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    var downloadLink = document.createElement("a");
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = "student_attendance_report.csv";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}
</script>

</body>
</html>
