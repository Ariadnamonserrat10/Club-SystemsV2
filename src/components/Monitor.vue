<template>
  <div class="d-flex flex-column bg-light vh-100 p-4">
    <!-- PERFIL DEL MONITOR -->
    <div class="card shadow-sm p-3 mb-3 bg-white d-flex flex-row align-items-center">
      <img
        :src="usuarioActual.foto || 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
        alt="Foto del monitor"
        class="rounded-circle me-3"
        width="80"
        height="80"
        style="object-fit: cover;"
      />
      <div>
        <h4 class="text-primary mb-2">Registro de Asistencias</h4>
        <p><strong>Usuario:</strong> {{ usuarioActual.nombre }} {{ usuarioActual.apellidoP }}</p>
        <p>
          <strong>Club asignado:</strong>
          <span v-if="usuarioActual.club_nombre">{{ usuarioActual.club_nombre }}</span>
          <span v-else>- Ninguno -</span>
        </p>
      </div>
      <div class="ms-auto">
        <button class="btn btn-outline-danger btn-sm" @click="cerrarSesion">
          Cerrar sesión
        </button>
      </div>
    </div>

    <!-- ALERTAS -->
    <transition name="fade">
      <div
        v-if="mensaje.texto"
        :class="['alert', mensaje.tipo, 'position-fixed top-0 end-0 mt-3 me-3 shadow']"
        style="z-index:2000; min-width:280px"
        role="alert"
      >
        {{ mensaje.texto }}
      </div>
    </transition>

    <!-- CONTENEDOR DE ASISTENCIAS -->
    <div class="card shadow-sm p-3 flex-grow-1 overflow-auto" style="max-height: 70vh;">
      <h5 class="text-secondary mb-3">Asistencias del Club</h5>

      <!-- AGREGAR NUEVA FECHA -->
      <div class="mb-3">
        <label for="nuevaFecha" class="form-label">Agregar nueva fecha:</label>
        <div class="input-group">
          <input
            type="date"
            id="nuevaFecha"
            v-model="nuevaFecha"
            class="form-control"
          />
          <button class="btn btn-success" @click="agregarFecha">Agregar</button>
        </div>
      </div>

      <!-- TABLA DE ASISTENCIAS -->
      <div v-if="alumnosClub.length">
        <table class="table table-hover align-middle">
          <thead class="table-primary text-center">
            <tr>
              <th>Nombre</th>
              <th v-for="fecha in fechas" :key="fecha">{{ fecha }}</th>
              <th>Acreditado</th>
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
                <span
                  class="badge"
                  :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'"
                >
                  {{ alumno.faltas < 3 ? 'Acreditado' : 'No acreditado' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- BOTÓN GUARDAR CAMBIOS -->
        <div class="d-flex justify-content-end mt-3">
          <button class="btn btn-outline-primary" @click="guardarCambios">
            Guardar cambios
          </button>
        </div>
      </div>

      <div v-else class="text-center text-muted py-5">
        No hay alumnos registrados en este club.
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "Monitor",
  props: ["clubs", "alumnos", "fechas"],
  data() {
    return {
      usuarioActual: {
        nombre: "",
        apellidoP: "",
        tipo: "",
        foto: "https://cdn-icons-png.flaticon.com/512/847/847969.png", // fallback
        club_asignado: null,
        club_nombre: null,
      },
      monitor: { nombre: "Carlos Pérez", club: "Club de Robótica" }, // se reemplazará si BD trae club
      clubsList: [],          // lista de clubs desde BD
      selectedClubId: null,   // id seleccionado en el dropdown
      assigning: false,       // flag al asignar
      nuevaFecha: "",
      mensaje: { texto: "", tipo: "" },
    };
  },
  computed: {
    alumnosClub() {
      return this.alumnos
        .filter((a) => a.club === this.monitor.club)
        .map((a) => {
          if (!a.asistencias) a.asistencias = {};
          this.fechas.forEach((f) => {
            if (!(f in a.asistencias)) a.asistencias[f] = false;
          });
          a.faltas = Object.values(a.asistencias).filter((v) => v === false).length;
          return a;
        });
    },
  },
  methods: {
    async cargarUsuarioActual() {
      try {
        const usuarioId = sessionStorage.getItem("usuarioId");
        if (!usuarioId) return;

        const response = await axios.get(
          `http://localhost/Backend/obtenerUsuario.php?id=${usuarioId}`
        );

        if (response.data?.status === "success") {
          const datos = response.data.data;
          this.usuarioActual.nombre = datos.nombre || "";
          this.usuarioActual.apellidoP = datos.apellidoP || "";
          this.usuarioActual.tipo = datos.tipo || sessionStorage.getItem("usuarioTipo") || "";
          this.usuarioActual.foto = datos.foto || this.usuarioActual.foto;
          this.usuarioActual.club_asignado = datos.club_asignado ?? null;
          this.usuarioActual.club_nombre = datos.club_nombre ?? null;
          this.selectedClubId = this.usuarioActual.club_asignado;

          // Si la BD devuelve el club asignado al monitor, opcionalmente actualizarlo
          if (datos.club_nombre) this.monitor.club = datos.club_nombre;
        }
      } catch (error) {
        console.error("Error cargando usuario Monitor:", error);
      }
    },

    async cargarClubs() {
      try {
        const res = await axios.get("http://localhost/Backend/getClubs.php");
        if (res.data?.status === "success" && Array.isArray(res.data.data)) {
          this.clubsList = res.data.data;
        } else {
          console.warn("No se obtuvieron clubs:", res.data);
        }
      } catch (err) {
        console.error("Error cargando lista de clubs:", err);
      }
    },

    mostrarMensaje(texto, tipo) {
      this.mensaje.texto = texto;
      this.mensaje.tipo = tipo;
      setTimeout(() => (this.mensaje.texto = ""), 2500);
    },
    cerrarSesion() {
      this.mostrarMensaje("Sesión cerrada correctamente.", "alert-info");
      // pequeño retardo para mostrar el mensaje antes de redirigir
      setTimeout(() => {
        sessionStorage.clear();
        this.$router.push("/"); // regresar al login
      }, 1500);
    },
    agregarFecha() {
      if (!this.nuevaFecha) {
        this.mostrarMensaje("Seleccione una fecha antes de agregar.", "alert-warning");
        return;
      }

      const fechaFormateada = new Date(this.nuevaFecha).toLocaleDateString("es-MX", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });

      if (this.fechas.includes(fechaFormateada)) {
        this.mostrarMensaje("La fecha ya está registrada.", "alert-danger");
        return;
      }

      this.fechas.push(fechaFormateada);
      this.alumnosClub.forEach((alumno) => {
        alumno.asistencias[fechaFormateada] = false;
        alumno.faltas = Object.values(alumno.asistencias).filter((v) => v === false).length;
      });

      this.nuevaFecha = "";
      this.mostrarMensaje("Fecha agregada correctamente.", "alert-success");
    },
    actualizarFaltas(alumno) {
      alumno.faltas = Object.values(alumno.asistencias).filter((v) => v === false).length;
    },
    guardarCambios() {
      try {
        localStorage.setItem("fechas_monitor", JSON.stringify(this.fechas));
        localStorage.setItem("alumnos_monitor", JSON.stringify(this.alumnos));
        this.mostrarMensaje("Cambios guardados correctamente.", "alert-success");
      } catch (error) {
        this.mostrarMensaje("Error al guardar los datos.", "alert-danger");
      }
    },
    async asignarClub() {
      if (!this.selectedClubId) return;
      this.assigning = true;
      try {
        const usuarioId = sessionStorage.getItem("usuarioId");
        const payload = { usuarioId: Number(usuarioId), club_id: Number(this.selectedClubId) };
        const res = await axios.post("http://localhost/Backend/asignarClub.php", payload, {
          headers: { "Content-Type": "application/json" }
        });

        if (res.data?.status === "success") {
          // actualizar vista local
          const club = this.clubsList.find(c => c.id === Number(this.selectedClubId));
          this.usuarioActual.club_asignado = club ? club.id : this.selectedClubId;
          this.usuarioActual.club_nombre = club ? club.nombre : `Club ID ${this.selectedClubId}`;
          this.monitor.club = this.usuarioActual.club_nombre;
          this.mensaje = { texto: "Club asignado correctamente.", tipo: "success" };
        } else {
          this.mensaje = { texto: res.data.message || "Error al asignar club.", tipo: "error" };
        }
      } catch (err) {
        console.error("Error asignando club:", err);
        this.mensaje = { texto: "Error de red al asignar club.", tipo: "error" };
      } finally {
        this.assigning = false;
        setTimeout(() => (this.mensaje.texto = ""), 2500);
      }
    },
  },
  async mounted() {
    await this.cargarUsuarioActual();
    // await this.cargarClubs();
    // ...existing mounted logic (si lo hay)...
  },
};
</script>

<style scoped>
.card {
  border-radius: 12px;
}
.table {
  font-size: 0.95rem;
}
img {
  object-fit: cover;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}
</style>
