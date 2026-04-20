param(
    [string]$BaseUrl = "http://localhost/api/v1",
    [string]$Email = "hai.do@company.com",
    [string]$Password = "NV0009"
)

$ErrorActionPreference = "Stop"

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Path,
        [object]$Body = $null,
        [string]$Token = ""
    )

    $uri = "$BaseUrl$Path"
    $headers = @{ "Content-Type" = "application/json" }
    if ($Token -ne "") {
        $headers["Authorization"] = "Bearer $Token"
    }

    if ($null -eq $Body) {
        return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers -TimeoutSec 30
    }

    $json = $Body | ConvertTo-Json -Depth 10
    return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers -Body $json -TimeoutSec 30
}

function Login-Api {
    $login = Invoke-Api -Method "POST" -Path "/auth/login" -Body @{
        company_email = $Email
        password = $Password
    }

    return [string]($login.data.access_token)
}

function Prepare-Schedule {
    param(
        [string]$Token,
        [int]$EmployeeId,
        [string]$WorkDate,
        [int]$ShiftTypeId = 1
    )

    $assign = Invoke-Api -Method "POST" -Path "/team-schedule/assign" -Token $Token -Body @{
        employee_id = $EmployeeId
        shift_type_id = $ShiftTypeId
        effective_date = $WorkDate
        notes = "HRM demo verification"
    }

    $publish = Invoke-Api -Method "POST" -Path "/team-schedule/publish" -Token $Token -Body @{
        scope_type = "EMPLOYEE"
        scope_id = $EmployeeId
        from_date = $WorkDate
        to_date = $WorkDate
        notes = "HRM demo verification publish"
    }

    return @{
        assignment_id = $assign.data.assignment_id
        publish_log_id = $publish.data.publish_log_id
    }
}

function Get-DailyEntry {
    param(
        [string]$Token,
        [int]$EmployeeId,
        [string]$WorkDate
    )

    return (Invoke-Api -Method "GET" -Path "/timesheet/daily?employee_id=$EmployeeId&work_date=$WorkDate" -Token $Token).data
}

function Get-Exceptions {
    param(
        [string]$Token,
        [int]$EmployeeId,
        [string]$WorkDate
    )

    return (Invoke-Api -Method "POST" -Path "/timesheet/exceptions" -Token $Token -Body @{
        employee_ids = @($EmployeeId)
        from_date = $WorkDate
        to_date = $WorkDate
    }).data
}

function Get-PayrollExport {
    param(
        [string]$Token,
        [int]$EmployeeId,
        [string]$WorkDate
    )

    return (Invoke-Api -Method "POST" -Path "/timesheet/payroll-export" -Token $Token -Body @{
        employee_ids = @($EmployeeId)
        from_date = $WorkDate
        to_date = $WorkDate
    }).data[0]
}

function Save-Attendance {
    param(
        [string]$Token,
        [hashtable]$Payload
    )

    $employeeId = [int]$Payload.employee_id
    $attendanceDate = [string]$Payload.attendance_date
    $existingItems = (Invoke-Api -Method "GET" -Path "/attendances?date_from=$attendanceDate&date_to=$attendanceDate&page=1&per_page=200" -Token $Token).data
    $existing = $existingItems | Where-Object {
        [int]($_.employee_id) -eq $employeeId -and [string]($_.attendance_date) -eq $attendanceDate
    } | Select-Object -First 1

    if ($null -ne $existing) {
        $attendanceId = [int]$existing.attendance_id
        Invoke-Api -Method "DELETE" -Path "/attendances/$attendanceId" -Token $Token | Out-Null
    }

    return Invoke-Api -Method "POST" -Path "/attendances" -Token $Token -Body $Payload
}

$token = Login-Api

$results = [ordered]@{}

# Scenario 1: Normal working day
$normalDate = "2026-05-11"
$normalPrep = Prepare-Schedule -Token $token -EmployeeId 22 -WorkDate $normalDate
$normalAttendance = Save-Attendance -Token $token -Payload @{
    employee_id = 22
    attendance_date = $normalDate
    shift_type_id = 1
    check_in_time = "$normalDate 08:00:00"
    check_out_time = "$normalDate 17:30:00"
    check_in_method = "MOBILE"
    check_out_method = "MOBILE"
    status = "DA_DUYET"
    notes = "Demo normal attendance"
}
$results.normal_workday = [ordered]@{
    date = $normalDate
    schedule = $normalPrep
    attendance_id = $normalAttendance.data.attendance_id
    daily = Get-DailyEntry -Token $token -EmployeeId 22 -WorkDate $normalDate
    payroll = Get-PayrollExport -Token $token -EmployeeId 22 -WorkDate $normalDate
}

