<template>
  <div>
    <h3>Auditoría</h3>
    <p class="text-muted">Registros de acciones realizadas por usuarios del sistema</p>

    <div v-if="loading" class="text-center my-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <table v-else class="table table-striped table-bordered">
      <thead class="table-primary">
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Tipo</th>
          <th>Acción</th>
          <th>Descripción</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="registrosFiltrados.length === 0">
          <td colspan="5" class="text-center text-muted">
            No hay registros de auditoría relevantes
          </td>
        </tr>
        <tr v-for="r in registrosFiltrados" :key="r.id">
          <td>{{ formatFecha(r.fecha) }}</td>
          <td>{{ r.usuario_nombre || 'Usuario desconocido' }}</td>
          <td>
            <span 
              class="badge" 
              :class="r.usuario_tipo === 'OFICINA' ? 'bg-primary' : 'bg-success'"
            >
              {{ r.usuario_tipo || 'N/A' }}
            </span>
          </td>
          <td>{{ r.accion }}</td>
          <td>{{ r.descripcion }}</td>
        </tr>
      </tbody>
    </table>

    <div class="mt-3">
      <button class="btn btn-outline-primary" @click="cargarAuditoria">
        <i class="bi bi-arrow-clockwise"></i> Actualizar
      </button>
    </div>
  </div>
</template>

<script>
import { getAuditoria } from '../services/api';

export default {
  name: 'Auditoria',
  data() {
    return {
      registros: [],
      loading: false,
      error: null
    };
  },
  computed: {
    registrosFiltrados() {
      const permitidos = ['Insertar', 'Eliminar', 'Editar', 'Asistencia'];
      return (this.registros || []).filter(r => 
        permitidos.includes(r.accion)
      );
    }
  },
  methods: {
    async cargarAuditoria() {
      this.loading = true;
      this.error = null;
      try {
        this.registros = await getAuditoria();
      } catch (e) {
        this.error = e.message || 'Error al cargar auditoría';
        console.error('Error cargando auditoría:', e);
      } finally {
        this.loading = false;
      }
    },
    formatFecha(fecha) {
      if (!fecha) return '';
      const d = new Date(fecha);
      return d.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      });
    }
  },
  mounted() {
    this.cargarAuditoria();
    // Auto-actualizar cada 30 segundos
    this.interval = setInterval(() => {
      this.cargarAuditoria();
    }, 30000);
  },
  beforeUnmount() {
    if (this.interval) {
      clearInterval(this.interval);
    }
  }
};
</script>

<style scoped>
.table {
  font-size: 0.9rem;
}
</style>
