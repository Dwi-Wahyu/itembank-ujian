<?php
use Modules\Auth\Libraries\Auth;

$u        = Auth::user();
$role     = (int)($u['role_id'] ?? $u['id_role'] ?? -1);
$canSuper = ($role === 0);                 // superadmin
$canStd   = in_array($role, [1,2,3,4], true);

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

      <a class="menu-item <?= $isActive('ujian_teori') ?>" href="<?= site_url('admin/ujian/teori') ?>">
        <i class="bi bi-clipboard-pulse"></i><span>Ujian Teori</span>
      </a>

      <a class="menu-item <?= $isActive('ujian_praktek') ?>" href="<?= site_url('admin/ujian/praktek') ?>">
        <i class="bi bi-tools"></i><span>Ujian Praktek</span>
      </a>    
      <?php endif; ?>
    </nav>
  </div>
</aside>