# Scenario 2: Missing checkout -> exception -> HR adjustment
$exceptionDate = "2026-05-12"
$exceptionPrep = Prepare-Schedule -Token $token -EmployeeId 23 -WorkDate $exceptionDate
$exceptionAttendance = Save-Attendance -Token $token -Payload @{
    employee_id = 23
    attendance_date = $exceptionDate
    shift_type_id = 1
    check_in_time = "$exceptionDate 08:02:00"
    check_in_method = "MOBILE"
    status = "DA_DUYET"
    notes = "Demo missing checkout"
}
$exceptionDailyBefore = Get-DailyEntry -Token $token -EmployeeId 23 -WorkDate $exceptionDate
$exceptionListBefore = Get-Exceptions -Token $token -EmployeeId 23 -WorkDate $exceptionDate
$exceptionRequest = Invoke-Api -Method "POST" -Path "/exceptions/request" -Token $token -Body @{
    employee_id = 23
    attendance_event_id = $exceptionAttendance.data.attendance_id
    reason = "Quen checkout khi ket thuc ngay lam viec"
    requested_at = "$exceptionDate 17:40:00"
}
$exceptionApproved = Invoke-Api -Method "POST" -Path "/exceptions/$($exceptionRequest.data.exception_id)/approve-once" -Token $token -Body @{
    decision = "APPROVE"
    note = "Manager approves one-time exception for HR adjustment"
    valid_minutes = 60
}
$exceptionAttendanceFixed = Invoke-Api -Method "PATCH" -Path "/attendances/$($exceptionAttendance.data.attendance_id)" -Token $token -Body @{
    check_out_time = "$exceptionDate 17:35:00"
    check_out_method = "MANUAL"
    status = "DA_DUYET"
    approved_by = 9
    approved_date = "$exceptionDate 18:00:00"
    notes = "HR completed checkout after approved exception"
}
$results.missing_checkout_recovery = [ordered]@{
    date = $exceptionDate
    schedule = $exceptionPrep
    attendance_id = $exceptionAttendance.data.attendance_id
    daily_before = $exceptionDailyBefore
    exceptions_before = $exceptionListBefore
    exception_request = $exceptionRequest.data
    exception_approval = $exceptionApproved.data
    attendance_fixed = $exceptionAttendanceFixed.data
    daily_after = Get-DailyEntry -Token $token -EmployeeId 23 -WorkDate $exceptionDate
    exceptions_after = Get-Exceptions -Token $token -EmployeeId 23 -WorkDate $exceptionDate
    payroll_after = Get-PayrollExport -Token $token -EmployeeId 23 -WorkDate $exceptionDate
}

# Scenario 3: Approved full-day leave
$leaveDate = "2026-05-13"
$leavePrep = Prepare-Schedule -Token $token -EmployeeId 24 -WorkDate $leaveDate
$leaveCreated = Invoke-Api -Method "POST" -Path "/leave-requests" -Token $token -Body @{
    requester_id = 24
    request_date = $leaveDate
    leave_type_id = 1
    employee_id = 24
    from_date = $leaveDate
    to_date = $leaveDate
    number_of_days = 1
    reason = "Demo annual leave"
    status = "CHO_DUYET"
}
$leaveManagerStep = Invoke-Api -Method "PATCH" -Path "/leave-requests/$($leaveCreated.data.leave_request.leave_request_id)" -Token $token -Body @{
    status = "DANG_XU_LY"
}
$leaveHrStep = Invoke-Api -Method "PATCH" -Path "/leave-requests/$($leaveCreated.data.leave_request.leave_request_id)" -Token $token -Body @{
    status = "DA_DUYET"
}
$results.full_day_leave = [ordered]@{
    date = $leaveDate
    schedule = $leavePrep
    leave_request_id = $leaveCreated.data.leave_request.leave_request_id
    request_id = $leaveCreated.data.request.request_id
    manager_step = $leaveManagerStep.data.request_status
    hr_step = $leaveHrStep.data.request_status
    daily = Get-DailyEntry -Token $token -EmployeeId 24 -WorkDate $leaveDate
    payroll = Get-PayrollExport -Token $token -EmployeeId 24 -WorkDate $leaveDate
}

