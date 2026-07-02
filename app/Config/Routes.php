<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('', ['namespace' => 'Modules\Auth\Controllers'], static function ($routes) {
    $routes->get('/', 'AuthController::index');
    $routes->get('login', 'AuthController::index');
    
    $routes->post('auth/login', 'AuthController::login');
});

$routes->group('teori', ['namespace' => 'Modules\Teori\Controllers'], static function($r){
    $r->get('/', 'Auth::index');   // handle /teori (trailing slash)
    $r->get('',  'Auth::index');   // handle /teori (tanpa slash) — opsional aman
    $r->get('login', 'Auth::index');
    $r->post('login', 'Auth::login');
    $r->get('logout', 'Auth::logout');
    $r->get('dashboard', 'Dashboard::index');
    $r->get('csrf', 'Ujian::csrf'); 
    // UJIAN
    $r->get('ujian/hasil/(:num)', 'Ujian::hasil/$1');
    $r->get ('ujian/mulai',     'Ujian::mulai',     ['filter'=>'teoriauth']); // ?kode=...
    $r->get ('ujian/init',      'Ujian::init',      ['filter'=>'teoriauth']); // meta + soal + jawaban
    $r->post('ujian/jawab',     'Ujian::jawab',     ['filter'=>'teoriauth']); // autosave
    $r->post('ujian/heartbeat', 'Ujian::heartbeat', ['filter'=>'teoriauth']); // sisa detik & pelanggaran
    $r->post('ujian/finish',    'Ujian::finish',    ['filter'=>'teoriauth']); // submit akhir
    $r->match(['get','post'], 'attempt/status', 'Ujian::attemptStatus', ['filter'=>'teoriauth']);
});

$routes->group('e-osce', ['namespace' => 'Modules\Osce\Controllers'], static function($r){
    $r->get('/',        'Login::index');
    $r->get('login',    'Login::index');
    $r->post('login',   'Login::auth');
    $r->get('logout',   'Login::logout');
    $r->get('panel',            'Panel::index',   ['filter' => 'osceauth']);
    $r->get('api/peserta',      'Panel::peserta', ['filter' => 'osceauth']); // JSON
    $r->get('api/info/(:num)',      'Panel::info/$1', ['filter'=>'osceauth']);
    $r->post('ujian/start/(:num)', 'Panel::start/$1', ['filter' => 'osceauth']); // stub
    $r->get('nilai/(:num)',     'Panel::detail/$1',   ['filter' => 'osceauth']); // stub
    $r->get ('ujian/(:num)',        'Panel::ujianPage/$1',   ['filter'=>'osceauth']); // HALAMAN ujian
    $r->post('ujian/submit/(:num)', 'Panel::ujianSubmit/$1', ['filter'=>'osceauth']); // simpan nilai
});

