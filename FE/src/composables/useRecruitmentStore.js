import { reactive, computed, watch } from 'vue'
import { loadMockData, mockJobPostings, mockDepartments } from '@/mock-data/index.js'
import { BE_API_BASE, getAccessToken } from '@/services/runtimeConfig.js'
import { clearAuthSession } from '@/services/session.js'

const STATUS_MAP = {
  CHỜ_HR_DUYỆT: { key: 'pending_hr', label: 'Chờ HR duyệt', color: 'warning' },
  CHỜ_TP_DUYỆT: { key: 'pending_mgr', label: 'Chờ TP thẩm định', color: 'brand' },
  TP_ĐÃ_DUYỆT: { key: 'mgr_approved', label: 'TP đã duyệt', color: 'indigo' },
  ĐANG_PHỎNG_VẤN: { key: 'interviewing', label: 'Đang phỏng vấn', color: 'purple' },
  TRÚNG_TUYỂN: { key: 'pass', label: 'Trúng tuyển', color: 'success' },
  TỪ_CHỐI: { key: 'fail', label: 'Từ chối', color: 'danger' },
}

const LOCAL_KEY = 'aet_hrm_applications'

const _store = reactive({
  applications: [],
  hydrated: false,
})

let _nextId = 1
let _positionsCache = []

const getToken = () => getAccessToken()
const isAuthenticated = () => Boolean(getToken())

