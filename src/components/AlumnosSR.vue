<template>
  <div>
    <h3>Alumnos sin registrar (formularios)</h3>

    <div class="d-flex justify-content-between align-items-center my-3">
      <div>
        <button class="btn btn-primary me-2" @click="simularImport">Importar (simulación Google Forms)</button>
        <button class="btn btn-success" @click="openAddModal">Agregar manual</button>
      </div>
      <div>
        <small class="text-muted">Los registros muestran 3 opciones de club propuestas.</small>
      </div>
    </div>

    <div v-if="unregistered.length === 0" class="text-muted">No hay alumnos sin registrar.</div>

    <table v-if="unregistered.length" class="table table-bordered table-striped align-middle">
      <thead class="table-primary">
        <tr>
          <th>Nombre</th>
          <th>No. Control</th>
          <th>Teléfono</th>
          <th>Opciones de club</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(u, idx) in unregistered" :key="u.control + idx">
          <td>{{ u.nombre }} {{ u.apellidoP }} {{ u.apellidoM }}</td>
          <td>{{ u.control }}</td>
          <td>{{ u.telefono }}</td>
          <td>
            <div class="d-flex gap-2">
              <button v-for="(opt, oIndex) in u.opciones" :key="oIndex" class="btn btn-outline-secondary btn-sm" disabled>{{ opt }}</button>
            </div>
          </td>
          <td>
            <div class="d-flex gap-2">
              <button class="btn btn-success btn-sm" @click="assignToClub(idx, u.opciones[0])">Asignar 1</button>
              <button class="btn btn-success btn-sm" @click="assignToClub(idx, u.opciones[1])">Asignar 2</button>
              <button class="btn btn-success btn-sm" @click="assignToClub(idx, u.opciones[2])">Asignar 3</button>
              <button class="btn btn-danger btn-sm" @click="removeUnregistered(idx)">Eliminar</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal agregar alumno sin registrar -->
    <div class="modal fade" id="modalAddSR" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Agregar alumno (sin registrar)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-4"><input v-model="form.nombre" class="form-control" placeholder="Nombre(s)" /></div>
              <div class="col-md-4"><input v-model="form.apellidoP" class="form-control" placeholder="Apellido paterno" /></div>
              <div class="col-md-4"><input v-model="form.apellidoM" class="form-control" placeholder="Apellido materno" /></div>
              <div class="col-md-4 mt-2"><input v-model="form.control" maxlength="8" class="form-control" placeholder="Número de control (8 dígitos)" /></div>
              <div class="col-md-4 mt-2"><input v-model="form.telefono" class="form-control" placeholder="Teléfono" @input="soloNumeros('telefono')" /></div>
              <div class="col-md-4 mt-2">
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

              <!-- Opciones de clubs (3) -->
              <div class="col-12 mt-2">
                <label class="form-label">Opciones de club (3)</label>
                <div class="row g-2">
                  <div class="col-md-4"><select v-model="form.opciones[0]" class="form-select"><option disabled value="">Opción 1</option><option v-for="c in clubs" :key="c.nombre">{{ c.nombre }}</option></select></div>
                  <div class="col-md-4"><select v-model="form.opciones[1]" class="form-select"><option disabled value="">Opción 2</option><option v-for="c in clubs" :key="c.nombre+'2'">{{ c.nombre }}</option></select></div>
                  <div class="col-md-4"><select v-model="form.opciones[2]" class="form-select"><option disabled value="">Opción 3</option><option v-for="c in clubs" :key="c.nombre+'3'">{{ c.nombre }}</option></select></div>
                </div>
              </div>

            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" @click="saveUnregistered">Guardar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
/*
 AlumnosSR: maneja la lista de "sin registrar" que provendría de formularios externos.
 - Emite assign-alumno al padre cuando se asigna.
 - Emite import-unregistered si se importa una lista externa.
 - para la simulación se creará algunos registros de ejemplo.
*/
export default {
  name: 'AlumnosSR',
  props: ['clubs'],
  data() {
    return {
      unregistered: [],

      // formulario local para agregar manualmente
      form: {
        nombre: '',
        apellidoP: '',
        apellidoM: '',
        control: '',
        telefono: '',
        carrera: '',
        opciones: ['', '', '']
      }
    };
  },
  methods: {
    // simula import desde Google Forms (ejemplo)
    simularImport() {
      const sample = [
        { nombre: 'Pedro', apellidoP: 'Gómez', apellidoM: 'Sánchez', control: '20230001', telefono: '5512345678', carrera: 'Ingeniería en Sistemas', opciones: this.sampleOptions() },
        { nombre: 'Lucía', apellidoP: 'Méndez', apellidoM: 'Ríos', control: '20230002', telefono: '5598765432', carrera: 'Administración', opciones: this.sampleOptions() }
      ];
      this.unregistered.push(...sample);
      this.$emit('import-unregistered', this.unregistered);
      this.$emit('log', { usuario: 'Sistema', accion: 'Insertar', tipo: 'import', descripcion: `Importados ${sample.length} registros` });
      new bootstrap.Toast(document.getElementById('toastSuccess')).show();
    },

    sampleOptions() {
      // toma primeros 3 clubs disponibles o repite si hay menos
      const names = this.clubs.map(c => c.nombre);
      const out = [];
      for (let i=0;i<3;i++) out.push(names[i % Math.max(1,names.length)] || 'Sin club');
      return out;
    },

    openAddModal() {
      this.form = { nombre: '', apellidoP: '', apellidoM: '', control: '', telefono: '', carrera: '', opciones: ['', '', ''] };
      new bootstrap.Modal(document.getElementById('modalAddSR')).show();
    },

    saveUnregistered() {
      if (!this.form.nombre || !/^\d{8}$/.test(this.form.control) || !/^\d+$/.test(this.form.telefono) || !this.form.opciones[0]) {
        return this.$root.showError ? this.$root.showError('Verifique los campos (control 8 dígitos, teléfono numérico, al menos 1 opción de club)') : null;
      }
      this.unregistered.push({ ...this.form });
      this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Insertar', tipo: 'alumno_sin_registrar', descripcion: `Se agregó ${this.form.nombre}` });
      bootstrap.Modal.getInstance(document.getElementById('modalAddSR')).hide();
      new bootstrap.Toast(document.getElementById('toastSuccess')).show();
    },

    soloNumeros(campo) {
      this.form[campo] = this.form[campo].replace(/\D/g, '');
    },

    assignToClub(index, clubName) {
      // solicitar al padre la asignación; padre verificará cupo y lo agregará a registrados
      const alumno = this.unregistered[index];
      if (!clubName) return this.$root.showError('Club inválido');
      // primer, verificar cupo localmente
      this.$emit('assign-alumno', { alumnoIndex: index, clubNombre: clubName });
      // si el padre acepta la asignación, éste eliminará el registro de la lista "sin registrar".
      // Para coherencia local, esperar a que parent actualice via import-unregistered o usar evento de retorno.
      // Aquí, se intentará retirar localmente al asignar (padre también hará la operación definitiva).
      // Intentar eliminar local si el padre no mantiene copia:
      this.unregistered.splice(index, 1);
    },

    removeUnregistered(index) {
      this.unregistered.splice(index, 1);
      this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Eliminar', tipo: 'alumno_sin_registrar', descripcion: `Registro eliminado` });
      new bootstrap.Toast(document.getElementById('toastSuccess')).show();
    }
  }
};
</script>