$routes->group('admin', ['namespace' => 'Modules\Admin\Controllers'], static function ($routes) {
    $routes->get('/', 'AuthController::index');

    $routes->post('login', 'AuthController::login');

    $routes->get('logout', 'AuthController::logout');

    $routes->get('options/departemen', 'OptionsController::departemen'); 
    // 0–4: dashboard + soal + ujian
    $routes->get('ujian/teori/laporan/(:segment)', 'UjianTeoriReport::laporan/$1');
    $routes->group('', ['filter' => 'adminauth:0,1,2,3,4'], static function ($routes) {
        $routes->get('dashboard',            'DashboardController::index');

        $routes->get('soal/format',          'SoalController::format');   // soal_format
        
        $routes->get ('ujian/teori',         'UjianController::teori');
        $routes->post('ujian/teori/create',     'UjianController::teoriCreate');    // create via modal
        $routes->get ('ujian/teori/export',     'UjianController::teoriExport');    // export excel (per blok)
        $routes->post('ujian/teori/delete/(:num)', 'UjianController::teoriDelete/$1'); // optional
        
        $routes->get ('ujian/teori/get/(:num)',    'UjianController::teoriGet/$1');
        $routes->post('ujian/teori/update/(:num)', 'UjianController::teoriUpdate/$1');
        $routes->get('ujian/teori',          'UjianController::teori');   // ujian_teori
        $routes->get('ujian/praktek',        'UjianController::praktek'); // ujian_praktek
        $routes->get('ujian/teori/newcode', 'UjianController::newKode');
        // DETAIL ujian teori
        $routes->get ('ujian/teori/detail/(:num)',           'UjianController::teoriDetail/$1');

        // Fitur Pilih Soal Massal
        $routes->get ('ujian/teori/soal-list/(:num)',        'UjianController::soalList/$1');
        $routes->get ('ujian/teori/pilih-soal/(:num)',       'UjianController::pilihSoal/$1');
        $routes->post('ujian/teori/soal-add/(:num)/(:num)',  'UjianController::soalAdd/$1/$2');
        $routes->post('ujian/teori/soal-del/(:num)',         'UjianController::soalDel/$1');

        // FRAG: tabel peserta (HTML partial)
        $routes->get ('ujian/teori/peserta/(:segment)',      'UjianController::pesertaTable/$1');
        $routes->get ('ujian/teori/pesertaOsceTable/(:segment)',      'UjianController::pesertaTable/$1');
        // FRAG: modal list mahasiswa yang belum terdaftar
        $routes->get ('ujian/teori/pilih-mahasiswa/(:segment)', 'UjianController::pilihMahasiswa/$1');

        // ACTION: tambah & hapus peserta
        $routes->get ('ujian/teori/peserta-add/(:segment)/(:num)', 'UjianController::pesertaAdd/$1/$2');
        $routes->post('ujian/teori/peserta-add/(:segment)/(:num)', 'UjianController::pesertaAdd/$1/$2');
        $routes->post('ujian/teori/peserta-del/(:segment)/(:num)', 'UjianController::pesertaDel/$1/$2');

        $routes->get ('ujian/teori/mass-assign-soal/(:num)', 'UjianController::massAssignSoal/$1');
        $routes->post('ujian/teori/mass-assign-soal-save/(:num)', 'UjianController::massAssignSoalSave/$1');
        // routes.php (dalam group admin yang sudah ada)
        $routes->get ('ujian/praktek',              'UjianController::praktek');          // list
        $routes->post('ujian/praktek/create',       'UjianController::praktekCreate');    // create via modal
        $routes->get ('ujian/praktek/get/(:num)',   'UjianController::praktekGet/$1');    // load data edit
        $routes->post('ujian/praktek/update/(:num)','UjianController::praktekUpdate/$1'); // submit edit
        $routes->get ('ujian/praktek/detail/(:num)','UjianController::praktekDetail/$1'); // detail
        $routes->get ('ujian/praktek/peserta/(:segment)', 'UjianController::pesertaOsceTable/$1');
        $routes->get ('ujian/praktek/pilih-mahasiswa/(:segment)', 'UjianController::pilihMahasiswa/$1');
        $routes->post('ujian/praktek/peserta-add/(:segment)/(:num)', 'UjianController::pesertaAdd/$1/$2');
        $routes->post('ujian/praktek/peserta-del/(:segment)/(:num)', 'UjianController::pesertaDel/$1/$2');
        $routes->post('ujian/praktek/delete/(:num)', 'UjianController::osceDelete/$1'); // optional


        // Ujian Proctoring & Syncing Routes
        $routes->get ('ujian/teori/detail/(:num)', 'UjianController::teoriDetail/$1');
        $routes->get ('ujian/teori/live-status/(:num)', 'UjianController::getLiveStatus/$1');
        $routes->post('ujian/import-offline', 'UjianController::importOffline');
        
        $routes->get ('sync/auto', 'UjianController::autoSyncOnLogin');
        $routes->get ('ujian/teori/pull/(:any)', 'UjianController::pullExam/$1');
        $routes->post('ujian/teori/push/(:any)', 'UjianController::pushResults/$1');
        $routes->post('ujian/teori/force-submit/(:num)', 'UjianController::forceSubmit/$1');
        
        $routes->get ('ujian/praktek/pull/(:any)',  'UjianController::pullOsce/$1');
        $routes->post('ujian/praktek/push/(:any)',  'UjianController::pushOsceResults/$1');
    });
});



