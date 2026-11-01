import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Cloche de notifications (NutriAlert Hub).
 * Compteur et items rendus côté serveur, rafraîchis sans rechargement de page
 * via des appels JSON protégés par le jeton CSRF.
 */
Alpine.data('notificationBell', (config = {}) => ({
    open: false,
    unread: config.unread ?? 0,
    items: config.recent ?? [],
    endpoints: config.endpoints ?? {},
    loading: false,

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    toggle() {
        this.open = !this.open;

        if (this.open) {
            this.refresh();
        }
    },

    badge() {
        return this.unread > 99 ? '99+' : String(this.unread);
    },

    async refresh() {
        if (!this.endpoints.recent) {
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(this.endpoints.recent, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (response.ok) {
                const data = await response.json();
                this.items = data.items ?? [];
                this.unread = data.unread ?? 0;
            }
        } catch (error) {
            // Silencieux : l'UI reste stable si le réseau échoue.
        } finally {
            this.loading = false;
        }
    },

    async markRead(item) {
        if (item.read || !item.id) {
            return;
        }

        try {
            const response = await fetch((this.endpoints.read ?? '').replace('__ID__', item.id), {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.unread = data.unread ?? Math.max(0, this.unread - 1);
                item.read = true;
            }
        } catch (error) {
            // Silencieux.
        }
    },

    async markAll() {
        if (!this.endpoints.readAll || this.unread === 0) {
            return;
        }

        try {
            const response = await fetch(this.endpoints.readAll, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrf(),
                },
            });

            if (response.ok) {
                this.unread = 0;
                this.items = this.items.map((item) => ({ ...item, read: true }));
            }
        } catch (error) {
            // Silencieux.
        }
    },
}));

Alpine.start();
