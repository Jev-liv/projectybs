{{--
Layout: App (Authenticated)
Digunakan untuk semua halaman yang membutuhkan autentikasi.
Menyertakan sidebar navigasi dan topbar.
--}}
@props(['title' => 'Dashboard'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ config('app.name', 'YBS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50">
    {{-- Offline Indicator --}}
    <x-offline-indicator />

    {{-- Mobile Backdrop Overlay --}}
    <div id="sidebarBackdrop" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

    {{-- ── Layout Wrapper: flex row (sidebar | konten) ────────────── --}}
    <div class="flex min-h-screen">

        {{-- ── Sidebar ──────────────────────────────────────────────── --}}
        <x-sidebar />

        {{-- ── Konten Utama ─────────────────────────────────────────── --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <x-navbar :title="$title" />

            {{-- Page Content --}}
            <main class="flex-1 p-4 md:p-6 overflow-x-hidden">
                {{ $slot }}
            </main>

        </div>

    </div>

    @if (auth()->user()?->office === 'YBS' && request()->routeIs('kernel.*', 'analisa-moisture.*', 'lap-jangkos.*', 'oil-loss-foss.*', 'process.*'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function roundToHalfHour(value) {
                    if (!value) {
                        return value;
                    }

                    const parts = value.split(':');
                    if (parts.length < 2) {
                        return value;
                    }

                    const hour = parseInt(parts[0], 10);
                    const minute = parseInt(parts[1], 10);
                    if (Number.isNaN(hour) || Number.isNaN(minute)) {
                        return value;
                    }

                    const roundedMinute = minute < 30 ? 0 : 30;
                    return String(hour).padStart(2, '0') + ':' + String(roundedMinute).padStart(2, '0');
                }

                function normalizeTimeInput(input) {
                    if (!input || !input.value || !/^\d{2}:\d{2}$/.test(input.value)) {
                        return;
                    }

                    const rounded = roundToHalfHour(input.value);
                    if (rounded !== input.value) {
                        input.value = rounded;
                    }
                }

                document.querySelectorAll('input[type="time"]').forEach(function (input) {
                    normalizeTimeInput(input);
                    input.addEventListener('change', function () {
                        normalizeTimeInput(input);
                    });
                    input.addEventListener('blur', function () {
                        normalizeTimeInput(input);
                    });
                });
            });
        </script>
    @endif

    {{-- Auto Flash Messages dengan SweetAlert Toast --}}
    <script>
        function lockFormSubmission(form, submitter = null) {
            if (!form || form.dataset.submitting === '1') {
                return;
            }

            form.dataset.submitting = '1';

            const action = (form.getAttribute('action') || '').toLowerCase();
            const isExport = action.includes('export') || form.hasAttribute('data-no-lock');

            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach((button) => {
                button.disabled = true;

                if (button.tagName === 'BUTTON') {
                    if (!button.dataset.originalHtml) {
                        button.dataset.originalHtml = button.innerHTML;
                    }
                    if (button === submitter) {
                        button.innerHTML = isExport ? 'Memproses...' : 'Menyimpan...';
                    }
                } else if (button.tagName === 'INPUT') {
                    if (!button.dataset.originalValue) {
                        button.dataset.originalValue = button.value;
                    }
                    if (button === submitter) {
                        button.value = isExport ? 'Memproses...' : 'Menyimpan...';
                    }
                }
            });

            if (isExport) {
                // Remove cookie before request
                document.cookie = "export_done=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                
                const interval = setInterval(() => {
                    if (document.cookie.indexOf("export_done=1") !== -1) {
                        clearInterval(interval);
                        
                        submitButtons.forEach((button) => {
                            button.disabled = false;
                            if (button === submitter) {
                                if (button.tagName === 'BUTTON') {
                                    button.innerHTML = button.dataset.originalHtml || 'Export';
                                } else {
                                    button.value = button.dataset.originalValue || 'Export';
                                }
                            }
                        });
                        
                        // Reset submitting state so form can be submitted again
                        form.dataset.submitting = '0';
                        
                        // Clean up cookie
                        document.cookie = "export_done=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    }
                }, 500);
            }
        }

        window.lockFormSubmission = lockFormSubmission;

        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                window.showSuccess({!! json_encode(session('success')) !!});
            @endif

            @if (session('error'))
                window.showError({!! json_encode(session('error')) !!});
            @endif

            @if (session('info'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: {!! json_encode(session('info')) !!},
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            @endif

            @if (session('warning'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: {!! json_encode(session('warning')) !!},
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            @endif
        });

        // Mobile Sidebar Toggle
        const mobileToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');

        function toggleMobileSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        if (mobileToggle) {
            mobileToggle.addEventListener('click', toggleMobileSidebar);
        }

        if (backdrop) {
            backdrop.addEventListener('click', toggleMobileSidebar);
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method === 'get' || event.defaultPrevented) {
                return;
            }

            lockFormSubmission(form, event.submitter ?? null);
        }, false);

        // Close sidebar saat resize ke desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        });
    </script>

</body>

</html>