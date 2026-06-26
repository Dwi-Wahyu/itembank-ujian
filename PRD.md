# Product Requirements Document (PRD): FKG Item Bank & CBT System

## 1. Project Overview
A specialized Computer Based Test (CBT) and Item Bank system for the Faculty of Dentistry (FKG). The system manages a large pool of questions (Theory & Practice) and orchestrates high-stakes exams with automated grading and anti-cheat mechanisms.

- **Tech Stack:** PHP 8.1+ (CodeIgniter 4), MariaDB/MySQL.
- **Libraries:** PhpSpreadsheet (Excel), PhpWord (Docx), Dompdf (PDF), Select2, Summernote.
- **Target OS:** Linux (optimized for case-sensitive filesystems).

---

## 2. User Roles & Permissions

| Level | Role | Scope & Permissions |
| :--- | :--- | :--- |
| **0** | **Superadmin** | Full system access, including User Management and system resets. |
| **1** | **Admin** | Manage master data, bank soal, and ujian orchestration. |
| **2** | **Dekan** | View-only dashboard and reports (Management level). |
| **3** | **Ketua** | Oversight on exams and question publishing. |
| **4** | **Reviewer** | Restricted to `admin/soal`, `admin/options`, and `admin/praktek/aspek`. Can provide feedback and set status to `publish/reject`. |
| **5** | **Dosen** | Question creator. Can only view and edit their own drafts. |
| **-** | **Mahasiswa** | Participant. Restricted to the exam interface during scheduled windows. |

---

## 3. Core Modules & Technical Workflows

### 3.1. Item Bank (Bank Soal)
#### Theory Questions (`ujian_teori`)
- **Structure:** Vignette (case study), Question, Options (A-E), Key, Weights (optional), Reference, and Reason.
- **Register Code:** Format: `{id}/{komp_kode}/{penyakit_kode}/{bidang_kode}/{dd/mm/yyyy}`.
- **Status Lifecycle:** `draft` (0) -> `review` (1) -> `publish` (2) / `reject` (3).
- **Media:** Multi-image support (JSON array of filenames). Uploaded to `public/uploads/soal_teori`.
- **Import:** Supports "Wide" (header-based) and "Vertical" (token-based: `soal|register` and `pilihan|register|A-E`) Excel formats.

#### Practice Questions (OSCE - `ujian_praktek`)
- **Structure:** Scenario, Examiner Tasks, Candidate Tasks, Media (images/PDF).
- **Rubric (Aspek):** Defined per question. Options are parsed from HTML strings (e.g., `0 : Salah, 1 : Benar`).
- **Export:** Exportable to Docx via PhpWord for paper-based station backups.

### 3.2. Theory CBT Module (`Teori`)
- **Attempt Tracking (`ujian_attempt`):**
    - **Randomization:** Question IDs are shuffled once per participant and stored in `order_json`.
    - **Timer:** Server-authoritative `remaining_seconds`.
    - **Anti-Cheat:** Heartbeat logs violations for `blur` (switching tabs), `visibility` (minimizing), and `fullscreen-exit`.
    - **Termination:** Auto-submission occurs if time expires or violations reach threshold (default: 3).
- **Grading:** Automatic scoring upon completion. Stores `benar`, `salah`, `kosong`, and `nilai`.

### 3.3. OSCE CBT Module (`Osce`)
- **Examiner Workflow:** Station-based dashboard. Examiners select a participant from the station queue.
- **GPS (Global Performance Score):** Subjective evaluation (0: Fail, 1: Borderline, 2: Pass).
- **Persistence:** Atomic transactions for saving total score (`jawaban_osce`) and granular aspect-level scores (`jawaban_osce_aspek`).

### 3.4. Administrative Management
- **Exam Sesi:** Managed in `buat_teori` (Theory) and `osce` (Practice).
- **Enrollment (`admin_cbt`):** Links students to exam codes. Generates a unique `no_ujian` (Format: `{ExamCode}{4-digit sequence}`).
- **Reports:** Excel exports for participant scores and statistics.

---

## 4. Technical Constraints & Security

### 4.1. Concurrency Management
- **Session Locking:** To prevent performance bottlenecks during concurrent exams, high-frequency endpoints (`jawab`, `heartbeat`) call `@session_write_close()` immediately after reading the session.

### 4.2. Database Schema Highlights
- **`admin_cbt`:** The bridge table for all enrollments.
- **`ujian_teori`:** Central repository for MCQ items.
- **`ujian_attempt`:** Records the lifecycle of a theory exam session.
- **`osce`:** Defines a station-based practical exam session.

### 4.3. Security Mandates
- **Filter Guard:** All admin routes require `adminauth`. Specific role-level arguments (e.g., `adminauth:0,1`) are used in routes.
- **CSRF Token Rotation:** Every AJAX response (HTML fragments) must refresh the CSRF token via `X-CSRF-TOKEN` headers to prevent stale tokens.
- **Fingerprint Validation:** Auth library validates user fingerprints to prevent session hijacking.

---

## 5. Development Standards
- **Routing:** Grouped by namespace (`Modules\Admin`, `Modules\Teori`, `Modules\Osce`).
- **Templates:** Base views use common layouts for Admin, Student, and Examiner portals.
- **File Handling:** Use `FCPATH` for uploads and `base_url()` for asset access. Files must be sanitized using `basename()`.
