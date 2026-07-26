<?php
use Modules\Auth\Libraries\Auth;

$u        = Auth::user();
$role     = (int)($u['role_id'] ?? $u['id_role'] ?? -1);
$canSuper = ($role === 0);                 // superadmin
$canStd   = in_array($role, [1,2,3,4,6], true);

$isActive = fn(string $k) => (($menuActive ?? '') === $k) ? 'active' : '';
$open     = fn(array $keys)  => in_array($menuActive ?? '', $keys, true) ? 'show'  : '';
$aria     = fn(array $keys)  => in_array($menuActive ?? '', $keys, true) ? 'true'  : 'false';
?>

<aside class="app-sidebar" id="sidebar">
  <div class="sidebar-inner d-flex flex-column">
    
    <!-- BRAND AREA -->
    <div class="brand-wrap d-flex align-items-center gap-3 px-2 mb-4 mt-1">
      <img src="<?= base_url('assets/img/logo_unhas.png') ?>" alt="Logo" class="brand-logo">
      <div class="brand-text">
        <div class="brand-title">E-UJIAN</div>
        <div class="brand-sub">Fakultas Kedokteran Gigi</div>
      </div>
    </div>

    <nav class="menu flex-grow-1" id="sidebarMenu">
      <div class="menu-section">Menu</div>

      <?php if ($canSuper || $canStd): ?>
      <a class="menu-item <?= $isActive('dashboard') ?>" href="<?= site_url('admin/dashboard') ?>">
        <i class="bi bi-speedometer2"></i><span>Dashboard</span>
      </a>

      <!-- SOAL -->
      <a class="menu-item menu-parent" data-bs-toggle="collapse" href="#navSoal" role="button"
         aria-expanded="<?= $aria(['soal_format','soal_teori','soal_praktek']) ?>" aria-controls="navSoal">
        <i class="bi bi-journal-text"></i><span>Soal</span>
        <i class="bi bi-chevron-down ms-auto caret"></i>
      </a>
      <div class="collapse submenu <?= $open(['soal_format','soal_teori','soal_praktek']) ?>" id="navSoal" data-bs-parent="#sidebarMenu">
        <a class="submenu-item <?= $isActive('soal_teori')   ?>" href="<?= site_url('admin/soal/teori')   ?>">
          <span>Soal Teori</span>
        </a>
        <a class="submenu-item <?= $isActive('soal_praktek') ?>" href="<?= site_url('admin/soal/praktek') ?>">
          <span>Soal Praktek</span>
        </a>
      </div>

      <!-- UJIAN -->
      <?php if ($role !== 4): ?>
      <a class="menu-item menu-parent" data-bs-toggle="collapse" href="#navUjian" role="button"
         aria-expanded="<?= $aria(['ujian_teori','ujian_praktek','osce-soal']) ?>" aria-controls="navUjian">
        <i class="bi bi-clipboard2-check"></i><span>Ujian</span>
        <i class="bi bi-chevron-down ms-auto caret"></i>
      </a>
      <div class="collapse submenu <?= $open(['ujian_teori','ujian_praktek']) ?>" id="navUjian" data-bs-parent="#sidebarMenu">
        <a class="submenu-item <?= $isActive('ujian_teori')   ?>" href="<?= site_url('admin/ujian/teori')   ?>">
          <span>Teori</span>
        </a>
        <a class="submenu-item <?= $isActive('ujian_praktek') ?>" href="<?= site_url('admin/ujian/praktek') ?>">
          <span>Praktek</span>
        </a>
      </div>
      <?php endif; ?>
      <?php endif; ?>

      <?php if ($canSuper): ?>
      <div class="menu-section">Admin</div>

      <!-- MASTER DATA -->
      <a class="menu-item menu-parent" data-bs-toggle="collapse" href="#navMaster" role="button"
         aria-expanded="<?= $aria(['master_bid_ilmu','master_departemen','master_blok','kel_penyakit','master_kom_utama','master_mahasiswa','master_dosen']) ?>" aria-controls="navMaster">
        <i class="bi bi-database-fill-gear"></i><span>Master Data</span>
        <i class="bi bi-chevron-down ms-auto caret"></i>
      </a>
      <div class="collapse submenu <?= $open(['master_bid_ilmu','master_departemen','master_blok','kel_penyakit','master_kom_utama','master_mahasiswa','master_dosen']) ?>" id="navMaster" data-bs-parent="#sidebarMenu">
        <a class="submenu-item <?= $isActive('master_bid_ilmu')  ?>" href="<?= site_url('admin/master/bid-ilmu')  ?>">
          <span>Bidang Ilmu</span>
        </a>
        <a class="submenu-item <?= $isActive('master_departemen') ?>" href="<?= site_url('admin/master/departemen') ?>">
          <span>Departemen</span>
        </a>
        <a class="submenu-item <?= $isActive('master_blok') ?>" href="<?= site_url('admin/master/blok') ?>">
          <span>Blok</span>
        </a>
        <a class="submenu-item <?= $isActive('kel_penyakit') ?>" href="<?= site_url('admin/master/kel-penyakit') ?>">
          <span>Kel Penyakit</span>
        </a>
        <a class="submenu-item <?= $isActive('master_kom_utama') ?>" href="<?= site_url('admin/master/kom-utama') ?>">
          <span>Kompetensi Utama</span>
        </a>
        <a class="submenu-item <?= $isActive('master_mahasiswa') ?>" href="<?= site_url('admin/master/mahasiswa') ?>">
          <span>Mahasiswa</span>
        </a>
        <a class="submenu-item <?= $isActive('master_dosen') ?>" href="<?= site_url('admin/master/dosen') ?>">
          <span>Dosen</span>
        </a>
      </div>
      
      <!-- PENGGUNA -->
      <a class="menu-item menu-parent" data-bs-toggle="collapse" href="#navPengguna" role="button"
         aria-expanded="<?= $aria(['pengguna-dosen','pengguna-reviewer','pengguna-operator','pengguna-manajemen','pengguna-administrator']) ?>" aria-controls="navPengguna">
        <i class="bi bi-people-fill"></i><span>Pengguna</span>
        <i class="bi bi-chevron-down ms-auto caret"></i>
      </a>
      <div class="collapse submenu <?= $open(['pengguna-dosen','pengguna-reviewer','pengguna-operator','pengguna-manajemen','pengguna-administrator']) ?>" id="navPengguna" data-bs-parent="#sidebarMenu">
        <a class="submenu-item <?= $isActive('pengguna-dosen')  ?>" href="<?= site_url('admin/master/pengguna-dosen')  ?>">
          <span>Dosen</span>
        </a>
        <a class="submenu-item <?= $isActive('pengguna-reviewer')  ?>" href="<?= site_url('admin/master/pengguna-reviewer')  ?>">
          <span>Reviewer</span>
        </a>
        <a class="submenu-item <?= $isActive('pengguna-operator')  ?>" href="<?= site_url('admin/master/pengguna-operator')  ?>">
          <span>Operator Ujian</span>
        </a>
        <a class="submenu-item <?= $isActive('pengguna-manajemen')  ?>" href="<?= site_url('admin/master/pengguna-manajemen')  ?>">
          <span>Manajemen</span>
        </a>
        <a class="submenu-item <?= $isActive('pengguna-administrator')  ?>" href="<?= site_url('admin/master/pengguna-administrator')  ?>">
          <span>Administrator</span>
        </a>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</aside>
