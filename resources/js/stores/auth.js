import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        permissions: [],
        roles: [],
        loading: false,
        error: null
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        
        hasPermission: (state) => (permission) => {
            return state.permissions.includes(permission);
        },
        
        hasRole: (state) => (role) => {
            return state.roles.includes(role);
        },
        
        hasAnyRole: (state) => (roles) => {
            return roles.some(role => state.roles.includes(role));
        },
        
        canAccess: (state) => (resource) => {
            const resourcePermissions = {
                'localidades': ['view localidades'],
                'segmentation': ['manage segmentation'],
                'admin': ['admin access'],
                'stats': ['view stats']
            };
            
            const required = resourcePermissions[resource] || [];
            return required.some(permission => state.permissions.includes(permission));
        }
    },

    actions: {
        async loadUser(userId = null) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/user');
                this.user = response.data;
                this.permissions = response.data.permissions || [];
                this.roles = response.data.roles || [];
                
            } catch (error) {
                this.error = error.response?.data?.message || 'Error loading user';
                console.error('Error loading user:', error);
                
                // Clear auth data on error
                this.user = null;
                this.permissions = [];
                this.roles = [];
            } finally {
                this.loading = false;
            }
        },

        async login(credentials) {
            this.loading = true;
            this.error = null;
            
            try {
                // Get CSRF token
                await axios.get('/sanctum/csrf-cookie');
                
                // Login
                await axios.post('/login', credentials);
                
                // Load user data
                await this.loadUser();
                
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed';
                console.error('Login error:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true;
            
            try {
                await axios.post('/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.user = null;
                this.permissions = [];
                this.roles = [];
                this.loading = false;
                this.error = null;
            }
        },

        clearError() {
            this.error = null;
        },

        updateUser(userData) {
            this.user = { ...this.user, ...userData };
        }
    }
});