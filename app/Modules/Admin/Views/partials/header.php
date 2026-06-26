<header class="app-header">
  <div class="container-fluid d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3 header-left desktop-shift">
      <!-- Burger untuk MOBILE (slide in) -->
      <button class="btn btn-link text-dark p-0 d-lg-none" id="btnSidebarMobile" type="button" aria-label="Menu">
        <i class="bi bi-list" style="font-size:1.6rem"></i>
      </button>

      <!-- Desktop Toggle Sidebar Mini (Moved from Sidebar Footer) -->
      <button class="btn btn-link text-muted p-0 d-none d-lg-inline-flex align-items-center text-decoration-none" 
              id="btnSidebarMini" type="button" title="Toggle Sidebar">
        <i class="bi bi-list" id="toggleIcon" style="font-size:1.5rem"></i>
      </button>

      <!-- Brand area removed from header, moved to sidebar -->
    </div>

    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none fw-medium" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-2 text-muted" style="font-size:1.2rem"></i>
          <span><?= esc($me['name'] ?? 'Admin') ?></span>
          <i class="bi bi-caret-down-fill ms-1 small text-muted"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2">
          <li><a class="dropdown-item py-2" href="<?= base_url('admin/profile') ?>"><i class="bi bi-gear me-2"></i>Profil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('admin/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