# Scenario 4: Approved business trip without office attendance
$tripDate = "2026-05-14"
$tripPrep = Prepare-Schedule -Token $token -EmployeeId 25 -WorkDate $tripDate
$tripCreated = Invoke-Api -Method "POST" -Path "/requests" -Token $token -Body @{
    request_type_id = 4
    requester_id = 25
    request_date = $tripDate
    from_date = $tripDate
    to_date = $tripDate
    duration = 1
    reason = "Demo business trip"
    status = "CHO_DUYET"
}
$tripManagerStep = Invoke-Api -Method "PATCH" -Path "/requests/$($tripCreated.data.request_id)" -Token $token -Body @{
    status = "DANG_XU_LY"
}
$tripApproved = Invoke-Api -Method "PATCH" -Path "/requests/$($tripCreated.data.request_id)" -Token $token -Body @{
    status = "DA_DUYET"
}
$results.business_trip = [ordered]@{
    date = $tripDate
    schedule = $tripPrep
    request_id = $tripCreated.data.request_id
    manager_step = $tripManagerStep.data.status
    approved_step = $tripApproved.data.status
    daily = Get-DailyEntry -Token $token -EmployeeId 25 -WorkDate $tripDate
    payroll = Get-PayrollExport -Token $token -EmployeeId 25 -WorkDate $tripDate
}

# Scenario 5: Approved OT with actual late checkout >= 30 minutes
$otDate = "2026-05-15"
$otPrep = Prepare-Schedule -Token $token -EmployeeId 26 -WorkDate $otDate
$otRequest = Invoke-Api -Method "POST" -Path "/overtime-requests" -Token $token -Body @{
    employee_id = 26
    overtime_date = $otDate
    start_time = "17:30:00"
    end_time = "18:30:00"
    break_time = 0
    reason = "Demo overtime"
}
$otApproved = Invoke-Api -Method "PATCH" -Path "/overtime-requests/$($otRequest.data.overtime_id)" -Token $token -Body @{
    status = "DA_DUYET"
    approved_by = 9
    approved_date = "$otDate 18:31:00"
}
$otAttendance = Save-Attendance -Token $token -Payload @{
    employee_id = 26
    attendance_date = $otDate
    shift_type_id = 1
    check_in_time = "$otDate 08:00:00"
    check_out_time = "$otDate 18:15:00"
    check_in_method = "MOBILE"
    check_out_method = "MOBILE"
    status = "DA_DUYET"
    notes = "Demo OT with approved request"
}
$results.approved_ot = [ordered]@{
    date = $otDate
    schedule = $otPrep
    overtime_id = $otRequest.data.overtime_id
    overtime_status = $otApproved.data.status
    attendance_id = $otAttendance.data.attendance_id
    daily = Get-DailyEntry -Token $token -EmployeeId 26 -WorkDate $otDate
    payroll = Get-PayrollExport -Token $token -EmployeeId 26 -WorkDate $otDate
}

# Scenario 6: Late checkout without approved OT should not create OT minutes
$lateNoOtDate = "2026-05-18"
$lateNoOtPrep = Prepare-Schedule -Token $token -EmployeeId 27 -WorkDate $lateNoOtDate
$lateNoOtAttendance = Save-Attendance -Token $token -Payload @{
    employee_id = 27
    attendance_date = $lateNoOtDate
    shift_type_id = 1
    check_in_time = "$lateNoOtDate 08:01:00"
    check_out_time = "$lateNoOtDate 18:10:00"
    check_in_method = "MOBILE"
    check_out_method = "MOBILE"
    status = "DA_DUYET"
    notes = "Late checkout without OT approval"
}
$results.late_checkout_without_ot = [ordered]@{
    date = $lateNoOtDate
    schedule = $lateNoOtPrep
    attendance_id = $lateNoOtAttendance.data.attendance_id
    daily = Get-DailyEntry -Token $token -EmployeeId 27 -WorkDate $lateNoOtDate
    payroll = Get-PayrollExport -Token $token -EmployeeId 27 -WorkDate $lateNoOtDate
}

$results.audit_tail = (Invoke-Api -Method "GET" -Path "/workflow-governance/audit-logs?page=1&per_page=10" -Token $token).data

$results | ConvertTo-Json -Depth 12
