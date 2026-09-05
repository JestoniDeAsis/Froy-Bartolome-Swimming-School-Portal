document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("sidebarToggle");
    const sideMenu = document.getElementById("sideMenu");
    const displayContainer = document.getElementById("displayContainer");
    const backdrop = document.getElementById("sidebarBackdrop");

    function isMobile() {
        return window.matchMedia("(max-width: 768px)").matches;
    }

    function setSidebarOpen(open) {
        sideMenu.classList.toggle("collapsed", !open);
        displayContainer.classList.toggle("expanded", !open);
        if (isMobile()) {
            backdrop.classList.toggle("show", open);
        }
    }

    // Start collapsed on mobile
    if (isMobile()) {
        setSidebarOpen(false);
    }

    toggleBtn.addEventListener("click", function () {
        const isOpen = !sideMenu.classList.contains("collapsed");
        setSidebarOpen(!isOpen);
    });

    backdrop.addEventListener("click", function () {
        setSidebarOpen(false);
    });
});