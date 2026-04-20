import { computed, ref } from 'vue';

/**
 * useWeekMatrix - Helper for weekly matrix calculations
 */
export function useWeekMatrix(dateRangeRef) {
  /**
   * Parse dates and calculate week boundaries
   */
  const getWeekDays = (weekStartDate) => {
    const start = new Date(weekStartDate);
    const days = [];
    
    for (let i = 0; i < 7; i++) {
      const d = new Date(start);
      d.setDate(d.getDate() + i);
      days.push({
        date: d,
        dateString: d.toISOString().split('T')[0],
        dayOfWeek: ['CN', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'][d.getDay()],
        dayNumber: d.getDate(),
      });
    }
    return days;
  };
  
  /**
   * Get all weeks in the date range
   */
  const getWeeksInRange = (fromDate, toDate) => {
    const from = new Date(fromDate);
    const to = new Date(toDate);
    const weeks = [];
    
    let current = new Date(from);
    // Move to Monday of the week containing fromDate
    const day = current.getDay();
    const diff = current.getDate() - day + (day === 0 ? -6 : 1);
    current.setDate(diff);
    
    while (current <= to) {
      const weekStart = new Date(current);
      weeks.push({
        startDate: weekStart.toISOString().split('T')[0],
        startDateObj: new Date(weekStart),
        weekNumber: `Tuần ${Math.ceil((weekStart.getDate() + 6) / 7)}`,
      });
      current.setDate(current.getDate() + 7);
    }
    
    return weeks;
  };
  
  /**
   * Current week being displayed
   */
  const currentWeekIndex = ref(0);
  
  const weeks = computed(() => {
    if (!dateRangeRef.value?.from || !dateRangeRef.value?.to) return [];
    return getWeeksInRange(dateRangeRef.value.from, dateRangeRef.value.to);
  });
  
  const currentWeek = computed(() => {
    if (weeks.value.length === 0) return null;
    return weeks.value[currentWeekIndex.value] || weeks.value[0];
  });
  
  const currentWeekDays = computed(() => {
    if (!currentWeek.value) return [];
    return getWeekDays(currentWeek.value.startDate);
  });
  
  const canGoPrevWeek = computed(() => currentWeekIndex.value > 0);
  const canGoNextWeek = computed(() => currentWeekIndex.value < weeks.value.length - 1);
  
  const goToPrevWeek = () => {
    if (canGoPrevWeek.value) currentWeekIndex.value--;
  };
  
  const goToNextWeek = () => {
    if (canGoNextWeek.value) currentWeekIndex.value++;
  };
  
  return {
    weeks,
    currentWeekIndex,
    currentWeek,
    currentWeekDays,
    canGoPrevWeek,
    canGoNextWeek,
    goToPrevWeek,
    goToNextWeek,
    getWeekDays,
    getWeeksInRange,
  };
}

/**
 * formatDateDisplay - Format date for display
 */
export function formatDateDisplay(dateString) {
  const d = new Date(dateString + 'T00:00:00');
  return `${d.getDate()}/${d.getMonth() + 1}`;
}

/**
 * getShiftDisplay - Get display text for shift
 */
export function getShiftDisplay(shift) {
  if (!shift) return 'Chưa gán';
  if (shift.is_day_off) return 'Nghỉ';
  return shift.shift_code || shift.shift_name || 'CA';
}

/**
 * getAssignmentForDate - Get assignment or override for employee on specific date
 */
export function getAssignmentForDate(employeeId, dateString, assignments, overrides) {
  // Check override first
  const override = overrides.find(
    o => o.employee_id === employeeId && o.work_date === dateString
  );
  if (override) {
    return { type: 'override', data: override };
  }
  
  // Check base assignment
  const assignment = assignments.find(
    a => a.employee_id === employeeId && a.effective_date <= dateString &&
           (!a.expiry_date || a.expiry_date >= dateString)
  );
  if (assignment) {
    return { type: 'base', data: assignment };
  }
  
  return null;
}
