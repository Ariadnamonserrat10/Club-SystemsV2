<template>
  <div>
    <h3>Carreras registradas</h3>
    <div class="d-flex justify-content-between align-items-center my-3">
      <button class="btn btn-success" @click="openModal">Agregar carrera</button>
    </div>

    <div v-if="carreras && carreras.length">
      <table class="table table-bordered table-striped align-middle table-fit">
        <thead class="table-primary">
          <tr>
            <th class="col-nombre">Nombre</th>
            <th class="col-abreviatura">Abreviatura</th>
            <th class="col-acciones">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(carrera, index) in carreras" :key="carrera.id || index">
            <td class="col-nombre">{{ carrera.nombre }}</td>
            <td class="col-abreviatura">{{ carrera.abreviatura }}</td>
            <td class="col-acciones">
              <div class="flex-actions">
                <button class="btn btn-warning btn-sm me-2" @click="startEdit(index)">Editar</button>
                <button class="btn btn-danger btn-sm" @click="confirmDelete(index)">Eliminar</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="text-muted">No hay carreras registradas.</p>

    <!-- Modal agregar/editar -->
    <div class="modal fade" id="modalCarreras" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingIndex === null ? 'Agregar nueva carrera' : 'Editar carrera' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input v-model="localCarrera.nombre" class="form-control mb-2" placeholder="Nombre de la carrera" />
            <input v-model="localCarrera.abreviatura" class="form-control mb-2" placeholder="Abreviatura" />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" @click="saveCarrera">{{ editingIndex === null ? 'Guardar' : 'Actualizar' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm delete modal -->
    <div class="modal fade" id="confirmDeleteCarrera" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>¿Seguro que desea eliminar esta carrera?</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger" @click="deleteConfirmed">Eliminar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import * as api from '../services/api';

export default {
  name: 'CarrerasR',
  props: ['carreras'],
  data() {
    return {
      localCarrera: { nombre: '', abreviatura: '' },
      editingIndex: null,
      pendingDeleteIndex: null
    };
  },
  methods: {
    openModal() {
      this.editingIndex = null;
      this.localCarrera = { nombre: '', abreviatura: '' };
      new bootstrap.Modal(document.getElementById('modalCarreras')).show();
    },
    startEdit(index) {
      this.editingIndex = index;
      const c = this.carreras[index];
      this.localCarrera = { nombre: c.nombre, abreviatura: c.abreviatura };
      new bootstrap.Modal(document.getElementById('modalCarreras')).show();
    },
    async saveCarrera() {
      if (!this.localCarrera.nombre || !this.localCarrera.abreviatura) {
        return this.$root.showError ? this.$root.showError('Todos los campos son obligatorios') : alert('Todos los campos son obligatorios');
      }
      
      try {
        if (this.editingIndex === null) {
          await api.createCarrera(this.localCarrera);
          this.$emit('log', {
            accion: 'Insertar',
            tipo: 'carrera',
            descripcion: `Se creó la carrera "${this.localCarrera.nombre}"`
          });
          this.$emit('refresh-carreras');
        } else {
          const id = this.carreras[this.editingIndex].id;
          const prev = this.carreras[this.editingIndex].nombre;
          await api.updateCarrera(id, this.localCarrera);
          this.$emit('log', {
            accion: 'Editar',
            tipo: 'carrera',
            descripcion: `Se actualizó la carrera "${prev}" -> "${this.localCarrera.nombre}"`
          });
          this.$emit('refresh-carreras');
        }
        bootstrap.Modal.getInstance(document.getElementById('modalCarreras')).hide();
      } catch (error) {
        console.error(error);
        if (this.$root.showError) this.$root.showError(error.message);
      }
    },
    confirmDelete(index) {
      this.pendingDeleteIndex = index;
      new bootstrap.Modal(document.getElementById('confirmDeleteCarrera')).show();
    },
    async deleteConfirmed() {
      try {
        const c = this.carreras[this.pendingDeleteIndex];
        const id = c.id;
        await api.deleteCarrera(id);
        this.$emit('log', {
          accion: 'Eliminar',
          tipo: 'carrera',
          descripcion: `Se eliminó la carrera "${c.nombre}"`
        });
        this.$emit('refresh-carreras');
        bootstrap.Modal.getInstance(document.getElementById('confirmDeleteCarrera')).hide();
      } catch (error) {
        console.error(error);
        if (this.$root.showError) this.$root.showError(error.message);
      }
    }
  }
};
</script>

<style scoped>
.table-fit {
  width: 100% !important;
  margin-bottom: 1rem;
}
.table th {
  text-align: center;
  vertical-align: middle;
}
.col-nombre {
  text-align: left;
  padding-right: 20px;
}
.col-abreviatura {
  text-align: center;
  width: 120px;
}
.col-acciones {
  width: 180px;
  text-align: center;
}
.flex-actions {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