// ===== ADMIN =====
// $routes->group('admin', ['namespace' => 'Modules\Admin\Controllers'], static function ($routes) {
//     $routes->get('/', 'AuthController::index');
//     $routes->post('login', 'AuthController::login');
//     $routes->get('logout', 'AuthController::logout');
//     $routes->get('options/departemen', 'OptionsController::departemen'); 
//     // 0–4: dashboard + soal + ujian
//     $routes->get('ujian/teori/laporan/(:segment)', 'UjianTeoriReport::laporan/$1');
//     $routes->group('', ['filter' => 'adminauth:0,1,2,3,4'], static function ($routes) {
//     $routes->get('dashboard',            'DashboardController::index');

//     $routes->get('soal/format',          'SoalController::format');   // soal_format
    
//     $routes->get ('ujian/teori',         'UjianController::teori');
//     $routes->post('ujian/teori/create',     'UjianController::teoriCreate');    // create via modal
//     $routes->get ('ujian/teori/export',     'UjianController::teoriExport');    // export excel (per blok)
//     $routes->post('ujian/teori/delete/(:num)', 'UjianController::teoriDelete/$1'); // optional
    
//     $routes->get ('ujian/teori/get/(:num)',    'UjianController::teoriGet/$1');
//     $routes->post('ujian/teori/update/(:num)', 'UjianController::teoriUpdate/$1');
//     $routes->get('ujian/teori',          'UjianController::teori');   // ujian_teori
//     $routes->get('ujian/praktek',        'UjianController::praktek'); // ujian_praktek
//     $routes->get('ujian/teori/newcode', 'UjianController::newKode');
//     // DETAIL ujian teori
//     $routes->get ('ujian/teori/detail/(:num)',           'UjianController::teoriDetail/$1');

//     // Fitur Pilih Soal Massal
//     $routes->get ('ujian/teori/soal-list/(:num)',        'UjianController::soalList/$1');
//     $routes->get ('ujian/teori/pilih-soal/(:num)',       'UjianController::pilihSoal/$1');
//     $routes->post('ujian/teori/soal-add/(:num)/(:num)',  'UjianController::soalAdd/$1/$2');
//     $routes->post('ujian/teori/soal-del/(:num)',         'UjianController::soalDel/$1');

//     // FRAG: tabel peserta (HTML partial)
//     $routes->get ('ujian/teori/peserta/(:segment)',      'UjianController::pesertaTable/$1');
//     $routes->get ('ujian/teori/pesertaOsceTable/(:segment)',      'UjianController::pesertaTable/$1');
//     // FRAG: modal list mahasiswa yang belum terdaftar
//     $routes->get ('ujian/teori/pilih-mahasiswa/(:segment)', 'UjianController::pilihMahasiswa/$1');

//     // ACTION: tambah & hapus peserta
//     $routes->get ('ujian/teori/peserta-add/(:segment)/(:num)', 'UjianController::pesertaAdd/$1/$2');
//     $routes->post('ujian/teori/peserta-add/(:segment)/(:num)', 'UjianController::pesertaAdd/$1/$2');
//     $routes->post('ujian/teori/peserta-del/(:segment)/(:num)', 'UjianController::pesertaDel/$1/$2');

//     $routes->get ('ujian/teori/mass-assign-soal/(:num)', 'UjianController::massAssignSoal/$1');
//     $routes->post('ujian/teori/mass-assign-soal-save/(:num)', 'UjianController::massAssignSoalSave/$1');
//     // routes.php (dalam group admin yang sudah ada)
//     $routes->get ('ujian/praktek',              'UjianController::praktek');          // list
//     $routes->post('ujian/praktek/create',       'UjianController::praktekCreate');    // create via modal
//     $routes->get ('ujian/praktek/get/(:num)',   'UjianController::praktekGet/$1');    // load data edit
//     $routes->post('ujian/praktek/update/(:num)','UjianController::praktekUpdate/$1'); // submit edit
//     $routes->get ('ujian/praktek/detail/(:num)','UjianController::praktekDetail/$1'); // detail
//     $routes->get ('ujian/praktek/peserta/(:segment)', 'UjianController::pesertaOsceTable/$1');
//     $routes->get ('ujian/praktek/pilih-mahasiswa/(:segment)', 'UjianController::pilihMahasiswa/$1');
//     $routes->post('ujian/praktek/peserta-add/(:segment)/(:num)', 'UjianController::pesertaAdd/$1/$2');
//     $routes->post('ujian/praktek/peserta-del/(:segment)/(:num)', 'UjianController::pesertaDel/$1/$2');
//     $routes->post('ujian/praktek/delete/(:num)', 'UjianController::osceDelete/$1'); // optional

