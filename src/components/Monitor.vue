<template>
  <div class="d-flex flex-column bg-light vh-100 p-4">
    <!-- Información del monitor con foto y botón cerrar sesión -->
    <div class="card shadow-sm p-3 mb-3 bg-white">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <img
            :src="monitor.foto"
            alt="Foto monitor"
            class="rounded-circle me-3"
            width="80"
            height="80"
          />
          <div>
            <h4 class="text-primary mb-1">Registro de Asistencias</h4>
            <p class="mb-0"><strong>Usuario:</strong> {{ monitor.nombre }}</p>
            <p class="mb-0"><strong>Club asignado:</strong> {{ monitor.club }}</p>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm" @click="guardarStateLocal">
            Guardar
          </button>
          <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#logoutModal">
            Cerrar sesión
          </button>
        </div>
      </div>
    </div>

    <!-- Tabla de asistencias y selector de fecha -->
    <div class="card shadow-sm p-3 flex-grow-1">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="text-secondary m-0">Asistencias del Club</h5>

        <!-- Selector calendario para agregar nueva fecha -->
        <div class="input-group w-auto">
          <input
            v-model="nuevaFecha"
            type="date"
            class="form-control form-control-sm"
            aria-label="Nueva fecha"
          />
          <button class="btn btn-sm btn-outline-primary" @click="agregarFecha">
            Agregar
          </button>
        </div>
      </div>

      <div v-if="alumnosClub.length">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-primary">
              <tr>
                <th>Nombre</th>
                <th v-for="fecha in fechas" :key="fecha" class="text-center">{{ fecha }}</th>
                <th class="text-center">Acreditado</th>
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
                <td class="text-center">
                  <span class="badge" :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'">
                    {{ alumno.faltas < 3 ? 'Acreditado' : 'No acreditado' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="text-center text-muted py-5">
        No hay alumnos registrados en este club.
      </div>
    </div>

    <!-- Modal confirmación logout -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Cerrar sesión</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <p>¿Desea cerrar sesión y limpiar los datos locales?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="confirmLogout">Cerrar sesión</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toasts (success / error) -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:2000">
      <div class="toast align-items-center text-bg-success border-0" id="toastSuccess" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">{{ toastMsg }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
      </div>

      <div class="toast align-items-center text-bg-danger border-0 mt-2" id="toastError" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">{{ toastErr }}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "Monitor",
  props: ["clubs", "alumnos", "fechas"],
  data() {
    return {
      // monitor local (imagen incluida)
      monitor: {
        nombre: "Carlos Pérez",
        club: "Club de Robótica",
        foto: "https://cdn-icons-png.flaticon.com/512/2922/2922510.png",
      },
      nuevaFecha: "",
      toastMsg: "",
      toastErr: ""
    };
  },
  computed: {
    alumnosClub() {
      // asegurarse de devolver copias reactivas que tienen asistencias definidas
      return this.alumnos
        .filter(a => a.club === this.monitor.club)
        .map(a => {
          // si asistencias undefined, inicializar con las fechas actuales
          if (!a.asistencias) {
            this.$set(a, "asistencias", {});
            this.fechas.forEach(f => {
              if (!(f in a.asistencias)) this.$set(a.asistencias, f, false);
            });
          } else {
            // garantizar que para cada fecha exista la propiedad
            this.fechas.forEach(f => {
              if (!(f in a.asistencias)) this.$set(a.asistencias, f, false);
            });
          }
          if (typeof a.faltas !== "number") a.faltas = Object.values(a.asistencias).filter(v => v === false).length;
          return a;
        });
    },
  },
  mounted() {
    // cargar fechas/alumnos desde localStorage si existen (persistencia mínima)
    const savedFechas = localStorage.getItem("fechas_monitor");
    const savedAlumnos = localStorage.getItem("alumnos_monitor");
    if (savedFechas) {
      try { 
        const parsed = JSON.parse(savedFechas);
        // empujar solo las que no están (evita duplicados)
        parsed.forEach(f => { if (!this.fechas.includes(f)) this.fechas.push(f); });
      } catch(e) {}
    }
    if (savedAlumnos) {
      try {
        const parsed = JSON.parse(savedAlumnos);
        // sincronizar asistencias por nombre-control (simple merge)
        parsed.forEach(saved => {
          const local = this.alumnos.find(a => a.control === saved.control);
          if (local) {
            local.asistencias = { ...local.asistencias, ...(saved.asistencias || {}) };
            local.faltas = Object.values(local.asistencias).filter(v => v === false).length;
          }
        });
      } catch(e) {}
    }
  },
  methods: {
    actualizarFaltas(alumno) {
      // recalcula faltas: contar false en asistencias
      const totalFaltas = Object.values(alumno.asistencias || {}).filter(v => v === false).length;
      this.$set(alumno, "faltas", totalFaltas);
      this.showSuccess("Asistencia actualizada");
      // opcional: guardar cambios en localStorage
      this.guardarStateLocal();
    },

    agregarFecha() {
      if (!this.nuevaFecha) {
        this.showError("Seleccione una fecha antes de agregar.");
        return;
      }

      // formateo dd/mm/aaaa en es-MX
      const fechaFormateada = new Date(this.nuevaFecha).toLocaleDateString("es-MX");

      if (this.fechas.includes(fechaFormateada)) {
        this.showError("Esa fecha ya existe.");
        this.nuevaFecha = "";
        return;
      }

      // agregar la fecha
      this.fechas.push(fechaFormateada);

      // inicializar asistencias (false) para todos los alumnos del club
      this.alumnos.forEach(alumno => {
        if (alumno.club === this.monitor.club) {
          if (!alumno.asistencias) this.$set(alumno, "asistencias", {});
          this.$set(alumno.asistencias, fechaFormateada, false);
          // recalcular faltas por seguridad
          this.$set(alumno, "faltas", Object.values(alumno.asistencias).filter(v => v === false).length);
        }
      });

      this.nuevaFecha = "";
      this.showSuccess("Fecha agregada correctamente");
      this.guardarStateLocal();
    },

    guardarStateLocal() {
      // Guardar fechas y asistencias básicas (persistencia simple)
      try {
        localStorage.setItem("fechas_monitor", JSON.stringify(this.fechas));
        // guardar solo alumnos con control, para no serializar referencias complejas
        const toSave = this.alumnos.map(a => ({
          control: a.control,
          asistencias: a.asistencias || {}
        }));
        localStorage.setItem("alumnos_monitor", JSON.stringify(toSave));
        this.showSuccess("Datos guardados localmente");
      } catch (e) {
        this.showError("Error al guardar datos localmente");
      }
    },

    // logout: cerrar modal y limpiar almacenamiento
    confirmLogout() {
      // cerrar modal manualmente
      const modalEl = document.getElementById("logoutModal");
      const m = bootstrap.Modal.getInstance(modalEl);
      if (m) m.hide();

      // limpiar localStorage (solo keys usadas aquí)
      localStorage.removeItem("fechas_monitor");
      localStorage.removeItem("alumnos_monitor");

      // opcional: limpiar monitor data local
      this.monitor = { nombre: "", club: "", foto: "" };

      this.showSuccess("Sesión cerrada. Redirigiendo...");
      // redirigir al login después de breve retardo para que el toast se vea
      setTimeout(() => {
        this.$router.push("/");
      }, 700);
    },

    // toasts con Bootstrap
    showSuccess(msg) {
      this.toastMsg = msg;
      const tEl = document.getElementById("toastSuccess");
      const t = new bootstrap.Toast(tEl, { delay: 2500 });
      t.show();
    },

    showError(msg) {
      this.toastErr = msg;
      const tEl = document.getElementById("toastError");
      const t = new bootstrap.Toast(tEl, { delay: 3000 });
      t.show();
    }
  }
};
</script>

<style scoped>
.card {
  border-radius: 12px;
}
.table {
  font-size: 0.95rem;
}
</style>
