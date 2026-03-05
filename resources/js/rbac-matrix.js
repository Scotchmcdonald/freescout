/**
 * RBAC Permission Matrix — Alpine.js Component
 *
 * Manages:
 * - Permission matrix state (role × permission checkbox grid)
 * - Accordion expand/collapse for module groups
 * - Search/filter for permissions
 * - Optimistic UI toggles with Axios
 * - Bulk toggle per module/role
 */
export default function rbacMatrix(initialMatrix, updateUrl, csrfToken) {
    return {
        /**
         * Matrix state: { roleId: { permissionId: boolean } }
         * @type {Object<number, Object<number, boolean>>}
         */
        matrix: initialMatrix || {},

        /**
         * Search/filter query string
         * @type {string}
         */
        search: '',

        /**
         * Set of module keys currently expanded
         * @type {Set<string>}
         */
        expandedModules: new Set(),

        /**
         * Loading state per cell: "roleId-permissionId" => true
         * @type {Object<string, boolean>}
         */
        loading: {},

        /**
         * Toast message
         * @type {{ message: string, type: string } | null}
         */
        toast: null,

        /**
         * Initialize component
         */
        init() {
            // Expand the first module group by default
            this.$nextTick(() => {
                const firstAccordion = this.$el.querySelector('[data-module-key]');
                if (firstAccordion) {
                    this.expandedModules.add(firstAccordion.dataset.moduleKey);
                }
            });
        },

        /**
         * Check if a permission is enabled for a role
         */
        isChecked(roleId, permissionId) {
            return !!(this.matrix[roleId] && this.matrix[roleId][permissionId]);
        },

        /**
         * Toggle a single permission for a role
         */
        async togglePermission(roleId, permissionId, attached) {
            const key = `${roleId}-${permissionId}`;
            const previousState = this.isChecked(roleId, permissionId);

            // Optimistic update
            if (!this.matrix[roleId]) {
                this.matrix[roleId] = {};
            }
            this.matrix[roleId][permissionId] = attached;
            this.loading[key] = true;

            try {
                await axios.post(updateUrl, {
                    role_id: roleId,
                    permission_id: permissionId,
                    attached: attached,
                    _token: csrfToken,
                });
                this.showToast('Permission updated', 'success');
            } catch (error) {
                // Revert on failure
                this.matrix[roleId][permissionId] = previousState;
                this.showToast('Failed to update permission', 'error');
                console.error('[RBAC] Toggle failed:', error);
            } finally {
                delete this.loading[key];
            }
        },

        /**
         * Toggle all permissions in a module group for a specific role
         * If all are checked → uncheck all. Otherwise → check all.
         */
        async toggleModuleForRole(modulePermissionIds, roleId) {
            const state = this.moduleTriState(roleId, modulePermissionIds);
            const newState = state !== 'all'; // if all → uncheck; if some/none → check all

            const promises = [];
            for (const permId of modulePermissionIds) {
                if (this.isChecked(roleId, permId) !== newState) {
                    promises.push(this.togglePermission(roleId, permId, newState));
                }
            }
            await Promise.all(promises);
        },

        /**
         * Compute tri-state for a module/role: 'all', 'some', or 'none'
         */
        moduleTriState(roleId, modulePermissionIds) {
            if (!this.matrix[roleId] || modulePermissionIds.length === 0) return 'none';
            const count = modulePermissionIds.filter(id => this.matrix[roleId][id]).length;
            if (count === 0) return 'none';
            if (count === modulePermissionIds.length) return 'all';
            return 'some';
        },

        /**
         * Check if a permission name matches the current search query
         */
        matchesSearch(permissionName) {
            if (!this.search || this.search.trim() === '') {
                return true;
            }
            const query = this.search.toLowerCase().trim();
            return permissionName.toLowerCase().includes(query);
        },

        /**
         * Check if a module group has any permissions matching the search
         */
        moduleMatchesSearch(permissionNames) {
            if (!this.search || this.search.trim() === '') {
                return true;
            }
            return permissionNames.some(name => this.matchesSearch(name));
        },

        /**
         * Check if a cell is currently loading
         */
        isLoading(roleId, permissionId) {
            return !!this.loading[`${roleId}-${permissionId}`];
        },

        /**
         * Show a temporary toast notification
         */
        showToast(message, type = 'success') {
            this.toast = { message, type };
            setTimeout(() => {
                this.toast = null;
            }, 2000);
        },

        /**
         * Count enabled permissions for a role within a set of permission IDs
         */
        countEnabled(roleId, permissionIds) {
            if (!this.matrix[roleId]) return 0;
            return permissionIds.filter(id => this.matrix[roleId][id]).length;
        },
    };
}
