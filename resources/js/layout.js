document.addEventListener('DOMContentLoaded', function() {
    // Modal Logic
    const modalOverlay = document.getElementById('modal-overlay');
    const modalContainer = document.getElementById('modal-container');
    const openModalButton = document.getElementById('open-modal');
    const closeModalButton = document.getElementById('close-modal');

    function openModal() {
        modalOverlay.classList.remove('hidden');
        setTimeout(() => {
            modalOverlay.classList.add('modal-open');
            modalContainer.classList.add('modal-open');
        }, 10);
    }

    function closeModal() {
        modalOverlay.classList.remove('modal-open');
        modalContainer.classList.remove('modal-open');
        setTimeout(() => {
            modalOverlay.classList.add('hidden');
        }, 300);
    }

    if(openModalButton) {
        openModalButton.addEventListener('click', openModal);
    }

    if(closeModalButton) {
        closeModalButton.addEventListener('click', closeModal);
    }

    modalOverlay.addEventListener('click', (e) => {
        if(e.target === modalOverlay) {
            closeModal();
        }
    });
    // Sidebar Mobile Logic
    const mobileHamburgerButton = document.getElementById('mobile-hamburger-button');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const mainContent = document.getElementById('main-content');
    
    function toggleMobileSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    }
    
    if(mobileHamburgerButton && sidebar && sidebarOverlay) {
        mobileHamburgerButton.addEventListener('click', toggleMobileSidebar);
        sidebarOverlay.addEventListener('click', toggleMobileSidebar);
    }

    // Sidebar Desktop Logic
    const desktopSidebarToggle = document.getElementById('desktop-sidebar-toggle');
    const logoContainerExpanded = document.getElementById('logo-container-expanded');
    const logoContainerCollapsed = document.getElementById('logo-container-collapsed');
    const sidebarTexts = document.querySelectorAll('.sidebar-text');
    
    if(desktopSidebarToggle) {
        let isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        
        function applySidebarState(collapsed) {
            if (collapsed) {
                sidebar.classList.replace('w-64', 'w-20');
                mainContent.classList.replace('lg:ml-64', 'lg:ml-20');
                logoContainerExpanded.classList.add('hidden');
                logoContainerCollapsed.classList.remove('hidden');
                sidebarTexts.forEach(text => text.classList.add('opacity-0', 'invisible'));
            } else {
                sidebar.classList.replace('w-20', 'w-64');
                mainContent.classList.replace('lg:ml-20', 'lg:ml-64');
                logoContainerExpanded.classList.remove('hidden');
                logoContainerCollapsed.classList.add('hidden');
                setTimeout(() => {
                    sidebarTexts.forEach(text => text.classList.remove('opacity-0', 'invisible'));
                }, 150);
            }
        }
        
        applySidebarState(isSidebarCollapsed);
        desktopSidebarToggle.addEventListener('click', () => {
            isSidebarCollapsed = !isSidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', isSidebarCollapsed);
            applySidebarState(isSidebarCollapsed);
        });
    }

    // Dark Mode Logic
    const themeLightButton = document.getElementById('theme-light-button');
    const themeDarkButton = document.getElementById('theme-dark-button');
    const htmlElement = document.documentElement;
    const activeThemeClasses = ['bg-gray-200', 'dark:bg-gray-700'];
    
    function applyTheme(theme) {
        if (theme === 'dark') {
            htmlElement.classList.add('dark');
            themeDarkButton.classList.add(...activeThemeClasses);
            themeLightButton.classList.remove(...activeThemeClasses);
        } else {
            htmlElement.classList.remove('dark');
            themeLightButton.classList.add(...activeThemeClasses);
            themeDarkButton.classList.remove(...activeThemeClasses);
        }
    }

    // Initialize theme
    if(themeLightButton && themeDarkButton) {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        applyTheme(savedTheme || (prefersDark ? 'dark' : 'light'));

        themeLightButton.addEventListener('click', () => {
            applyTheme('light');
            localStorage.setItem('theme', 'light');
        });

        themeDarkButton.addEventListener('click', () => {
            applyTheme('dark');
            localStorage.setItem('theme', 'dark');
        });
    }

    // Profile Menu Logic
    const profileMenuButton = document.getElementById('profile-menu-button');
    const profileMenu = document.getElementById('profile-menu');
    
    if(profileMenuButton && profileMenu) {
        profileMenuButton.addEventListener('click', function(event) {
            event.stopPropagation();
            const isHidden = profileMenu.classList.contains('hidden');
            profileMenu.classList.toggle('hidden', !isHidden);
            profileMenu.classList.toggle('opacity-0', !isHidden);
            profileMenu.classList.toggle('scale-95', !isHidden);
            profileMenu.classList.toggle('opacity-100', isHidden);
            profileMenu.classList.toggle('scale-100', isHidden);
        });

        window.addEventListener('click', function(e) {
            if (!profileMenu.contains(e.target) && !profileMenuButton.contains(e.target)) {
                profileMenu.classList.add('opacity-0', 'scale-95');
                profileMenu.classList.remove('opacity-100', 'scale-100');
                setTimeout(() => profileMenu.classList.add('hidden'), 100);
            }
        });
    }
});
