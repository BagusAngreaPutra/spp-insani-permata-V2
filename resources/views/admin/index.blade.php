@extends('layouts.app')

@section('content')

@push('page-styles')
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: #1a202c;
    line-height: 1.6;
}

.main-content {
    margin-left: 280px;
    min-height: 100vh;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    position: absolute;
    right: 0;
    top: 0;
    width: calc(100% - 280px);
}




.user-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #16a34a, #15803d);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 8px 25px rgba(22, 163, 74, 0.3);
}

.dropdown {
    position: relative;
}

.dropdown-trigger {
    display: inline-flex;
    align-items: center;
    padding: 12px 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    color: #2d3748;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    transition: all 300ms ease;
    cursor: pointer;
    gap: 12px;
    font-weight: 600;
    font-size: 14px;
}

.dropdown-trigger:hover {
    background: rgba(255, 255, 255, 0.95);
    transform: translateY(-2px);
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    z-index: 1000;
    margin-top: 12px;
    width: 14rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dropdown-content.show {
    display: block;
    animation: dropdownSlide 0.3s ease;
}

@keyframes dropdownSlide {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.dropdown-link {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    text-decoration: none;
    color: #2d3748;
    font-size: 14px;
    font-weight: 500;
    transition: all 200ms ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    gap: 12px;
}

.dropdown-link:hover {
    background: rgba(22, 163, 74, 0.1);
    color: #16a34a;
}

.content-area {
    padding: 3rem 2.5rem;
}

.alert {
    background: linear-gradient(135deg, #d1fae5, #bbf7d0);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: #166534;
    padding: 1.5rem 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 3rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    padding: 2.5rem 3rem;
    border-radius: 24px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.page-title {
    font-size: 2.25rem;
    font-weight: 800;
    background: linear-gradient(135deg, #2d3748, #4a5568);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    border-radius: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 300ms ease;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-primary {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(34, 197, 94, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    box-shadow: 0 4px 4px rgba(245, 158, 11, 0.3);
}

.btn-warning:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 8px rgba(245, 158, 11, 0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    box-shadow: 0 4px 4px rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 4px rgba(239, 68, 68, 0.4);
}

.btn-sm {
    border-radius: 10px;
    flex: 0 0 auto;
    font-size: 0.8rem;
    line-height: 1;
    min-height: 34px;
    padding: 0.55rem 0.75rem;
    white-space: nowrap;
}

.table-container {
    background: #ffffff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid #e5e7eb;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
    table-layout: fixed;
}

.modern-table th:nth-child(1),
.modern-table td:nth-child(1) { width: 56px; }
.modern-table th:nth-child(2),
.modern-table td:nth-child(2) { width: 20%; }
.modern-table th:nth-child(3),
.modern-table td:nth-child(3) { width: 15%; }
.modern-table th:nth-child(4),
.modern-table td:nth-child(4) { width: 12%; }
.modern-table th:nth-child(5),
.modern-table td:nth-child(5) { width: 12%; }
.modern-table th:nth-child(6),
.modern-table td:nth-child(6) { width: 13%; }
.modern-table th:nth-child(7),
.modern-table td:nth-child(7) { width: 170px; }

.modern-table thead {
    background: #f8fafc;
}

.modern-table th {
    padding: 0.9rem 1rem;
    text-align: left;
    font-weight: 700;
    color: #166534;
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border: none;
    vertical-align: middle;
    height: auto;
}

.modern-table td {
    padding: 0.95rem 1rem;
    border-bottom: 1px solid #edf2f7;
    color: #374151;
    vertical-align: middle;
    height: auto;
    min-width: 0;
}

.modern-table tbody tr {
    background: transparent;
}

.modern-table tbody tr:hover {
    background: #f8fafc;
}

/* DIHAPUS SELURUHNYA: Pseudo-element yang menyebabkan pergeseran */
/* .modern-table tbody tr:hover::before dan .modern-table tbody tr::before DIHAPUS */

.modern-table tbody tr:last-child td {
    border-bottom: none;
}

.action-buttons {
    display: inline-flex;
    gap: 0.35rem;
    align-items: center;
    justify-content: flex-start;
    height: auto;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.action-form {
    display: inline-flex;
    margin: 0;
    align-items: center;
    flex: 0 0 auto;
}

/* PERBAIKAN: Styling untuk elemen dalam tabel dengan height tetap */
.id-text {
    color: #166534;
    font-weight: 700;
    font-size: 0.9rem;
    line-height: 1.4;
    display: inline-flex;
}

.username-text {
    font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
    font-size: 0.84rem;
    font-weight: 600;
    color: #374151;
    line-height: 1.4;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.admin-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.94rem;
    line-height: 1.4;
    display: block;
    overflow-wrap: anywhere;
}

.role-badge {
    border-radius: 999px;
    display: inline-flex;
    font-size: 0.78rem;
    font-weight: 800;
    padding: 0.3rem 0.7rem;
    white-space: nowrap;
}

.role-badge.super-admin {
    background: #dcfce7;
    color: #166534;
}

.role-badge.admin {
    background: #eef2ff;
    color: #3730a3;
}

.permission-count {
    color: #64748b;
    display: inline-flex;
    font-size: 0.82rem;
    font-weight: 700;
    line-height: 1.25;
}

/* PERBAIKAN: Container untuk tanggal dengan alignment yang lebih baik */
.date-container {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    gap: 2px;
}

.date-text {
    color: #6b7280;
    font-size: 0.84rem;
    font-weight: 500;
    line-height: 1.2;
    margin: 0;
}

.date-time {
    font-size: 0.75rem;
    opacity: 0.7;
    color: #6b7280;
    line-height: 1.2;
    margin: 0;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    color: #d1d5db;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #374151;
    font-weight: 600;
}

/* DIHAPUS: Animasi fadeInUp yang tidak diperlukan */
/* @keyframes fadeInUp dan .fade-in-up DIHAPUS */

@media (max-width: 1024px) {
    .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        width: 100%;
        position: relative;
        top: 0;
        right: auto;
    }
    
    .content-area {
        padding: 2rem 1.5rem;
        width: 100%;
    }



    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 2rem;
        padding: 2rem;
    }

    .page-title {
        font-size: 1.75rem;
    }

    .action-buttons {
        gap: 0.6rem;
        width: auto;
    }

    .btn-sm {
        width: auto;
        justify-content: center;
        padding: 0.65rem 0.9rem;
        font-size: 0.82rem;
    }
}

@media (max-width: 640px) {
    .table-container {
        background: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        overflow: visible;
    }

    .modern-table,
    .modern-table thead,
    .modern-table tbody,
    .modern-table th,
    .modern-table td,
    .modern-table tr {
        display: block;
        min-width: 0 !important;
        width: 100% !important;
    }

    .modern-table {
        border-collapse: separate;
        border-spacing: 0;
        overflow: visible;
        white-space: normal;
    }

    .modern-table thead {
        display: none;
    }

    .modern-table tbody tr {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
        margin-bottom: 1rem;
        overflow: hidden;
        padding: 0.35rem 0;
    }

    .modern-table tbody tr:hover {
        background: #ffffff;
    }

    .modern-table tbody tr.empty-row {
        padding: 0;
    }

    .modern-table tbody tr.empty-row td {
        border-bottom: 0;
        display: block;
        padding: 0;
    }

    .modern-table tbody tr.empty-row td::before {
        content: none;
    }

    .modern-table td {
        align-items: flex-start;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 0.8rem 1rem;
        text-align: right;
    }

    .modern-table td:last-child {
        border-bottom: 0;
    }

    .modern-table td::before {
        color: #64748b;
        content: attr(data-label);
        flex: 0 0 42%;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-align: left;
        text-transform: uppercase;
    }

    .id-text,
    .admin-name,
    .username-text,
    .date-container,
    .permission-count {
        max-width: 58%;
        text-align: right;
    }

    .username-text {
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
        word-break: break-word;
    }

    .date-container {
        align-items: flex-end;
    }

    .action-buttons {
        justify-content: flex-end;
        max-width: 58%;
        width: auto;
    }

    .page-title {
        font-size: 1.5rem;
    }

   

    .content-area {
        padding: 1.5rem 1rem;
    }
}

@media (max-width: 420px) {
    .modern-table td {
        display: block;
        text-align: left;
    }

    .modern-table td::before {
        display: block;
        margin-bottom: 0.35rem;
    }

    .id-text,
    .admin-name,
    .username-text,
    .date-container,
    .permission-count,
    .action-buttons {
        align-items: flex-start;
        justify-content: flex-start;
        max-width: 100%;
        text-align: left;
    }
}
</style>
@endpush

<!-- Include Sidebar -->
@include('layouts.sidebar')


<!-- Main Content -->
<div class="main-content">
    <!-- Header dengan dropdown Profile -->
@include('layouts.header')
  

    <!-- Content Area -->
    <div class="content-area admin-directory-page">
        <div class="content-container">
            <!-- Success Alert -->
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <!-- Page Header -->
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-users-cog"></i>
                    Guru & Admin
                </h2>
                <a href="{{ route('admin.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Admin
                </a>
            </div>

            <!-- Table Container -->
            <div class="table-card admin-table-card">
                <div class="table-header">
                    <h3><i class="fas fa-user-shield"></i> Daftar Guru & Admin</h3>
                    <span class="badge badge-secondary">
                        {{ method_exists($admin, 'total') ? $admin->total() : $admin->count() }} pengguna
                    </span>
                </div>
                <div class="table-responsive">
                <table class="modern-table admin-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Admin</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Hak Akses</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($admin as $item)
                        <tr>
                            <td data-label="No">
                                <span class="id-text">{{ $loop->iteration }}</span>
                            </td>
                            <td data-label="Nama Admin">
                                <div class="table-entity">
                                    <span class="table-entity-icon" aria-hidden="true">
                                        <i class="fas fa-user-shield"></i>
                                    </span>
                                    <div class="admin-name">{{ $item->nama_admin }}</div>
                                </div>
                            </td>
                            <td data-label="Username">
                                <div class="username-text">{{ $item->username }}</div>
                            </td>
                            <td data-label="Role">
                                <span class="role-badge {{ $item->isSuperAdmin() ? 'super-admin' : 'admin' }}">
                                    {{ $item->role_label }}
                                </span>
                            </td>
                            <td data-label="Hak Akses">
                                <span class="permission-count">
                                    {{ $item->isSuperAdmin() ? 'Semua fitur' : count($item->permissionKeys()) . ' hak akses' }}
                                </span>
                            </td>
                            <td data-label="Tanggal Dibuat">
                                <div class="date-container">
                                    <div class="date-text">{{ $item->created_at->format('d M Y') }}</div>
                                    <div class="date-time">{{ $item->created_at->format('H:i') }}</div>
                                </div>
                            </td>
                            <td data-label="Aksi">
                                <div class="action-buttons">
                                    <a href="{{ route('admin.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.destroy', $item->id) }}" method="POST" class="action-form">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" data-confirm-title="Hapus akun admin" data-confirm-message="Akun admin ini akan dihapus. Apakah Anda yakin?" data-confirm-label="Hapus" data-confirm-tone="danger">
                                            <i class="fas fa-trash"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>Belum ada data admin</h3>
                                    <p>Silakan tambah admin baru untuk memulai mengelola sistem</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('dropdownContent');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.dropdown');
    const dropdownContent = document.getElementById('dropdownContent');
    
    if (!dropdown.contains(event.target)) {
        dropdownContent.classList.remove('show');
    }
});

// Close dropdown on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('dropdownContent').classList.remove('show');
    }
});

// DIHAPUS: JavaScript untuk animasi yang tidak diperlukan
// Enhanced loading animation code DIHAPUS

// Add smooth scroll behavior
document.documentElement.style.scrollBehavior = 'smooth';
</script>

@endsection