const parseLocalStore = () => {
  try {
    const raw = localStorage.getItem(LOCAL_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return Array.isArray(parsed) ? parsed : null
  } catch {
    return null
  }
}

const normalizeStatus = (status) => {
  const key = String(status || '').trim().toUpperCase()
  if (STATUS_MAP[key]) return key
  return 'CHỜ_HR_DUYỆT'
}

const rawToBackendStatus = (statusRaw) => {
  const key = normalizeStatus(statusRaw)
  if (key === 'ĐANG_PHỎNG_VẤN') return 'INTERVIEWING'
  if (key === 'TRÚNG_TUYỂN') return 'PASSED'
  if (key === 'TỪ_CHỐI') return 'REJECTED'
  return 'SCREENING'
}

const workflowToRaw = (frontendStatus, fallback = 'CHỜ_HR_DUYỆT') => {
  const key = String(frontendStatus || '').trim().toLowerCase()
  if (key === 'pending_hr') return 'CHỜ_HR_DUYỆT'
  if (key === 'pending_mgr') return 'CHỜ_TP_DUYỆT'
  if (key === 'mgr_approved') return 'TP_ĐÃ_DUYỆT'
  if (key === 'interviewing') return 'ĐANG_PHỎNG_VẤN'
  if (key === 'pass') return 'TRÚNG_TUYỂN'
  if (key === 'fail') return 'TỪ_CHỐI'
  return fallback
}

const inferFrontendWorkflow = (candidate = {}) => {
  if (candidate.frontend_workflow_status) return candidate.frontend_workflow_status
  const appStatus = String(candidate.application_status || '').toUpperCase()
  const workflow = String(candidate.workflow_status || '').toUpperCase()
  if (appStatus === 'REJECTED') return 'fail'
  if (appStatus === 'PASSED' || appStatus === 'HIRED') return 'pass'
  if (appStatus === 'INTERVIEWING') return 'interviewing'
  if (workflow === 'APPROVED') return 'mgr_approved'
  if (workflow === 'PENDING' && appStatus === 'SCREENING') return 'pending_mgr'
  return 'pending_hr'
}

const shape = (a) => {
  const statusRaw = normalizeStatus(a.status)
  const st = STATUS_MAP[statusRaw] || STATUS_MAP.CHỜ_HR_DUYỆT
  return {
    id: a.applicationId,
    applicationId: a.applicationId,
    jobId: a.jobId,
    departmentId: a.departmentId,
    name: a.fullName,
    email: a.email,
    phone: a.phone,
    initials: a.avatarInitials || a.fullName?.split(' ').map(w => w[0]).slice(-2).join('').toUpperCase(),
    position: a.jobTitle,
    positionName: a.positionName,
    department: a.departmentName,
    address: a.address || '',
    cvUrl: a.cvUrl || '',
    skills: a.skills || [],
    education: a.education || {},
    workExperience: a.workExperience || [],
    coverLetter: a.coverLetter || '',
    aiScore: a.aiMatchScore || 0,
    aiRemarks: a.aiMatchRemarks || '',
    aiScoringStatus: a.aiScoringStatus || 'PENDING',
    aiScoringError: a.aiScoringError || '',
    aiSemanticScore: a.aiSemanticScore ?? null,
    aiMustHaveScore: a.aiMustHaveScore ?? null,
    aiNiceScore: a.aiNiceScore ?? null,
    aiExpScore: a.aiExpScore ?? null,
    aiMatchedSkills: Array.isArray(a.aiMatchedSkills) ? a.aiMatchedSkills : [],
    aiMissingSkills: Array.isArray(a.aiMissingSkills) ? a.aiMissingSkills : [],
    managerDecisionProposal: a.managerDecisionProposal || 'PENDING',
    date: a.appliedDate ? new Date(a.appliedDate).toLocaleDateString('vi-VN') : '',
    appliedDate: a.appliedDate,
    interviewDate: a.interviewDate,
    status: st.key,
    statusRaw,
    statusLabel: st.label,
    statusColor: st.color,
    reviewedByHR: a.reviewedByHR || null,
    managerReview: a.reviewedByManager || null,
    managerScore: a.managerScore || null,
    notes: a.notes || '',
    backendCandidateId: a.backendCandidateId || null,
    backendInterviewId: a.backendInterviewId || null,
    departmentManagerId: a.departmentManagerId || null,
    latestInterviewManagerId: a.latestInterviewManagerId || null,
  }
}

const sortByAppliedDateDesc = (items) =>
  [...items].sort((a, b) => new Date(b.appliedDate || 0) - new Date(a.appliedDate || 0))

const stripDate = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return null
  if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) return raw
  const parsed = new Date(raw)
  if (Number.isNaN(parsed.getTime())) return null
  const yyyy = parsed.getFullYear()
  const mm = String(parsed.getMonth() + 1).padStart(2, '0')
  const dd = String(parsed.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

const stripTime = (value) => {
  const raw = String(value || '').trim()
  if (!raw) return '00:00:00'
  if (/^\d{2}:\d{2}:\d{2}$/.test(raw)) return raw
  if (/^\d{2}:\d{2}$/.test(raw)) return `${raw}:00`
  const parsed = new Date(`1970-01-01T${raw}`)
  if (Number.isNaN(parsed.getTime())) return '00:00:00'
  const hh = String(parsed.getHours()).padStart(2, '0')
  const mm = String(parsed.getMinutes()).padStart(2, '0')
  const ss = String(parsed.getSeconds()).padStart(2, '0')
  return `${hh}:${mm}:${ss}`
}

const isFileLike = (value) => typeof File !== 'undefined' && value instanceof File

const persistLocal = () => {
  localStorage.setItem(LOCAL_KEY, JSON.stringify(_store.applications))
}

watch(
  () => _store.applications,
  () => persistLocal(),
  { deep: true }
)

const fromBackendCandidate = (item = {}) => {
  const frontendWorkflow = inferFrontendWorkflow(item)
  const status = workflowToRaw(frontendWorkflow)
  const aiScore =
    item.ai_score !== null && item.ai_score !== undefined
      ? Number(item.ai_score)
      : 0

  const interviewDate = item.latest_interview_date
    ? `${item.latest_interview_date}T${String(item.latest_interview_time || '00:00:00').slice(0, 8)}`
    : item.suggested_interview_date
      ? `${item.suggested_interview_date}T${String(item.suggested_interview_time || '00:00:00').slice(0, 8)}`
      : null

  return {
    applicationId: Number(item.candidate_id),
    backendCandidateId: Number(item.candidate_id),
    backendInterviewId: item.latest_interview_id ? Number(item.latest_interview_id) : null,
    departmentManagerId: Number(item.department_manager_id) || null,
    latestInterviewManagerId: Number(item.latest_interview_manager_id) || null,
    jobId: Number(item.recruitment_position_id) || null,
    jobTitle: item.position_name || '',
    departmentId: Number(item.department_id) || null,
    departmentName: item.department_name || '',
    positionName: item.position_name || '',
    fullName: item.full_name || 'Unknown candidate',
    email: item.email || '',
    phone: item.phone_number || '',
    address: '',
    avatarInitials: (item.full_name || 'UV')
      .split(' ')
      .map((w) => w[0])
      .slice(-2)
      .join('')
      .toUpperCase(),
    cvUrl: item.cv_download_url || item.cv_url || '',
    skills: [],
    education: {},
    workExperience: [],
    coverLetter: '',
    aiMatchScore: Number.isFinite(aiScore) ? Math.round(aiScore) : 0,
    aiMatchRemarks: item.ai_scoring_error || item.notes || '',
    aiScoringStatus: String(item.ai_scoring_status || 'PENDING').toUpperCase(),
    aiScoringError: item.ai_scoring_error || '',
    aiSemanticScore: item.ai_semantic_score !== null && item.ai_semantic_score !== undefined ? Number(item.ai_semantic_score) : null,
    aiMustHaveScore: item.ai_must_have_score !== null && item.ai_must_have_score !== undefined ? Number(item.ai_must_have_score) : null,
    aiNiceScore: item.ai_nice_score !== null && item.ai_nice_score !== undefined ? Number(item.ai_nice_score) : null,
    aiExpScore: item.ai_exp_score !== null && item.ai_exp_score !== undefined ? Number(item.ai_exp_score) : null,
    aiMatchedSkills: Array.isArray(item.ai_matched_skills) ? item.ai_matched_skills : [],
    aiMissingSkills: Array.isArray(item.ai_missing_skills) ? item.ai_missing_skills : [],
    managerDecisionProposal: item.manager_decision_proposal || 'PENDING',
    status,
    appliedDate: item.applied_at ? `${item.applied_at}T00:00:00` : new Date().toISOString(),
    reviewedByHR: null,
    reviewedByManager: item.manager_review_notes || null,
    managerScore: item.manager_score !== null && item.manager_score !== undefined ? Number(item.manager_score) : null,
    interviewDate,
    notes: item.notes || '',
  }
}

const applyBackendCandidateToRecord = (record, saved) => {
  if (!saved || typeof saved !== 'object') return
  if (saved.candidate_id) record.backendCandidateId = Number(saved.candidate_id)
  if (saved.recruitment_position_id) record.jobId = Number(saved.recruitment_position_id)
  if (saved.position_name) {
    record.jobTitle = String(saved.position_name)
    record.positionName = String(saved.position_name)
  }
  if (saved.department_id !== undefined && saved.department_id !== null) {
    const departmentId = Number(saved.department_id)
    record.departmentId = Number.isFinite(departmentId) && departmentId > 0 ? departmentId : null
  }
  if (saved.department_name !== undefined && saved.department_name !== null) {
    record.departmentName = String(saved.department_name || '')
  }
  if (saved.cv_download_url || saved.cv_url) record.cvUrl = saved.cv_download_url || saved.cv_url
  if (saved.application_status) {
    const frontendStatus = workflowToRaw(saved.frontend_workflow_status, record.status)
    record.status = normalizeStatus(frontendStatus)
  }

  if (saved.ai_score !== undefined && saved.ai_score !== null) {
    const score = Number(saved.ai_score)
    if (Number.isFinite(score)) record.aiMatchScore = Math.round(score)
  }
  record.aiScoringStatus = String(saved.ai_scoring_status || record.aiScoringStatus || 'PENDING').toUpperCase()
  record.aiScoringError = saved.ai_scoring_error || ''
  record.aiSemanticScore = saved.ai_semantic_score !== undefined && saved.ai_semantic_score !== null
    ? Number(saved.ai_semantic_score)
    : null
  record.aiMustHaveScore = saved.ai_must_have_score !== undefined && saved.ai_must_have_score !== null
    ? Number(saved.ai_must_have_score)
    : null
  record.aiNiceScore = saved.ai_nice_score !== undefined && saved.ai_nice_score !== null
    ? Number(saved.ai_nice_score)
    : null
  record.aiExpScore = saved.ai_exp_score !== undefined && saved.ai_exp_score !== null
    ? Number(saved.ai_exp_score)
    : null
  record.aiMatchedSkills = Array.isArray(saved.ai_matched_skills) ? saved.ai_matched_skills : []
  record.aiMissingSkills = Array.isArray(saved.ai_missing_skills) ? saved.ai_missing_skills : []
  if (saved.manager_decision_proposal) {
    record.managerDecisionProposal = saved.manager_decision_proposal
  }
  if (saved.manager_review_notes) {
    record.reviewedByManager = saved.manager_review_notes
  }
  if (saved.manager_score !== undefined && saved.manager_score !== null) {
    const score = Number(saved.manager_score)
    record.managerScore = Number.isFinite(score) ? score : null
  }
}

const toLegacyBaseRecord = (item = {}) => ({
  applicationId: Number(item.applicationId),
  backendCandidateId: item.backendCandidateId || null,
  backendInterviewId: item.backendInterviewId || null,
  departmentManagerId: item.departmentManagerId || null,
  latestInterviewManagerId: item.latestInterviewManagerId || null,
  jobId: item.jobId || null,
  jobTitle: item.jobTitle || item.positionName || '',
  departmentId: item.departmentId || null,
  departmentName: item.departmentName || '',
  positionId: item.positionId || null,
  positionName: item.positionName || '',
  fullName: item.fullName || '',
  email: item.email || '',
  phone: item.phone || '',
  address: item.address || '',
  avatarInitials: item.avatarInitials || '',
  cvUrl: item.cvUrl || '',
  skills: item.skills || [],
  education: item.education || {},
  workExperience: item.workExperience || [],
  coverLetter: item.coverLetter || '',
  aiMatchScore: Number(item.aiMatchScore || 0),
  aiMatchRemarks: item.aiMatchRemarks || '',
  aiScoringStatus: item.aiScoringStatus || 'PENDING',
  aiScoringError: item.aiScoringError || '',
  aiSemanticScore: item.aiSemanticScore ?? null,
  aiMustHaveScore: item.aiMustHaveScore ?? null,
  aiNiceScore: item.aiNiceScore ?? null,
  aiExpScore: item.aiExpScore ?? null,
  aiMatchedSkills: Array.isArray(item.aiMatchedSkills) ? item.aiMatchedSkills : [],
  aiMissingSkills: Array.isArray(item.aiMissingSkills) ? item.aiMissingSkills : [],
  managerDecisionProposal: item.managerDecisionProposal || 'PENDING',
  status: normalizeStatus(item.status),
  appliedDate: item.appliedDate || new Date().toISOString(),
  reviewedByHR: item.reviewedByHR || null,
  reviewedByManager: item.reviewedByManager || null,
  managerScore: item.managerScore || null,
  interviewDate: item.interviewDate || null,
  notes: item.notes || '',
})

const _idx = (applicationId) => _store.applications.findIndex((a) => String(a.applicationId) === String(applicationId))
const _removeLocal = (applicationId) => {
  const index = _idx(applicationId)
  if (index !== -1) {
    _store.applications.splice(index, 1)
  }
}

const _newId = () => {
  const id = _nextId
  _nextId += 1
  return id
}

const apiRequest = async (path, { method = 'GET', body, isMultipart = false } = {}) => {
  const token = getToken()
  if (!token) throw new Error('Missing auth token')
  const headers = {
    Authorization: `Bearer ${token}`,
  }
  if (!isMultipart) {
    headers['Content-Type'] = 'application/json'
  }

  const response = await fetch(`${BE_API_BASE}${path}`, {
    method,
    headers,
    body:
      body === undefined
        ? undefined
        : isMultipart
          ? body
          : JSON.stringify(body),
  })
  const payload = await response.json().catch(() => ({}))
  if (!response.ok || payload?.success === false) {
    const error = new Error(payload?.message || `BE request failed: ${response.status}`)
    error.status = response.status
    throw error
  }
  return payload?.data
}

const publicApiRequest = async (path, { method = 'GET', body, isMultipart = false } = {}) => {
  const headers = {}
  if (!isMultipart) {
    headers['Content-Type'] = 'application/json'
  }

  const response = await fetch(`${BE_API_BASE}${path}`, {
    method,
    headers,
    body:
      body === undefined
        ? undefined
        : isMultipart
          ? body
          : JSON.stringify(body),
  })

  const payload = await response.json().catch(() => ({}))
  if (!response.ok || payload?.success === false) {
    const error = new Error(payload?.message || `Public request failed: ${response.status}`)
    error.status = response.status
    throw error
  }
  return payload?.data
}

const loadPositionsCache = async () => {
  if (!isAuthenticated()) return []
  const data = await apiRequest('/recruitment-positions?page=1&per_page=500')
  _positionsCache = Array.isArray(data) ? data : []
  return _positionsCache
}

const findOrCreateRecruitmentPosition = async (record) => {
  if (!isAuthenticated()) return null
  if (_positionsCache.length === 0) {
    await loadPositionsCache()
  }
  const deptId = Number(record.departmentId || 0)
  const title = String(record.jobTitle || record.positionName || '').trim().toLowerCase()
  let found = _positionsCache.find((p) => {
    const sameDept = Number(p.department_id || 0) === deptId
    const sameTitle = String(p.position_name || '').trim().toLowerCase() === title
    return sameDept && sameTitle
  })
  if (found) return Number(found.recruitment_position_id)

  const positionCode = `REC-${new Date().getFullYear()}-${Math.floor(Math.random() * 900 + 100)}`
  const created = await apiRequest('/recruitment-positions', {
    method: 'POST',
    body: {
      position_code: positionCode,
      position_name: record.jobTitle || record.positionName || 'Vị trí tuyển dụng',
      department_id: deptId > 0 ? deptId : null,
      status: 'OPEN',
      vacancy_count: 1,
      employment_type: 'FULL_TIME',
    },
  })
  if (created?.recruitment_position_id) {
    _positionsCache.push(created)
    return Number(created.recruitment_position_id)
  }
  await loadPositionsCache()
  found = _positionsCache.find((p) => String(p.position_name || '').trim().toLowerCase() === title)
  return found ? Number(found.recruitment_position_id) : null
}

const uploadCandidateCv = async (candidateId, cvFile) => {
  if (!isFileLike(cvFile)) return null
  const formData = new FormData()
  formData.append('file', cvFile, cvFile.name || 'candidate-cv.pdf')

  const uploaded = await apiRequest(`/recruitment-candidates/${candidateId}/cv`, {
    method: 'POST',
    body: formData,
    isMultipart: true,
  })

  return uploaded?.cv_download_url || uploaded?.cv_url || null
}

const ensureBackendCandidate = async (record, cvFile = null) => {
  if (!isAuthenticated()) return null
  if (record.backendCandidateId) {
    if (isFileLike(cvFile)) {
      const cvUrl = await uploadCandidateCv(Number(record.backendCandidateId), cvFile)
      if (cvUrl) record.cvUrl = cvUrl
    }
    return Number(record.backendCandidateId)
  }

  const positionId = await findOrCreateRecruitmentPosition(record)
  if (!positionId) return null

  const fallbackCvUrl = typeof record.cvUrl === 'string' && !record.cvUrl.startsWith('data:')
    ? record.cvUrl
    : null

  const saved = await apiRequest('/recruitment-candidates', {
    method: 'POST',
    body: {
      full_name: record.fullName,
      email: record.email || null,
      phone_number: record.phone || null,
      recruitment_position_id: positionId,
      cv_url: fallbackCvUrl,
      source_channel: 'LANDING_PAGE',
      ai_score: Number(record.aiMatchScore || 0),
      application_status: rawToBackendStatus(record.status),
      applied_at: stripDate(record.appliedDate || new Date().toISOString()),
      notes: record.notes || null,
    },
  })

  applyBackendCandidateToRecord(record, saved)

  if (record.backendCandidateId && isFileLike(cvFile)) {
    const cvUrl = await uploadCandidateCv(record.backendCandidateId, cvFile)
    if (cvUrl) {
      record.cvUrl = cvUrl
    }
  }

  return record.backendCandidateId
}

const syncPublicApplication = async (record, cvFile) => {
  if (!isFileLike(cvFile)) return null

  const formData = new FormData()
  formData.append('full_name', record.fullName || 'Ứng viên')
  if (record.email) formData.append('email', record.email)
  if (record.phone) formData.append('phone_number', record.phone)
  formData.append('position_name', record.jobTitle || record.positionName || 'Vị trí tuyển dụng')
  const departmentId = Number(record.departmentId || 0)
  if (departmentId > 0) {
    formData.append('department_id', String(departmentId))
  }
  formData.append('source_channel', 'LANDING_PAGE')
  formData.append('application_status', 'NEW')
  formData.append('ai_score', String(Number(record.aiMatchScore || 0)))
  formData.append('applied_at', stripDate(record.appliedDate || new Date().toISOString()) || '')
  if (record.notes) formData.append('notes', record.notes)
  formData.append('file', cvFile, cvFile.name || 'candidate-cv.pdf')

  const saved = await publicApiRequest('/public/recruitment/applications', {
    method: 'POST',
    body: formData,
    isMultipart: true,
  })

  applyBackendCandidateToRecord(record, saved)

  return saved
}

const syncManagerReview = async (record, payload) => {
  const candidateId = await ensureBackendCandidate(record)
  if (!candidateId) return
  await apiRequest(`/recruitment-candidates/${candidateId}/manager-review`, {
    method: 'PATCH',
    body: payload,
  })
}

const syncInterview = async (record, date, time) => {
  const candidateId = await ensureBackendCandidate(record)
  if (!candidateId) return

  const backendDate = stripDate(date)
  const backendTime = stripTime(time)

  if (record.backendInterviewId) {
    const updated = await apiRequest(`/interviews/${record.backendInterviewId}`, {
      method: 'PATCH',
      body: {
        interview_date: backendDate,
        interview_time: backendTime,
        status: 'SCHEDULED',
      },
    })
    if (updated?.interview_id) record.backendInterviewId = Number(updated.interview_id)
  } else {
    const created = await apiRequest('/interviews', {
      method: 'POST',
      body: {
        candidate_id: candidateId,
        interview_date: backendDate,
        interview_time: backendTime,
        interview_mode: 'ONLINE',
      },
    })
    if (created?.interview_id) record.backendInterviewId = Number(created.interview_id)
  }
}

const syncCandidateStatus = async (record, statusRaw) => {
  const candidateId = await ensureBackendCandidate(record)
  if (!candidateId) return
  await apiRequest(`/recruitment-candidates/${candidateId}`, {
    method: 'PATCH',
    body: {
      application_status: rawToBackendStatus(statusRaw),
      notes: record.notes || null,
      ai_score: Number(record.aiMatchScore || 0),
    },
  })
}

const bootstrapStore = async () => {
  if (_store.hydrated) return

  const localApps = parseLocalStore()
  const seed = Array.isArray(localApps) ? localApps : []
  _store.applications = seed.map((item) => toLegacyBaseRecord(item))
  _nextId = Math.max(..._store.applications.map((a) => Number(a.applicationId || 0)), 0) + 1
  _store.hydrated = true

  if (!isAuthenticated()) return

  try {
    await loadMockData()
    await refreshRecruitmentCandidates()
  } catch (error) {
    if (Number(error?.status || 0) === 401) {
      clearAuthSession()
    }
    console.warn('[recruitment-store] load backend failed:', error?.message || error)
  }
}

bootstrapStore()

export async function refreshRecruitmentCandidates() {
  if (!isAuthenticated()) return false
  const candidatesData = await apiRequest('/recruitment-candidates?page=1&per_page=500')
  const candidates = Array.isArray(candidatesData) ? candidatesData : []

  _store.applications = sortByAppliedDateDesc(
    candidates.map((item) => fromBackendCandidate(item)).map((item) => toLegacyBaseRecord(item))
  )
  _nextId = Math.max(..._store.applications.map((a) => Number(a.applicationId || 0)), 0) + 1
  return true
}

export function submitApplication(formData) {
  const job = mockJobPostings.find((j) => j.jobId == formData.jobId)
  const normalizedDeptName = String(formData.departmentName || job?.departmentName || '').trim().toLowerCase()
  const inferredDeptIdByName = normalizedDeptName
    ? Number(
        mockDepartments.find((d) => String(d.departmentName || '').trim().toLowerCase() === normalizedDeptName)?.departmentId || 0,
      )
    : 0
  const resolvedDepartmentId =
    Number(formData.departmentId || 0) > 0
      ? Number(formData.departmentId)
      : Number(job?.departmentId || 0) > 0
        ? Number(job?.departmentId)
        : inferredDeptIdByName > 0
          ? inferredDeptIdByName
          : null

  const newApp = toLegacyBaseRecord({
    applicationId: _newId(),
    jobId: formData.jobId || null,
    jobTitle: formData.jobTitle || job?.title || '',
    departmentId: resolvedDepartmentId,
    departmentName: formData.departmentName || job?.departmentName || '',
    positionId: job?.positionId || null,
    positionName: job?.positionName || 'Chuyên viên',
    fullName: formData.fullName,
    email: formData.email,
    phone: formData.phone || '',
    address: '',
    avatarInitials: formData.fullName.split(' ').map((w) => w[0]).slice(-2).join('').toUpperCase(),
    cvUrl: formData.cvUrl || '',
    skills: formData.skills || [],
    education: { school: '', major: '', degree: '', graduationYear: null },
    workExperience: [],
    coverLetter: formData.coverLetter || '',
    aiMatchScore: Number(formData.aiMatchScore || 0),
    aiMatchRemarks: 'Hồ sơ mới từ Landing Page.',
    status: 'CHỜ_HR_DUYỆT',
    appliedDate: new Date().toISOString(),
    reviewedByHR: null,
    reviewedByManager: null,
    managerScore: null,
    interviewDate: null,
    notes: formData.notes || '',
  })
  _store.applications.unshift(newApp)

  // Landing CV submit should always use public endpoint to ensure
  // candidate + CV are stored atomically in one transaction.
  if (isFileLike(formData.cvFile)) {
    void syncPublicApplication(newApp, formData.cvFile).catch((error) => {
      if (!newApp.backendCandidateId) {
        _removeLocal(newApp.applicationId)
      }
      console.warn('[recruitment-store] public submit failed:', error?.message || error)
    })
  } else if (isAuthenticated()) {
    void ensureBackendCandidate(newApp, formData.cvFile).catch((error) => {
      if (!newApp.backendCandidateId) {
        _removeLocal(newApp.applicationId)
      }
      if (Number(error?.status || 0) === 401) {
        clearAuthSession()
      }
      console.warn('[recruitment-store] submit sync failed:', error?.message || error)
    })
  }

  return shape(newApp)
}

export function forwardToManager(applicationId, hrReviewerName = 'HR Admin', interviewData = null) {
  const i = _idx(applicationId)
  if (i === -1) return false
  const hasInterviewPlan = Boolean(interviewData && interviewData.date && interviewData.time)
  _store.applications[i].status = hasInterviewPlan ? 'ĐANG_PHỎNG_VẤN' : 'CHỜ_TP_DUYỆT'
  _store.applications[i].reviewedByHR = hrReviewerName

  if (hasInterviewPlan) {
    const isoDateTime = `${interviewData.date}T${interviewData.time}:00`
    _store.applications[i].interviewDate = isoDateTime
  }

  if (isAuthenticated()) {
    const record = _store.applications[i]
    const syncTasks = [
      syncManagerReview(record, {
        workflow_status: 'PENDING',
        manager_decision_proposal: 'PENDING',
        // HR forwarding should not fill manager review note.
        manager_review_notes: null,
        suggested_interview_date: interviewData?.date || null,
        suggested_interview_time: interviewData?.time || null,
      }),
    ]

    if (hasInterviewPlan) {
      syncTasks.push(syncInterview(record, interviewData.date, interviewData.time))
      syncTasks.push(syncCandidateStatus(record, 'ĐANG_PHỎNG_VẤN'))
    } else {
      syncTasks.push(syncCandidateStatus(record, 'CHỜ_TP_DUYỆT'))
    }

    void Promise.all(syncTasks).catch((error) => {
      console.warn('[recruitment-store] forward sync failed:', error?.message || error)
    })
  }

  return true
}

export function rejectApplication(applicationId, reason = 'Không phù hợp yêu cầu') {
  const i = _idx(applicationId)
  if (i === -1) return false
  _store.applications[i].status = 'TỪ_CHỐI'
  _store.applications[i].notes = reason

  if (isAuthenticated()) {
    const record = _store.applications[i]
    void Promise.all([
      syncManagerReview(record, {
        workflow_status: 'REJECTED',
        manager_decision_proposal: 'FAIL',
        manager_review_notes: reason,
      }).catch(() => null),
      syncCandidateStatus(record, 'TỪ_CHỐI'),
    ]).catch((error) => {
      console.warn('[recruitment-store] reject sync failed:', error?.message || error)
    })
  }

  return true
}

export async function submitManagerEvaluation(applicationId, evaluationData, decision) {
  const i = _idx(applicationId)
  if (i === -1) return false

  _store.applications[i].reviewedByManager = evaluationData.notes
  _store.applications[i].managerScore = evaluationData.score || null
  _store.applications[i].managerDecisionProposal = decision === 'approve' ? 'PASS' : 'FAIL'
  _store.applications[i].status = 'ĐANG_PHỎNG_VẤN'

  if (isAuthenticated()) {
    const record = _store.applications[i]
    await syncManagerReview(record, {
      manager_decision_proposal: decision === 'approve' ? 'PASS' : 'FAIL',
      manager_score: evaluationData.score ?? null,
      manager_review_notes: evaluationData.notes ?? null,
    })
    await refreshRecruitmentCandidates()
  }

  return true
}

export function scheduleInterview(applicationId, date, time, skipStatusChange = false) {
  const i = _idx(applicationId)
  if (i === -1) return false

  if (!skipStatusChange) {
    _store.applications[i].status = 'ĐANG_PHỎNG_VẤN'
  }

  const isoDateTime = `${date}T${time}:00`
  _store.applications[i].interviewDate = isoDateTime

  if (isAuthenticated()) {
    const record = _store.applications[i]
    void Promise.all([
      syncInterview(record, date, time),
      syncCandidateStatus(record, 'ĐANG_PHỎNG_VẤN'),
    ]).catch((error) => {
      console.warn('[recruitment-store] interview sync failed:', error?.message || error)
    })
  }

  return true
}

export function finalizeCandidate(applicationId, finalStatus) {
  const i = _idx(applicationId)
  if (i === -1) return false
  _store.applications[i].status = finalStatus === 'pass' ? 'TRÚNG_TUYỂN' : 'TỪ_CHỐI'

  if (isAuthenticated()) {
    const record = _store.applications[i]
    void syncCandidateStatus(record, record.status).catch((error) => {
      console.warn('[recruitment-store] finalize sync failed:', error?.message || error)
    })
  }
  return true
}

export async function retryCandidateAiScore(applicationId) {
  const i = _idx(applicationId)
  if (i === -1) return false
  const record = _store.applications[i]
  const candidateId = await ensureBackendCandidate(record)
  if (!candidateId) return false

  record.aiScoringStatus = 'PENDING'
  record.aiScoringError = ''
  record.aiMatchScore = 0

  const response = await apiRequest(`/recruitment-candidates/${candidateId}/ai-score/retry`, {
    method: 'POST',
  })
  if (response?.candidate) {
    applyBackendCandidateToRecord(record, response.candidate)
  }
  return true
}

export function useHRApplications() {
  const all = computed(() =>
    sortByAppliedDateDesc(_store.applications).map(shape)
  )

  const hrQueue = computed(() =>
    all.value.filter((c) => ['pending_hr', 'mgr_approved'].includes(c.status))
  )

  const hrPipeline = computed(() =>
    all.value.filter((c) => !['pass', 'fail'].includes(c.status))
  )

  return { all, hrQueue, hrPipeline }
}

export function useManagerApplications(departmentId, managerEmployeeId = null) {
  const deptIds = Array.isArray(departmentId)
    ? departmentId
      .map((value) => Number(value))
      .filter((value) => Number.isFinite(value) && value > 0)
    : [Number(departmentId)].filter((value) => Number.isFinite(value) && value > 0)

  const managerId = Number(managerEmployeeId || 0)

  const pendingEval = computed(() =>
    _store.applications
      .filter(
        (a) => {
          const statusRaw = normalizeStatus(a.status)
          if (!['CHỜ_TP_DUYỆT', 'TP_ĐÃ_DUYỆT', 'ĐANG_PHỎNG_VẤN'].includes(statusRaw)) {
            return false
          }

          if (String(a.managerDecisionProposal || 'PENDING').toUpperCase() !== 'PENDING') {
            return false
          }

          const deptId = Number(a.departmentId || 0)
          const interviewManagerId = Number(a.latestInterviewManagerId || 0)
          const positionManagerId = Number(a.departmentManagerId || 0)
          const isAssignedToManager = managerId > 0 && (interviewManagerId === managerId || positionManagerId === managerId)

          if (isAssignedToManager) return true
          if (deptIds.length === 0) return true
          if (deptId === 0) return true
          return deptIds.includes(deptId)
        }
      )
      .map(shape)
      .sort((a, b) => new Date(b.appliedDate || 0) - new Date(a.appliedDate || 0))
  )

  const deptInfo = mockDepartments.find((d) => deptIds.includes(Number(d.departmentId)))
  return { pendingEval, deptName: deptInfo?.departmentName || 'Phòng ban' }
}

export function useRecruitmentStore(filterDeptId = null, options = {}) {
  const managerId = Number(options?.managerId || 0)
  const candidates = computed(() => {
    let list = _store.applications
    if (filterDeptId) {
      const deptFilters = Array.isArray(filterDeptId)
        ? filterDeptId.map((v) => Number(v)).filter((v) => Number.isFinite(v) && v > 0)
        : [Number(filterDeptId)].filter((v) => Number.isFinite(v) && v > 0)

      if (deptFilters.length) {
        list = list.filter((a) => {
          const deptId = Number(a.departmentId || 0)
          const interviewManagerId = Number(a.latestInterviewManagerId || 0)
          const positionManagerId = Number(a.departmentManagerId || 0)
          if (managerId > 0 && (interviewManagerId === managerId || positionManagerId === managerId)) {
            return true
          }
          // Keep records with missing department mapping so managers can still review/interview.
          if (!deptId) return true
          return deptFilters.includes(deptId)
        })
      }
    }
    return list.map(shape).sort((a, b) => new Date(b.appliedDate || 0) - new Date(a.appliedDate || 0))
  })

  return {
    candidates,
    scheduleInterview,
    approveToManager: forwardToManager,
    submitManagerReview: (id, notes) => submitManagerEvaluation(id, { notes }, 'approve'),
    finalizeDecision: finalizeCandidate,
    rejectApplication,
    addApplication: submitApplication,
  }
}