//     // Soal Teori
//     // Import Soal Teori (Excel)
//     $routes->get ('soal/teori/import/template', 'SoalTeoriController::importTemplate');
//     $routes->post('soal/teori/import/upload',   'SoalTeoriController::importUpload');

//     $routes->get ('soal/teori',                 'SoalTeoriController::index');        // full page
//     $routes->get ('soal/teori/list',            'SoalTeoriController::index');        // fragment ?frag=list (opsional)
//     $routes->get ('soal/teori/get/(:num)',      'SoalTeoriController::get/$1');
//     $routes->post('soal/teori/create',          'SoalTeoriController::create');
//     $routes->get ('soal/praktek/import/template', 'SoalPraktekController::importTemplate');
//     $routes->post('soal/praktek/import/upload',   'SoalPraktekController::importUpload');
//     $routes->get('soal/praktek/export/(:num)', 'SoalPraktekController::exportDocx/$1');
//     // Export semua soal praktek (satu dokx)
//     $routes->get('soal/praktek/export/all', 'SoalPraktekController::exportAllDocx');
//     // Export ZIP (banyak docx, 1 soal = 1 file)
//     $routes->get('soal/praktek/export/zip',  'SoalPraktekController::exportZipDocx');
//     $routes->get('soal/teori/export/zip', 'SoalTeoriController::exportZipPerPaket');
//     $routes->post('soal/teori/delete/(:num)',   'SoalTeoriController::delete/$1');
//     $routes->get('soal/teori/cari-kode',      'SoalTeoriController::searchKodeTeori');      // select2 remote
//     $routes->post('soal/teori/upload',        'SoalTeoriController::uploadMedia');          // upload di modal
//     $routes->post('soal/teori/upload/delete', 'SoalTeoriController::deleteMedia'); 
//     $routes->get ('soal/teori/reg-generate', 'SoalTeoriController::regGenerate');
//     $routes->get ('soal/teori/edit/(:num)',   'SoalTeoriController::teoriEdit/$1');
//     $routes->post('soal/teori/update/(:num)', 'SoalTeoriController::teoriUpdate/$1');
//     $routes->get('soal/teori/tambah',       'SoalTeoriController::teoriNew');
//     $routes->post('soal/teori/simpan',      'SoalTeoriController::teoriStore');
//     $routes->get('soal/teori/review/(:num)', 'SoalTeoriController::teoriReview/$1');
//     $routes->get('soal/teori/revisi-list/(:num)', 'SoalTeoriController::teoriRevisiList/$1');
//     $routes->get('soal/teori/revisi-get/(:num)',  'SoalTeoriController::teoriRevisiGet/$1');
//     $routes->post('soal/teori/revisi-save',       'SoalTeoriController::teoriRevisiSave');
//     // Soal Praktek
//     $routes->get ('soal/praktek',                 'SoalPraktekController::index');        // full page
//     $routes->get ('soal/praktek/list',            'SoalPraktekController::index');        // fragment ?frag=list (opsional)
//     $routes->get ('soal/praktek/get/(:num)',      'SoalPraktekController::get/$1');
//     $routes->post('soal/praktek/create',          'SoalPraktekController::create');
//     $routes->post('soal/praktek/update/(:num)',   'SoalPraktekController::update/$1');
//     $routes->post('soal/praktek/delete/(:num)',   'SoalPraktekController::delete/$1');
    
//     $routes->post('soal/praktek/upload',        'SoalPraktekController::upload');          // upload di modal
//     $routes->post('soal/praktek/upload/delete', 'SoalPraktekController::uploadDelete'); 
//     $routes->get ('soal/praktek/reg-generate', 'SoalPraktekController::praktekRegGenerate');
//     $routes->get ('soal/praktek/edit/(:num)',   'SoalPraktekController::edit/$1');
//     $routes->post('soal/praktek/update/(:num)', 'SoalPraktekController::praktekUpdate/$1');
//     $routes->get('soal/praktek/add',       'SoalPraktekController::praktekAdd');
//     $routes->post('soal/praktek/simpan',      'SoalPraktekController::praktekSimpan');

