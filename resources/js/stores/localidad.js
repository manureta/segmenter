import { defineStore } from 'pinia';
import axios from 'axios';

export const useLocalidadStore = defineStore('localidad', {
    state: () => ({
        localidades: [],
        currentLocalidad: null,
        radios: [],
        svgCache: new Map(),
        loading: false,
        error: null,
        pagination: {
            currentPage: 1,
            perPage: 15,
            total: 0,
            lastPage: 1
        },
        filters: {
            search: '',
            provinciaId: null,
            departamentoId: null,
            withCarto: false,
            withListado: false,
            segmented: false
        }
    }),

    getters: {
        hasLocalidades: (state) => state.localidades.length > 0,
        
        filteredLocalidades: (state) => {
            let filtered = state.localidades;
            
            if (state.filters.search) {
                const search = state.filters.search.toLowerCase();
                filtered = filtered.filter(loc => 
                    loc.nombre.toLowerCase().includes(search) ||
                    loc.codigo.includes(search)
                );
            }
            
            if (state.filters.withCarto) {
                filtered = filtered.filter(loc => loc.processing_stats?.has_carto);
            }
            
            if (state.filters.withListado) {
                filtered = filtered.filter(loc => loc.processing_stats?.has_listado);
            }
            
            if (state.filters.segmented) {
                filtered = filtered.filter(loc => loc.processing_stats?.has_segmentation);
            }
            
            return filtered;
        },
        
        processingStats: (state) => {
            const stats = {
                total: state.localidades.length,
                withCarto: 0,
                withListado: 0,
                segmented: 0,
                radiosTotal: 0
            };
            
            state.localidades.forEach(loc => {
                if (loc.processing_stats) {
                    if (loc.processing_stats.has_carto) stats.withCarto++;
                    if (loc.processing_stats.has_listado) stats.withListado++;
                    if (loc.processing_stats.has_segmentation) stats.segmented++;
                    stats.radiosTotal += loc.processing_stats.radios_count || 0;
                }
            });
            
            return stats;
        }
    },

    actions: {
        async fetchLocalidades(params = {}) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/localidades', {
                    params: {
                        ...this.filters,
                        ...params,
                        page: this.pagination.currentPage,
                        per_page: this.pagination.perPage,
                        include_stats: true
                    }
                });
                
                this.localidades = response.data.data;
                this.pagination = {
                    currentPage: response.data.meta.current_page,
                    perPage: response.data.meta.per_page,
                    total: response.data.meta.total,
                    lastPage: response.data.meta.last_page
                };
                
            } catch (error) {
                this.error = error.response?.data?.message || 'Error loading localidades';
                console.error('Error fetching localidades:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchLocalidad(codigo) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get(`/localidades/${codigo}`);
                this.currentLocalidad = response.data.localidad;
                this.currentLocalidad.processing_stats = response.data.processing_stats;
                this.currentLocalidad.required_tables = response.data.required_tables;
                
                return this.currentLocalidad;
            } catch (error) {
                this.error = error.response?.data?.message || 'Error loading localidad';
                console.error('Error fetching localidad:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchRadios(codigo) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get(`/localidades/${codigo}/radios`);
                this.radios = response.data.data;
                return this.radios;
            } catch (error) {
                this.error = error.response?.data?.message || 'Error loading radios';
                console.error('Error fetching radios:', error);
                this.radios = [];
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchSvg(codigo) {
            // Check cache first
            if (this.svgCache.has(codigo)) {
                return this.svgCache.get(codigo);
            }
            
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get(`/localidades/${codigo}/svg`);
                const svg = response.data.svg;
                
                // Cache the result
                this.svgCache.set(codigo, svg);
                
                return svg;
            } catch (error) {
                this.error = error.response?.data?.message || 'Error generating SVG';
                console.error('Error fetching SVG:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async bulkStats(codigos) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.post('/localidades/bulk-stats', { codigos });
                return response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Error loading bulk stats';
                console.error('Error fetching bulk stats:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async clearCache(codigo) {
            try {
                await axios.delete(`/localidades/${codigo}/cache`);
                
                // Clear local cache too
                this.svgCache.delete(codigo);
                
                // Refresh current localidad if it's the same
                if (this.currentLocalidad?.codigo === codigo) {
                    await this.fetchLocalidad(codigo);
                }
                
                return true;
            } catch (error) {
                this.error = error.response?.data?.message || 'Error clearing cache';
                console.error('Error clearing cache:', error);
                throw error;
            }
        },

        updateFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters };
            this.pagination.currentPage = 1; // Reset to first page when filtering
        },

        setPage(page) {
            this.pagination.currentPage = page;
        },

        setPerPage(perPage) {
            this.pagination.perPage = perPage;
            this.pagination.currentPage = 1;
        },

        clearError() {
            this.error = null;
        },

        reset() {
            this.localidades = [];
            this.currentLocalidad = null;
            this.radios = [];
            this.svgCache.clear();
            this.loading = false;
            this.error = null;
            this.pagination = {
                currentPage: 1,
                perPage: 15,
                total: 0,
                lastPage: 1
            };
            this.filters = {
                search: '',
                provinciaId: null,
                departamentoId: null,
                withCarto: false,
                withListado: false,
                segmented: false
            };
        }
    }
});