<template>
  <div class="localidad-list">
    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Filtros</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Buscar</label>
            <input 
              v-model="filters.search" 
              type="text" 
              class="form-control" 
              placeholder="Nombre o código..."
              @input="debouncedSearch"
            >
          </div>
          <div class="col-md-2">
            <div class="form-check mt-4">
              <input 
                v-model="filters.withCarto" 
                class="form-check-input" 
                type="checkbox" 
                id="filterCarto"
                @change="applyFilters"
              >
              <label class="form-check-label" for="filterCarto">
                Con cartografía
              </label>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-check mt-4">
              <input 
                v-model="filters.withListado" 
                class="form-check-input" 
                type="checkbox" 
                id="filterListado"
                @change="applyFilters"
              >
              <label class="form-check-label" for="filterListado">
                Con listado
              </label>
            </div>
          </div>
          <div class="col-md-2">
            <div class="form-check mt-4">
              <input 
                v-model="filters.segmented" 
                class="form-check-input" 
                type="checkbox" 
                id="filterSegmented"
                @change="applyFilters"
              >
              <label class="form-check-label" for="filterSegmented">
                Segmentadas
              </label>
            </div>
          </div>
          <div class="col-md-2">
            <button 
              @click="resetFilters" 
              class="btn btn-secondary mt-4"
            >
              Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <LocalidadStats 
      v-if="showStats" 
      :stats="processingStats" 
      class="mb-4"
    />

    <!-- Loading -->
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="alert alert-danger" role="alert">
      {{ error }}
      <button @click="clearError" class="btn-close float-end"></button>
    </div>

    <!-- Results -->
    <div v-else class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
          Localidades 
          <span class="badge bg-secondary">{{ pagination.total }}</span>
        </h5>
        <div class="d-flex gap-2">
          <select 
            v-model="pagination.perPage" 
            @change="changePerPage"
            class="form-select form-select-sm"
          >
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <button 
            @click="refresh" 
            class="btn btn-sm btn-outline-primary"
            :disabled="loading"
          >
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
      </div>
      
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Estado</th>
              <th>Radios</th>
              <th>Segmentos</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="localidad in localidades" :key="localidad.codigo">
              <td>
                <code>{{ localidad.codigo }}</code>
              </td>
              <td>{{ localidad.nombre }}</td>
              <td>
                <div class="d-flex gap-1">
                  <span 
                    v-if="localidad.processing_stats?.has_carto"
                    class="badge bg-success"
                    title="Cartografía disponible"
                  >
                    C
                  </span>
                  <span 
                    v-if="localidad.processing_stats?.has_listado"
                    class="badge bg-info"
                    title="Listado disponible"
                  >
                    L
                  </span>
                  <span 
                    v-if="localidad.processing_stats?.has_segmentation"
                    class="badge bg-warning"
                    title="Segmentación disponible"
                  >
                    S
                  </span>
                </div>
              </td>
              <td>
                <span class="badge bg-light text-dark">
                  {{ localidad.processing_stats?.radios_count || 0 }}
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark">
                  {{ localidad.processing_stats?.segments_count || 0 }}
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button 
                    @click="viewLocalidad(localidad)"
                    class="btn btn-outline-primary"
                    title="Ver detalles"
                  >
                    <i class="fas fa-eye"></i>
                  </button>
                  <button 
                    v-if="localidad.processing_stats?.has_carto"
                    @click="showMap(localidad)"
                    class="btn btn-outline-success"
                    title="Ver mapa"
                  >
                    <i class="fas fa-map"></i>
                  </button>
                  <button 
                    @click="clearCache(localidad)"
                    class="btn btn-outline-warning"
                    title="Limpiar cache"
                    :disabled="loading"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="card-footer" v-if="pagination.lastPage > 1">
        <nav>
          <ul class="pagination justify-content-center mb-0">
            <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
              <button 
                @click="goToPage(pagination.currentPage - 1)"
                class="page-link"
                :disabled="pagination.currentPage === 1"
              >
                Anterior
              </button>
            </li>
            
            <li 
              v-for="page in visiblePages" 
              :key="page"
              class="page-item" 
              :class="{ active: page === pagination.currentPage }"
            >
              <button 
                @click="goToPage(page)"
                class="page-link"
              >
                {{ page }}
              </button>
            </li>
            
            <li class="page-item" :class="{ disabled: pagination.currentPage === pagination.lastPage }">
              <button 
                @click="goToPage(pagination.currentPage + 1)"
                class="page-link"
                :disabled="pagination.currentPage === pagination.lastPage"
              >
                Siguiente
              </button>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Localidad Detail Modal -->
    <div 
      v-if="selectedLocalidad"
      class="modal fade show d-block"
      tabindex="-1"
      style="background-color: rgba(0,0,0,0.5)"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ selectedLocalidad.nombre }}
              <small class="text-muted">{{ selectedLocalidad.codigo }}</small>
            </h5>
            <button 
              @click="closeModal"
              class="btn-close"
            ></button>
          </div>
          <div class="modal-body">
            <LocalidadStats 
              :stats="selectedLocalidad.processing_stats" 
              :detailed="true"
            />
            
            <div class="mt-3">
              <h6>Acciones</h6>
              <div class="d-flex gap-2">
                <button 
                  v-if="selectedLocalidad.processing_stats?.has_listado"
                  @click="loadRadios(selectedLocalidad)"
                  class="btn btn-primary"
                >
                  Ver Radios
                </button>
                <button 
                  v-if="selectedLocalidad.processing_stats?.has_carto"
                  @click="showMap(selectedLocalidad)"
                  class="btn btn-success"
                >
                  Ver Mapa
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { useLocalidadStore } from '../stores/localidad';
import { storeToRefs } from 'pinia';
import LocalidadStats from './LocalidadStats.vue';

