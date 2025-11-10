<template>
  <div>
    <h3>Alumnos registrados</h3>

    <div class="d-flex justify-content-between align-items-center my-3">
      <div>
        <button class="btn btn-success" @click="openAddModal">Agregar alumno</button>
      </div>
    </div>

    <table class="table table-hover table-bordered">
      <thead class="table-primary">
        <tr>
          <th>Nombre completo</th>
          <th>Carrera</th>
          <th>Semestre</th>
          <th>Número de control</th>
          <th>Teléfono</th>
          <th>Club</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(alumno, index) in alumnos" :key="alumno.control + index">
          <td>{{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}</td>
          <td>{{ alumno.carrera }}</td>
          <td>{{ alumno.semestre }}</td>
          <td>{{ alumno.control }}</td>
          <td>{{ alumno.telefono }}</td>
          <td>{{ alumno.club }}</td>
          <td>
            <button class="btn btn-danger btn-sm" @click="confirmDelete(index)">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal agregar alumno (reutilizable) -->
    <div class="modal fade" id="modalAddRegistered" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Agregar alumno</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-4"><input v-model="form.nombre" class="form-control" placeholder="Nombre(s)" /></div>
              <div class="col-md-4"><input v-model="form.apellidoP" class="form-control" placeholder="Apellido paterno" /></div>
              <div class="col-md-4"><input v-model="form.apellidoM" class="form-control" placeholder="Apellido materno" /></div>

              <div class="col-md-6 mt-2">
                <select v-model="form.carrera" class="form-select">
                  <option disabled value="">Selecciona carrera</option>
                  <option>Ingeniería en Sistemas</option>
                  <option>Ingeniería Industrial</option>
                  <option>Administración</option>
                  <option>Contaduría</option>
                  <option>Arquitectura</option>
                  <option>Derecho</option>
                  <option>Diseño Gráfico</option>
                </select>
              </div>

              <div class="col-md-6 mt-2">
                <select v-model="form.semestre" class="form-select">
                  <option disabled value="">Selecciona semestre</option>
                  <option v-for="n in 7" :key="n">{{ n }}</option>
                </select>
              </div>

              <div class="col-md-6 mt-2">
                <input v-model="form.control" maxlength="8" class="form-control" placeholder="Número de control (8 dígitos)" />
              </div>

              <div class="col-md-6 mt-2">
                <input v-model="form.telefono" class="form-control" placeholder="Teléfono" @input="soloNumeros('telefono')" />
              </div>

              <div class="col-12 mt-2">
                <select v-model="form.club" class="form-select">
                  <option disabled value="">Selecciona club</option>
                  <option v-for="c in clubs" :key="c.nombre">{{ c.nombre }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" @click="saveRegistered">Guardar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm delete -->
    <div class="modal fade" id="confirmDeleteRegistered" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>¿Seguro que desea eliminar este alumno?</p>
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
  name: 'AlumnosR',
  props: ['alumnos', 'clubs'],
  data() {
    return {
      form: { nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '' },
      pendingDeleteIndex: null
    };
  },
  methods: {
    openAddModal() {
      this.form = { nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '' };
      new bootstrap.Modal(document.getElementById('modalAddRegistered')).show();
    },
    saveRegistered() {
      if (!this.form.nombre || !/^\d{8}$/.test(this.form.control) || !/^\d+$/.test(this.form.telefono) || !this.form.club) {
        return this.$root.showError ? this.$root.showError('Verifique los campos (control 8 dígitos, teléfono numérico, club obligatorio)') : null;
      }
      this.$emit('add-alumno', { ...this.form }, 'Usuario Oficina');
      bootstrap.Modal.getInstance(document.getElementById('modalAddRegistered')).hide();
      this.form = { nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '' };
    },
    soloNumeros(campo) {
      this.form[campo] = this.form[campo].replace(/\D/g, '');
    },
    confirmDelete(index) {
      this.pendingDeleteIndex = index;
      new bootstrap.Modal(document.getElementById('confirmDeleteRegistered')).show();
    },
    deleteConfirmed() {
      this.$emit('delete-alumno', this.pendingDeleteIndex, 'Usuario Oficina');
      bootstrap.Modal.getInstance(document.getElementById('confirmDeleteRegistered')).hide();
    }
  }
};
</script>
