<template>
  <div>
    <h3>Reinscripciones</h3>

    <div class="mb-3 d-flex gap-2 align-items-center">
      <button class="btn btn-primary" @click="actualizarClubs">Actualizar clubs</button>
      <button class="btn btn-outline-secondary" @click="toggleSelectAllClubs">{{ allClubsSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}</button>
      <small class="text-muted ms-2">Selecciona los clubs. Haz clic en el club para ver sus alumnos.</small>
    </div>

    <div v-if="!clubs || clubs.length === 0" class="text-muted">No hay clubs disponibles.</div>

    <div v-else class="list-group">
      <div v-for="c in clubs" :key="c.id" class="list-group-item d-flex justify-content-between align-items-start">
        <div style="flex:1; cursor:pointer" @click="loadAlumnosClub(c.id)">
          <div><input type="checkbox" :value="c.id" v-model="selectedClubs" class="form-check-input me-2" /> <strong>{{ c.nombre }}</strong></div>
          <div class="small text-muted">Tipo: {{ c.tipo || 'CULTURAL' }} - Cupo: {{ c.cupo }} - Ocupados: {{ c.ocupados }}</div>
        </div>
          <div>
            <button class="btn btn-sm btn-outline-primary" @click.prevent="$emit('open-club', c.id)">Ver alumnos</button>
          </div>
      </div>
    </div>

    <div v-if="currentClubId" class="mt-4">
      <h5>Alumnos en el club</h5>

      <div class="mb-2 d-flex gap-2">
        <button class="btn btn-sm btn-danger" :disabled="!selectedStudents.length" @click="confirmAction('batchBaja', { ids: selectedStudents.slice() })">Dar de baja seleccionados</button>
        <button class="btn btn-sm btn-success" :disabled="!selectedStudents.length" @click="confirmAction('batchInscribir', { ids: selectedStudents.slice() })">Inscribir y acreditar seleccionados</button>
        <button class="btn btn-sm btn-outline-secondary" @click="toggleSelectAllStudents">{{ allStudentsSelected ? 'Deseleccionar todos' : 'Seleccionar todos' }}</button>
        <div class="ms-auto small text-muted">Fechas: {{ fechas.length }}</div>
      </div>

      <div v-if="loading" class="text-muted">Cargando alumnos...</div>
      <div v-else>
        <div v-if="alumnosClub.length === 0" class="text-muted">No hay alumnos inscritos en este club.</div>
        <table v-else class="table table-sm">
          <thead>
            <tr>
              <th></th>
              <th>Nombre</th>
              <th>Control</th>
              <th>Teléfono</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="al in alumnosClub" :key="al.id">
              <td><input type="checkbox" :value="al.id" v-model="selectedStudents" class="form-check-input" /></td>
              <td>{{ al.nombre }} {{ al.apellidoP }} {{ al.apellidoM }}</td>
              <td>{{ al.numeroControl || al.control || '' }}</td>
              <td>{{ al.telefono || '' }}</td>
              <td>
                <button class="btn btn-sm btn-danger me-2" @click="confirmAction('baja', { id: al.id })">Dar de baja</button>
                <button class="btn btn-sm btn-success" @click="confirmAction('inscribir', { id: al.id })">Inscribir y acreditar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Confirm modal -->
    <div class="modal fade" id="confirmReinscripcion" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Confirmar acción</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p v-if="pendingAction">{{ pendingMessage }}</p>
            <p v-else class="text-muted">No hay acción pendiente.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" @click="doPendingAction">Confirmar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getAsistenciasPorClub, batchReinscripciones } from '../services/api';

export default {
  name: 'Reinscripciones',
  props: { clubs: { type: Array, default: () => [] } },
  data() {
    return {
      selectedClubs: [],
      currentClubId: null,
      alumnosClub: [],
      fechas: [],
      asistencias: {},
      loading: false,
      selectedStudents: [],
      pendingAction: null,
    };
  },
  computed: {
    allClubsSelected() {
      return this.clubs && this.clubs.length > 0 && this.selectedClubs.length === this.clubs.length;
    },
    allStudentsSelected() {
      return this.alumnosClub && this.alumnosClub.length > 0 && this.selectedStudents.length === this.alumnosClub.length;
    },
    pendingMessage() {
      if (!this.pendingAction) return '';
      const t = this.pendingAction.type;
      if (t === 'baja') return `Dar de baja al alumno ID ${this.pendingAction.payload.id}?`;
      if (t === 'inscribir') return `Inscribir y acreditar al alumno ID ${this.pendingAction.payload.id}?`;
      if (t === 'batchBaja') return `Dar de baja a ${this.pendingAction.payload.ids.length} alumnos?`;
      if (t === 'batchInscribir') return `Inscribir y acreditar a ${this.pendingAction.payload.ids.length} alumnos?`;
      return 'Confirmar acción?';
    }
  },
  methods: {
    actualizarClubs() {
      this.$emit('actualizar-clubs', this.selectedClubs.slice());
      this.$emit('request-reload-clubs');
    },
    toggleSelectAllClubs() {
      if (this.allClubsSelected) this.selectedClubs = [];
      else this.selectedClubs = this.clubs.map(c => c.id);
    },
    async loadAlumnosClub(clubId) {
      this.currentClubId = clubId;
      this.loading = true;
      this.selectedStudents = [];
      try {
        const data = await getAsistenciasPorClub(clubId);
        this.fechas = data.fechas || [];
        this.asistencias = data.asistencias || {};
        this.alumnosClub = Array.isArray(data.alumnos) ? data.alumnos : [];
      } catch (e) {
        this.$emit('notify-error', { message: e.message || e });
        this.alumnosClub = [];
      } finally {
        this.loading = false;
      }
    },
    toggleSelectAllStudents() {
      if (this.allStudentsSelected) this.selectedStudents = [];
      else this.selectedStudents = this.alumnosClub.map(a => a.id);
    },
    confirmAction(type, payload) {
      this.pendingAction = { type, payload };
      const modal = new bootstrap.Modal(document.getElementById('confirmReinscripcion'));
      modal.show();
    },
    async doPendingAction() {
      const act = this.pendingAction;
      if (!act) return;
      const tipo = act.type;
      try {
        if (tipo === 'baja') {
          const aid = act.payload.id;
          await batchReinscripciones({ tipo: 'baja', alumnos: [aid] });
          this.alumnosClub = this.alumnosClub.filter(a => a.id !== aid);
          this.$emit('request-reload-alumnos');
          this.$emit('request-reload-clubs');
          this.$emit('notify', { type: 'success', message: 'Alumno dado de baja correctamente.' });
        } else if (tipo === 'inscribir') {
          const aid = act.payload.id;
          await batchReinscripciones({ tipo: 'inscribir_acreditar', alumnos: [aid], club_id: this.currentClubId, fechas: this.fechas });
          this.$emit('request-reload-alumnos');
          this.$emit('request-reload-clubs');
          this.$emit('notify', { type: 'success', message: 'Alumno inscrito y acreditado correctamente.' });
        } else if (tipo === 'batchBaja') {
          const ids = act.payload.ids || [];
          await batchReinscripciones({ tipo: 'baja', alumnos: ids });
          this.alumnosClub = this.alumnosClub.filter(a => !ids.includes(a.id));
          this.$emit('request-reload-alumnos');
          this.$emit('request-reload-clubs');
          this.$emit('notify', { type: 'success', message: `Se dieron de baja ${ids.length} alumnos.` });
        } else if (tipo === 'batchInscribir') {
          const ids = act.payload.ids || [];
          await batchReinscripciones({ tipo: 'inscribir_acreditar', alumnos: ids, club_id: this.currentClubId, fechas: this.fechas });
          this.$emit('request-reload-alumnos');
          this.$emit('request-reload-clubs');
          this.$emit('notify', { type: 'success', message: `Se inscribieron y acreditaron ${ids.length} alumnos.` });
        }
      } catch (e) {
        this.$emit('notify-error', { message: e.message || e });
      } finally {
        this.pendingAction = null;
        const modalEl = document.getElementById('confirmReinscripcion');
        const mdl = bootstrap.Modal.getInstance(modalEl);
        if (mdl) mdl.hide();
      }
    }
  }
};
</script>

<style scoped>
.list-group-item { display: flex; gap: 1rem; }
</style>