//     $routes->get('soal/praktek/cari-kode',      'SoalPraktekController::praktekCariKode');


//     $routes->get('praktek/aspek/list',   'SoalPraktekController::aspekList');   // ?soal_id=...
//     $routes->post('praktek/aspek/delete','SoalPraktekController::aspekDelete'); // id=...

//     $routes->get('aspek/',           'Aspek::index');
//             // partial list
//     $routes->get('aspek/add/(:num)',         'Aspek::add/$1');            // form tambah
//     $routes->post('aspek/create',     'Aspek::create');         // simpan tambah
//     $routes->get('aspek/edit/(:num)', 'Aspek::edit/$1');        // form edit
//     $routes->post('aspek/update/(:num)','Aspek::update/$1');    // simpan edit
//     // hapus (AJAX)
//     // opsional: ambil detail json
//     $routes->get('aspek/get/(:num)',  'Aspek::get/$1');

//     $routes->get('osce-soal',               'OsceSoal::index');
//     $routes->get('osce-soal/table',           'OsceSoal::table');          // partial list
//     $routes->get('osce-soal/get/(:num)',      'OsceSoal::get/$1');         // detail JSON
//     $routes->post('osce-soal/create',         'OsceSoal::create');         // simpan tambah
//     $routes->post('osce-soal/update/(:num)',  'OsceSoal::update/$1');      // simpan edit
//     $routes->post('osce-soal/delete/(:num)',  'OsceSoal::delete/$1');      // hapus
//     $routes->get('osce-soal/detail/(:num)', 'OsceSoal::detail/$1');
//     $routes->get('osce-soal/history-mahasiswa/(:num)', 'OsceSoal::historyMahasiswa/$1');
//     $routes->post('osce-soal/delete-multiple', 'OsceSoal::deleteMultiple');
//     // Select2 options
//     $routes->get('options/osce',    'OsceSoal::optionsOsce');    // ?q=
//     $routes->get('options/soal',    'OsceSoal::optionsSoal');    // ?q=
//     $routes->get('options/pengawas', 'OsceSoal::optionsPengawas');
//     $routes->get('osce/history-pdf/(:num)', 'OsceSoal::historyMahasiswaPdf/$1');
//     $routes->get('soal/praktek/review/(:num)',          'SoalPraktekController::review/$1');            // halaman review
//     $routes->get('soal/praktek/review/history/(:num)',  'SoalPraktekController::revisiPrakHistory/$1');  // partial history
//     $routes->post('soal/praktek/review/save',           'SoalPraktekController::revisiPrakSave');   
//     // app/Config/Routes.php
//     $routes->get('soal/praktek/review/get/(:num)', 'SoalPraktekController::revisiPrakGet/$1');
//     // simpan telaah (AJAX)
// });

// 0 only: master data + pengguna
// app/Config/Routes.php
// $routes->group('', ['filter' => 'adminauth', 'namespace' => 'Modules\Admin\Controllers'], static function ($routes) {
    
//     $routes->post('master/users/reset-all', 'PasswordMaintenanceController::resetAll', ['filter' => 'adminauth:0']);

//     $routes->group('master', ['filter' => 'adminauth:0'], static function($routes) {
//         // === Bidang Ilmu (CRUD via modal) ===
//         $routes->group('bid-ilmu', static function ($routes) {
//             $routes->get('/',              'BidIlmuController::index');
//             $routes->get('get/(:num)',     'BidIlmuController::get/$1');
//             $routes->post('create',        'BidIlmuController::create');
//             $routes->post('update/(:num)', 'BidIlmuController::update/$1');
//             $routes->post('delete/(:num)', 'BidIlmuController::delete/$1');
//         });

//         $routes->get ('blok',                 'BlokController::index');
//         $routes->get ('blok/list',            'BlokController::index');
//         $routes->get ('blok/get/(:num)',      'BlokController::get/$1');
//         $routes->post('blok/save',            'BlokController::save');
//         $routes->post('blok/delete/(:num)',   'BlokController::delete/$1');

