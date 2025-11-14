
        document.addEventListener("DOMContentLoaded", function() {
            function getInitials(name) {
                if (!name || typeof name !== 'string' || name.trim() === '') return '??';
                const parts = name.trim().split(' ');
                if (parts.length === 1) {
                    return parts[0].charAt(0).toUpperCase();
                }
                return `${parts[0].charAt(0)}${parts[parts.length - 1].charAt(0)}`.toUpperCase();
            }

            const userNameElement = document.getElementById('nama-anggota');
            if(userNameElement) {
                const userName = userNameElement.textContent.trim();
                document.getElementById('navbar-avatar-initials').textContent = getInitials(userName);
            }
        });
