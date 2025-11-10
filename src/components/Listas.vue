<template>
  <div>
    <h4 class="text-primary mb-3">Listas de Clubs</h4>

    <div class="mb-3">
      <label class="form-label">Selecciona un club:</label>
      <select v-model="clubSeleccionado" class="form-select">
        <option disabled value="">-- Seleccionar --</option>
        <option v-for="(club, i) in clubs" :key="i" :value="club.nombre">
          {{ club.nombre }}
        </option>
      </select>
    </div>

    <div v-if="clubSeleccionado">
      <h5 class="mt-3 text-secondary">Alumnos del {{ clubSeleccionado }}</h5>
      <table class="table table-bordered table-hover mt-3">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
            <th v-for="fecha in fechas" :key="fecha">{{ fecha }}</th>
            <th>Acreditado</th>
            <th>Constancia</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(alumno, index) in alumnosClub" :key="index">
            <td>{{ alumno.nombre }} {{ alumno.apellidoP }}</td>
            <td v-for="fecha in fechas" :key="fecha" class="text-center">
              <input
                type="checkbox"
                v-model="alumno.asistencias[fecha]"
                @change="actualizarFaltas(alumno)"
              />
            </td>
            <td>
              <span
                class="badge"
                :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'"
              >
                {{ alumno.faltas < 3 ? 'Acreditado' : 'No acreditado' }}
              </span>
            </td>
            <td>
              <button
                class="btn btn-sm"
                :class="alumno.faltas < 3 ? 'btn-outline-success' : 'btn-outline-secondary'"
                :disabled="alumno.faltas >= 3"
                @click="expedirConstancia(alumno)"
              >
                Expedir
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-end mt-3">
        <button class="btn btn-primary" @click="expedirTodas">
          Expedir todas las constancias
        </button>
      </div>
    </div>

    <div v-else class="text-muted mt-4 text-center">
      Selecciona un club para mostrar su lista.
    </div>
  </div>
</template>

<script>
export default {
  name: "Listas",
  props: ["clubs", "alumnos", "fechas"],
  data() {
    return {
      clubSeleccionado: "",
    };
  },
  computed: {
    alumnosClub() {
      return this.alumnos.filter(a => a.club === this.clubSeleccionado);
    },
  },
  methods: {
    actualizarFaltas(alumno) {
      const totalFaltas = Object.values(alumno.asistencias).filter(v => v === false).length;
      alumno.faltas = totalFaltas;
    },
    expedirConstancia(alumno) {
      alert(`Constancia expedida para ${alumno.nombre} ${alumno.apellidoP}`);
    },
    expedirTodas() {
      alert("Se expidieron todas las constancias disponibles.");
    },
  },
};
</script>

<style scoped>
.table {
  font-size: 0.95rem;
}
</style>