//         $routes->get ('departemen',               'DepartemenController::index');
//         $routes->get ('departemen/list',          'DepartemenController::index');
//         $routes->get ('departemen/get/(:num)',    'DepartemenController::get/$1');
//         $routes->post('departemen/save',          'DepartemenController::save');
//         $routes->post('departemen/delete/(:num)', 'DepartemenController::delete/$1');

//         $routes->get ('kel-penyakit',               'KelPenyakitController::index');
//         $routes->get ('kel-penyakit/list',          'KelPenyakitController::index');
//         $routes->get ('kel-penyakit/get/(:num)',    'KelPenyakitController::get/$1');
//         $routes->post('kel-penyakit/save',          'KelPenyakitController::save');
//         $routes->post('kel-penyakit/delete/(:num)', 'KelPenyakitController::delete/$1');

//         $routes->get ('kom-utama',               'KomUtamaController::index');
//         $routes->get ('kom-utama/list',          'KomUtamaController::index');
//         $routes->get ('kom-utama/get/(:num)',    'KomUtamaController::get/$1');
//         $routes->post('kom-utama/save',          'KomUtamaController::save');
//         $routes->post('kom-utama/delete/(:num)', 'KomUtamaController::delete/$1');

//         $routes->get ('mahasiswa',               'MahasiswaController::index');
//         $routes->get ('mahasiswa/list',          'MahasiswaController::index');
//         $routes->get ('mahasiswa/get/(:num)',    'MahasiswaController::get/$1');
//         $routes->post('mahasiswa/save',          'MahasiswaController::save');
//         $routes->post('mahasiswa/delete/(:num)', 'MahasiswaController::delete/$1');

//         $routes->get ('dosen',               'DosenController::index');
//         $routes->get ('dosen/list',          'DosenController::index');
//         $routes->get ('dosen/get/(:num)',    'DosenController::get/$1');
//         $routes->post('dosen/save',          'DosenController::save');
//         $routes->post('dosen/delete/(:num)', 'DosenController::delete/$1');

//         $routes->group('pengguna-admin', static function($routes){
//             $routes->get('/',                 'UsersAdminController::index');
//             $routes->get('get/(:num)',        'UsersAdminController::get/$1');
//             $routes->post('save',             'UsersAdminController::save');
//             $routes->post('delete/(:num)',    'UsersAdminController::delete/$1');
//             $routes->post('reset/(:num)',     'UsersAdminController::resetPassword/$1');
//             $routes->get('export',            'UsersAdminController::export');
//         });

//         $routes->group('pengguna-dosen', static function($routes){
//             $routes->get('/',                 'UsersDosenController::index');
//             $routes->get('get/(:num)',        'UsersDosenController::get/$1');
//             $routes->post('save',             'UsersDosenController::save');
//             $routes->post('delete/(:num)',    'UsersDosenController::delete/$1');
//             $routes->post('reset/(:num)',     'UsersDosenController::resetPassword/$1');
//             $routes->get('export',            'UsersDosenController::export');
//         });

//         $routes->group('pengguna-manajemen', static function($routes){
//             $routes->get('/',                 'UsersManajemenController::index');
//             $routes->get('get/(:num)',        'UsersManajemenController::get/$1');
//             $routes->post('save',             'UsersManajemenController::save');
//             $routes->post('delete/(:num)',    'UsersManajemenController::delete/$1');
//             $routes->post('reset/(:num)',     'UsersManajemenController::resetPassword/$1');
//             $routes->get('export',            'UsersManajemenController::export');
//         });

//         $routes->group('pengguna-reviewer', static function($routes){
//             $routes->get('/',                 'UsersReviewerController::index');
//             $routes->get('get/(:num)',        'UsersReviewerController::get/$1');
//             $routes->post('save',             'UsersReviewerController::save');
//             $routes->post('delete/(:num)',    'UsersReviewerController::delete/$1');
//             $routes->post('reset/(:num)',     'UsersReviewerController::resetPassword/$1');
//             $routes->get('export',            'UsersReviewerController::export');
//         });
//     });
// });