export default {
  name: 'LocalidadList',
  components: {
    LocalidadStats
  },
  props: {
    showStats: {
      type: Boolean,
      default: true
    }
  },
  emits: ['localidad-selected', 'show-map', 'show-radios'],
  
  setup(props, { emit }) {
    const store = useLocalidadStore();
    const { 
      localidades, 
      loading, 
      error, 
      pagination, 
      processingStats 
    } = storeToRefs(store);

    const selectedLocalidad = ref(null);
    const filters = ref({
      search: '',
      withCarto: false,
      withListado: false,
      segmented: false
    });

    let searchTimeout = null;

    const visiblePages = computed(() => {
      const current = pagination.value.currentPage;
      const last = pagination.value.lastPage;
      const delta = 2;
      const range = [];

      for (
        let i = Math.max(2, current - delta);
        i <= Math.min(last - 1, current + delta);
        i++
      ) {
        range.push(i);
      }

      if (current - delta > 2) {
        range.unshift('...');
      }
      if (current + delta < last - 1) {
        range.push('...');
      }

      range.unshift(1);
      if (last > 1) {
        range.push(last);
      }

      return range;
    });

    const debouncedSearch = () => {
      if (searchTimeout) {
        clearTimeout(searchTimeout);
      }
      searchTimeout = setTimeout(() => {
        applyFilters();
      }, 500);
    };

    const applyFilters = () => {
      store.updateFilters(filters.value);
      store.fetchLocalidades();
    };

    const resetFilters = () => {
      filters.value = {
        search: '',
        withCarto: false,
        withListado: false,
        segmented: false
      };
      applyFilters();
    };

    const goToPage = (page) => {
      if (page >= 1 && page <= pagination.value.lastPage) {
        store.setPage(page);
        store.fetchLocalidades();
      }
    };

    const changePerPage = () => {
      store.setPerPage(pagination.value.perPage);
      store.fetchLocalidades();
    };

    const refresh = () => {
      store.fetchLocalidades();
    };

    const clearError = () => {
      store.clearError();
    };

    const viewLocalidad = async (localidad) => {
      try {
        selectedLocalidad.value = await store.fetchLocalidad(localidad.codigo);
        emit('localidad-selected', selectedLocalidad.value);
      } catch (error) {
        console.error('Error loading localidad:', error);
      }
    };

    const closeModal = () => {
      selectedLocalidad.value = null;
    };

    const showMap = (localidad) => {
      emit('show-map', localidad);
    };

    const loadRadios = (localidad) => {
      emit('show-radios', localidad);
    };

    const clearCache = async (localidad) => {
      try {
        await store.clearCache(localidad.codigo);
        await store.fetchLocalidades(); // Refresh list
      } catch (error) {
        console.error('Error clearing cache:', error);
      }
    };

    onMounted(() => {
      store.fetchLocalidades();
    });

    return {
      localidades,
      loading,
      error,
      pagination,
      processingStats,
      selectedLocalidad,
      filters,
      visiblePages,
      debouncedSearch,
      applyFilters,
      resetFilters,
      goToPage,
      changePerPage,
      refresh,
      clearError,
      viewLocalidad,
      closeModal,
      showMap,
      loadRadios,
      clearCache
    };
  }
};
</script>

<style scoped>
.localidad-list {
  max-width: 100%;
}

.badge {
  font-size: 0.7rem;
}

.table th {
  border-top: none;
  font-weight: 600;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
}

.modal {
  z-index: 1050;
}

.page-item.disabled .page-link {
  cursor: not-allowed;
}
</style>