<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FBSS Portal')</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/images/icon_fbss.png') }}">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/our_theme.css') }}">

    <style>

        #toastStack {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
            max-width: 420px;
            padding: 0 1rem;
            pointer-events: none;
        }

        .toast-item {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 0.5rem;
            border-left: 4px solid transparent;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.08);
            font-size: 0.925rem;
            background-color: #fff;
            color: #212529;
            opacity: 0;
            transform: translateY(-30px);
            animation: toast-in 0.35s ease forwards;
        }

        .toast-item.toast-hide {
            animation: toast-out 0.35s ease forwards;
        }

        .toast-item .toast-message {
            flex: 1;
            word-break: break-word;
        }

        .toast-item .toast-close {
            background: none;
            border: none;
            color: #6c757d;
            opacity: 0.7;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            padding: 0;
        }

        .toast-item .toast-close:hover {
            opacity: 1;
        }

        /* Type accents: colored left border + matching message text color */
        .toast-danger {
            border-left-color: #dc3545;
        }
        .toast-danger .toast-message {
            color: #b02a37;
        }

        .toast-success {
            border-left-color: #198754;
        }
        .toast-success .toast-message {
            color: #146c43;
        }

        .toast-warning {
            border-left-color: #ffc107;
        }
        .toast-warning .toast-message {
            color: #997404;
        }

        .toast-info {
            border-left-color: #0dcaf0;
            background-color: #fff;
        }
        .toast-info .toast-message {
            color: #087990;
        }

        @keyframes toast-in {
            from { opacity: 0; transform: translateY(-30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @keyframes toast-out {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-20px); }
        }

        @media (max-width: 480px) {
            #toastStack {
                max-width: 100%;
                padding: 0 0.75rem;
            }
        }
    </style>

    @yield('styles')
</head>
<body>

    <div id="toastStack"></div>

    @yield('content')

    <script>
        function showToast(message, type = "danger", duration = 4000) {
            const stack = document.getElementById("toastStack");

            const toast = document.createElement("div");
            toast.className = "toast-item toast-" + type;
            toast.innerHTML = `
                <span class="toast-message"></span>
                <button type="button" class="toast-close" aria-label="Close">&times;</button>
            `;
            toast.querySelector(".toast-message").textContent = message;

            const remove = () => {
                toast.classList.add("toast-hide");
                toast.addEventListener("animationend", () => toast.remove(), { once: true });
            };

            toast.querySelector(".toast-close").addEventListener("click", remove);

            stack.appendChild(toast);

            const timer = setTimeout(remove, duration);

            toast.addEventListener("mouseenter", () => clearTimeout(timer));
        }

        // Keep the toast stack pinned to the actual visible viewport,
        // even when the mobile keyboard resizes/shifts it.
        const TOAST_TOP_MARGIN = 16;

        function pinToastToViewport() {
            const stack = document.getElementById("toastStack");
            if (!stack) return;

            const offset = window.visualViewport ? window.visualViewport.offsetTop : 0;
            stack.style.top = (offset + TOAST_TOP_MARGIN) + "px";
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener("resize", pinToastToViewport);
            window.visualViewport.addEventListener("scroll", pinToastToViewport);
        }
        window.addEventListener("orientationchange", pinToastToViewport);
        window.addEventListener("DOMContentLoaded", pinToastToViewport);
    </script>

    @yield('scripts')

</body>
</html>