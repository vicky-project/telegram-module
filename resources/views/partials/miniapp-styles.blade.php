<style>
  /* ============================================
   TELEGRAM THEME OVERRIDE UNTUK BOOTSTRAP 5
   ============================================ */

  /* Root variables untuk Bootstrap */
:root {
    --bs-body-bg: var(--tg-theme-bg-color, #ffffff);
    --bs-body-color: var(--tg-theme-text-color, #000000);
    --bs-border-color: var(--tg-theme-section-separator-color, #dee2e6);
    --bs-secondary-bg: var(--tg-theme-secondary-bg-color, #f5f5f5);
    --bs-secondary-color: var(--tg-theme-hint-color, #6c757d);
    --bs-link-color: var(--tg-theme-link-color, #007aff);
    --bs-link-hover-color: color-mix(in srgb, var(--tg-theme-link-color, #007aff) 85%, black);
    --bs-primary: var(--tg-theme-button-color, #007aff);
    --bs-primary-rgb: 0, 122, 255; /* fallback, akan diupdate via JS jika perlu */
    --bs-btn-color: var(--tg-theme-button-text-color, #ffffff);
    --bs-btn-bg: var(--tg-theme-button-color, #007aff);
    --bs-btn-border-color: var(--tg-theme-button-color, #007aff);
    --bs-btn-hover-bg: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 90%, black);
    --bs-btn-hover-border-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 90%, black);
    --bs-btn-active-bg: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 80%, black);
    }

    /* Body */
    body {
    background-color: var(--bs-body-bg) !important;
    color: var(--bs-body-color) !important;
    }

    /* Cards */
    .card {
    background-color: var(--bs-secondary-bg) !important;
    border-color: var(--tg-theme-section-separator-color, rgba(0,0,0,0.125)) !important;
    }
    .card-header, .card-footer {
    background-color: rgba(var(--tg-theme-secondary-bg-color, 245,245,245), 0.5) !important;
    border-bottom-color: var(--tg-theme-section-separator-color, rgba(0,0,0,0.125)) !important;
    }
    .card-body {
    color: var(--bs-body-color) !important;
    }

    /* Buttons */
    .btn {
    transition: none;
    }
    .btn-primary {
    background-color: var(--bs-btn-bg) !important;
    border-color: var(--bs-btn-border-color) !important;
    color: var(--bs-btn-color) !important;
    }
    .btn-primary:hover, .btn-primary:focus {
    background-color: var(--bs-btn-hover-bg) !important;
    border-color: var(--bs-btn-hover-border-color) !important;
    color: var(--bs-btn-color) !important;
    }
    .btn-primary:active {
    background-color: var(--bs-btn-active-bg) !important;
    border-color: var(--bs-btn-active-bg) !important;
    }
    .btn-outline-primary {
    color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
    }
    .btn-outline-primary:hover {
    background-color: var(--bs-primary) !important;
    color: var(--bs-btn-color) !important;
    }
    .btn-link {
    color: var(--bs-link-color) !important;
    }
    .btn-link:hover {
    color: var(--bs-link-hover-color) !important;
    }

    /* Text colors */
    .text-muted {
    color: var(--tg-theme-hint-color, #6c757d) !important;
    }
    a {
    color: var(--bs-link-color);
    }
    a:hover {
    color: var(--bs-link-hover-color);
    }

    /* List group */
    .list-group-item {
    background-color: var(--bs-secondary-bg) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .list-group-item-action:hover {
    background-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 10%, transparent) !important;
    }

    /* Progress bar */
    .progress {
    background-color: var(--tg-theme-secondary-bg-color, #e9ecef) !important;
    }
    .progress-bar {
    background-color: var(--tg-theme-button-color, #007aff) !important;
    color: var(--tg-theme-button-text-color, #ffffff) !important;
    }

    /* Form controls */
    .form-control, .form-select {
    background-color: var(--tg-theme-secondary-bg-color, #ffffff) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--tg-theme-section-separator-color, #ced4da) !important;
    }
    .form-control:focus, .form-select:focus {
    border-color: var(--tg-theme-button-color, #86b7fe) !important;
    box-shadow: 0 0 0 0.25rem color-mix(in srgb, var(--tg-theme-button-color, #007aff) 25%, transparent) !important;
    }
    .form-control::placeholder {
    color: var(--tg-theme-hint-color, #6c757d) !important;
    }

    /* Dropdown */
    .dropdown-menu {
    background-color: var(--tg-theme-secondary-bg-color, #ffffff) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .dropdown-item {
    color: var(--bs-body-color) !important;
    }
    .dropdown-item:hover {
    background-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 10%, transparent) !important;
    color: var(--bs-body-color) !important;
    }

    /* Modal */
    .modal-content {
    background-color: var(--tg-theme-bg-color, #ffffff) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .modal-header, .modal-footer {
    border-color: var(--tg-theme-section-separator-color) !important;
    }

    /* Alerts */
    .alert {
    background-color: var(--tg-theme-secondary-bg-color, #f8f9fa) !important;
    color: var(--bs-body-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .alert-primary {
    background-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 15%, transparent) !important;
    border-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 30%, transparent) !important;
    color: var(--bs-body-color) !important;
    }

    /* Badges */
    .badge.bg-primary {
    background-color: var(--tg-theme-button-color, #007aff) !important;
    color: var(--tg-theme-button-text-color, #ffffff) !important;
    }
    .badge.bg-secondary {
    background-color: var(--tg-theme-hint-color, #6c757d) !important;
    }

    /* Pagination */
    .page-link {
    background-color: var(--tg-theme-secondary-bg-color, #ffffff) !important;
    color: var(--bs-link-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .page-link:hover {
    background-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 10%, transparent) !important;
    color: var(--bs-link-hover-color) !important;
    }
    .page-item.active .page-link {
    background-color: var(--tg-theme-button-color, #007aff) !important;
    border-color: var(--tg-theme-button-color, #007aff) !important;
    color: var(--tg-theme-button-text-color, #ffffff) !important;
    }
    .page-item.disabled .page-link {
    background-color: var(--tg-theme-secondary-bg-color) !important;
    color: var(--tg-theme-hint-color) !important;
    }

    /* Nav tabs */
    .nav-tabs .nav-link {
    color: var(--bs-body-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .nav-tabs .nav-link:hover {
    background-color: var(--tg-theme-secondary-bg-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .nav-tabs .nav-link.active {
    background-color: var(--tg-theme-bg-color) !important;
    color: var(--tg-theme-link-color) !important;
    border-color: var(--tg-theme-section-separator-color) !important;
    border-bottom-color: transparent !important;
    }

    /* Borders utilities */
    .border {
    border-color: var(--tg-theme-section-separator-color) !important;
    }
    .border-top, .border-bottom, .border-start, .border-end {
    border-color: var(--tg-theme-section-separator-color) !important;
    }

    /* Tables */
    .table {
    color: var(--bs-body-color) !important;
    }
    .table-striped > tbody > tr:nth-of-type(odd) > * {
    background-color: var(--tg-theme-secondary-bg-color) !important;
    color: var(--bs-body-color) !important;
    }
    .table-hover > tbody > tr:hover > * {
    background-color: color-mix(in srgb, var(--tg-theme-button-color, #007aff) 5%, transparent) !important;
    }

    /* Spinners */
    .spinner-border {
    border-color: var(--tg-theme-hint-color) !important;
    border-right-color: transparent !important;
    }
    .loading-spinner {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 40px;
    }
    .toast-message {
    position: fixed;
    bottom: 20px;
    left: 16px;
    right: 16px;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
    color: white;
    padding: 12px 20px;
    border-radius: 30px;
    text-align: center;
    z-index: 9999;
    font-size: 14px;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    }
    .toast-message.show {
    opacity: 1;
    }
    .pagination-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    text-align: center;
    margin: 1rem 0;
    }
    .pagination {
    display: inline-flex;
    flex-wrap: nowrap;
    gap: 0.25rem;
    margin: 0;
    padding: 0;
    }
    .page-item {
    flex-shrink: 0;
    }
    .page-link {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    }
    .pagination .page-link {
    background-color: var(--tg-theme-bg-color);
    color: var(--tg-theme-text-color);
    border-color: var(--tg-theme-section-separator-color);
    }
    .pagination .active .page-link {
    background-color: var(--tg-theme-button-color);
    border-color: var(--tg-theme-button-color);
    color: var(--tg-theme-button-text-color);
    }
    </style>