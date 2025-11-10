<template>
  <div>
    <h3>Clubs registrados</h3>
    <div class="d-flex justify-content-between align-items-center my-3">
      <button class="btn btn-success" @click="openModal">Agregar club</button>
    </div>

    <div v-if="clubs && clubs.length">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Cupo</th>
            <th>Ocupados</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(club, index) in clubs" :key="club.nombre + index">
            <td>{{ club.nombre }}</td>
            <td>{{ club.descripcion }}</td>
            <td>{{ club.cupo }}</td>
            <td>{{ club.ocupados }}</td>
            <td>
              <button class="btn btn-warning btn-sm me-2" @click="startEdit(index)">Editar</button>
              <button class="btn btn-danger btn-sm" @click="confirmDelete(index)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="text-muted">No hay clubs registrados.</p>

    <!-- Modal agregar/editar -->
    <div class="modal fade" id="modalClubsR" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingIndex === null ? 'Agregar nuevo club' : 'Editar club' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input v-model="localClub.nombre" class="form-control mb-2" placeholder="Nombre del club" />
            <input v-model="localClub.descripcion" class="form-control mb-2" placeholder="Descripción" />
            <input v-model.number="localClub.cupo" type="number" min="1" class="form-control mb-2" placeholder="Cupo máximo" />
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" @click="saveClub">{{ editingIndex === null ? 'Guardar' : 'Actualizar' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm delete modal -->
    <div class="modal fade" id="confirmDeleteClub" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>¿Seguro que desea eliminar este club?</p>
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
export default {
  name: 'ClubsR',
  props: ['clubs', 'fechas'],
  data() {
    return {
      localClub: { nombre: '', descripcion: '', cupo: 0 },
      editingIndex: null,
      pendingDeleteIndex: null
    };
  },
  methods: {
    openModal() {
      this.editingIndex = null;
      this.localClub = { nombre: '', descripcion: '', cupo: 0 };
      new bootstrap.Modal(document.getElementById('modalClubsR')).show();
    },
    startEdit(index) {
      this.editingIndex = index;
      const c = this.clubs[index];
      this.localClub = { nombre: c.nombre, descripcion: c.descripcion, cupo: c.cupo };
      new bootstrap.Modal(document.getElementById('modalClubsR')).show();
    },
    saveClub() {
      if (!this.localClub.nombre || !this.localClub.descripcion || this.localClub.cupo <= 0) {
        this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Error', tipo: 'club', descripcion: 'Campos obligatorios' });
        return this.$root.showError ? this.$root.showError('Todos los campos son obligatorios') : null;
      }
      if (this.editingIndex === null) {
        this.$emit('add-club', { ...this.localClub }, 'Usuario Oficina');
      } else {
        this.$emit('edit-club', { index: this.editingIndex, club: { ...this.localClub } }, 'Usuario Oficina');
      }
      bootstrap.Modal.getInstance(document.getElementById('modalClubsR')).hide();
    },
    confirmDelete(index) {
      this.pendingDeleteIndex = index;
      new bootstrap.Modal(document.getElementById('confirmDeleteClub')).show();
    },
    deleteConfirmed() {
      this.$emit('delete-club', this.pendingDeleteIndex, 'Usuario Oficina');
      bootstrap.Modal.getInstance(document.getElementById('confirmDeleteClub')).hide();
    }
  }
};
</script>
