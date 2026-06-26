<?= $this->extend('Modules\Admin\Views\layouts\admin') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <h3 class="mb-3">Live Exam Proctoring</h3>

    <div class="card mb-4 border-primary shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="fa fa-cloud-download"></i> Offline Server Synchronization</h5>
            <span class="badge bg-light text-primary">Air-Gapped Mode</span>
        </div>
        <div class="card-body d-flex gap-3">
            <button id="btn-push-results" class="btn btn-warning px-4">
                <i class="fa fa-upload"></i> Push Final Grades to VPS
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white"><i class="fa fa-users"></i> Room Investigator Dashboard</h5>
            <button onclick="reloadProctorGrid()" class="btn btn-sm btn-outline-light">
                <i class="fa fa-refresh"></i> Refresh Status
            </button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered" id="proctor-table">
                <thead class="bg-light">
                    <tr>
                        <th>No Ujian</th>
                        <th>Mahasiswa</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Time Remaining</th>
                        <th class="text-center">Violations (Max 3)</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="live-student-rows">
                    <tr><td colspan="6" class="text-center text-muted">Loading live status...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$(document).ready(function() {
    const examId   = '<?= $ujian['id'] ?? 0; ?>';
    const examCode = '<?= esc($ujian['kode'] ?? ''); ?>';

    if (examId !== '0') {
        setInterval(reloadProctorGrid, 10000);
        reloadProctorGrid();
    }

    // ---- Pull ----
    $('#btn-pull-soal').click(function() {
        Swal.fire({
            title: 'Sinkronisasi Data Ujian',
            input: 'text',
            inputValue: examCode,
            inputLabel: 'Kode Ujian (dari VPS)',
            showCancelButton: true,
            confirmButtonText: 'Fetch',
            cancelButtonText: 'Batal',
            inputValidator: (v) => (!v.trim() ? 'Kode tidak boleh kosong' : null)
        }).then(result => {
            if (!result.isConfirmed) return;
            const code = result.value.trim();
            const $btn = $('#btn-pull-soal').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Downloading...');

            $.get('/admin/ujian/teori/pull/' + encodeURIComponent(code))
                .done(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                        $btn.prop('disabled', false).html('<i class="fa fa-download"></i> 1. Fetch Exam Data from VPS');
                    }
                })
                .fail(() => {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Pastikan server memiliki akses ke VPS.' });
                    $btn.prop('disabled', false).html('<i class="fa fa-download"></i> 1. Fetch Exam Data from VPS');
                });
        });
    });

    // ---- Push ----
    $('#btn-push-results').click(function() {
        Swal.fire({
            icon: 'question',
            title: 'Kirim Hasil Ujian?',
            html: `Semua nilai lokal untuk sesi <strong>${examCode}</strong> akan dikirim ke server utama.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b',
        }).then(result => {
            if (!result.isConfirmed) return;
            const $btn = $('#btn-push-results').prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Uploading...');

            $.post('/admin/ujian/teori/push/' + encodeURIComponent(examCode), {
                [csrfTokenName]: csrfTokenValue
            })
                .done(res => {
                    const icon = res.status === 'success' ? 'success' : 'error';
                    Swal.fire({ icon, title: res.status === 'success' ? 'Berhasil' : 'Gagal', text: res.message });
                    $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> 2. Push Final Grades to VPS');
                })
                .fail(() => {
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Gagal menghubungi server.' });
                    $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> 2. Push Final Grades to VPS');
                });
        });
    });
});

function reloadProctorGrid() {
    const examId = '<?= $ujian['id'] ?? 0; ?>';
    if (examId === '0') return;

    $.get('/admin/ujian/teori/live-status/' + examId, function(response) {
        let html = '';
        if (!response.students || response.students.length === 0) {
            html = '<tr><td colspan="6" class="text-center text-muted">Belum ada peserta atau data belum disinkronkan.</td></tr>';
        } else {
            response.students.forEach(function(stu) {
                const badgeColor = stu.status_ujian === 'selesai' ? 'bg-success'
                    : stu.status_ujian === 'mengerjakan' ? 'bg-primary' : 'bg-secondary';
                const alertClass = stu.violations >= 2 ? 'text-danger fw-bold' : '';
                html += `<tr>
                    <td class="fw-bold">${stu.no_ujian}</td>
                    <td>${stu.nama_mahasiswa}</td>
                    <td class="text-center"><span class="badge ${badgeColor}">${stu.status_ujian.toUpperCase()}</span></td>
                    <td class="text-center">${stu.remaining_time} mnt</td>
                    <td class="text-center ${alertClass}">
                        ${stu.violations > 0 ? '<i class="fa fa-warning text-warning"></i> ' : ''}
                        ${stu.violations} / 3
                    </td>
                    <td class="text-center">
                        ${stu.status_ujian === 'mengerjakan'
                            ? `<button class="btn btn-sm btn-danger py-0 px-2" onclick="forceSubmit('${stu.attempt_id}')">Stop</button>`
                            : '-'}
                    </td>
                </tr>`;
            });
        }
        $('#live-student-rows').html(html);
    });
}

function forceSubmit(attemptId) {
    Swal.fire({
        icon: 'warning',
        title: 'Paksa Submit?',
        text: 'Ujian peserta ini akan langsung diakhiri.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Akhiri',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545',
    }).then(result => {
        if (!result.isConfirmed) return;
        $.post('/admin/ujian/teori/force-submit/' + attemptId, {
            [csrfTokenName]: csrfTokenValue
        }, function() {
            reloadProctorGrid();
        }).fail(() => {
            Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat mengakhiri ujian.' });
        });
    });
}
</script>
<?= $this->endSection() ?>