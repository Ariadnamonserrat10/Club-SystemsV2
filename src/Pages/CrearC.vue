<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

const router = useRouter();

const userType = ref("oficina");
const showSuccessModal = ref(false);
const alertError = ref("");
const alertInfo = ref(true);
const registeredUser = ref(null);

const form = ref({
  nombre: "",
  apellidoP: "",
  apellidoM: "",
  numeroControl: "",
  telefono: "",
  carrera: "",
  semestre: "",
  usuario: "",
  password: "",
  confirmPassword: "",
  foto: null,
});

const carreras = [
  "Ingeniería Civil",
  "Ingeniería Industrial",
  "Ingeniería en Sistemas Computacionales",
  "Ingeniería en Gestión Empresarial",
  "Licenciatura en Administración",
  "Licenciatura en Arquitectura",
  "Ingeniería en Mecatrónica",
];

const selectUserType = (type) => {
  userType.value = type;
  alertInfo.value = type === "oficina";
  alertError.value = "";
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.value.foto = URL.createObjectURL(file);
  }
};

// 🔹 Conexión con PHP y MySQL (corregida y completa)
const handleRegister = async () => {
  alertError.value = "";

  if (form.value.password !== form.value.confirmPassword) {
    alertError.value = "Las contraseñas no coinciden";
    return;
  }

  if (form.value.password.length < 8) {
    alertError.value = "La contraseña debe tener al menos 8 caracteres";
    return;
  }

  if (userType.value === "monitor") {
    if (
      !form.value.numeroControl ||
      !form.value.carrera ||
      !form.value.semestre ||
      !form.value.telefono
    ) {
      alertError.value = "Completa todos los campos requeridos para Monitor";
      return;
    }
  }

  try {
    const payload = {
      nombre: form.value.nombre,
      apellidoP: form.value.apellidoP,
      apellidoM: form.value.apellidoM,
      usuario: form.value.usuario,
      password: form.value.password,
      tipo: userType.value === "oficina" ? "OFICINA" : "MONITOR"
    };

    if (userType.value === "monitor") {
      payload.numeroControl = form.value.numeroControl;
      payload.telefono = form.value.telefono || "";
      // Si más adelante manejas IDs numéricos para carrera/semestre en BD, asigna aquí
      // payload.carrera_id = Number(form.value.carrera_id) || null;
      // payload.semestre_id = Number(form.value.semestre_id) || null;
    }

    const response = await axios.post(
      "http://localhost/Backend/Registrar.php",
      payload,
      { headers: { "Content-Type": "application/json" } }
    );

    if (response.data.status === "success") {
      registeredUser.value = {
        nombre: `${form.value.nombre} ${form.value.apellidoP} ${form.value.apellidoM}`,
        tipo: userType.value.toUpperCase(),
        usuario: form.value.usuario,
        numeroControl: form.value.numeroControl || "N/A",
        carrera: form.value.carrera || "N/A",
        telefono: form.value.telefono || "N/A",
        foto: form.value.foto || null,
      };
      showSuccessModal.value = true;
    } else {
      alertError.value =
        response.data.message || "Error al registrar el usuario.";
    }
  } catch (error) {
    console.error("Axios error completo:", error);
    if (error.response) {
      console.error("Response status:", error.response.status);
      console.error("Response data:", error.response.data);
      alertError.value =
        error.response.data?.message ||
        error.response.data?.error ||
        JSON.stringify(error.response.data) ||
        `Error del servidor: ${error.response.status}`;
    } else if (error.request) {
      console.error("No hubo respuesta. Request:", error.request);
      alertError.value =
        "No hubo respuesta del servidor (posible problema de red o URL incorrecta).";
    } else {
      console.error("Error al preparar la petición:", error.message);
      alertError.value = `Error: ${error.message}`;
    }
  }
};

const resetForm = () => {
  form.value = {
    nombre: "",
    apellidoP: "",
    apellidoM: "",
    numeroControl: "",
    telefono: "",
    carrera: "",
    semestre: "",
    usuario: "",
    password: "",
    confirmPassword: "",
    foto: null,
  };
  userType.value = "oficina";
  alertError.value = "";
  alertInfo.value = true;
};

const closeModalAndReset = () => {
  showSuccessModal.value = false;
  resetForm();
};

const goToLogin = () => {
  showSuccessModal.value = false;
  router.push("/");
};
</script>

<template>
  <div class="registro-container">
    <div
      class="card shadow border-0 rounded-4 bg-light p-3 w-100"
      style="max-width: 700px"
    >
      <div class="text-center text-white bg-primary py-3 rounded-3 mb-2">
        <i class="bi bi-person-plus-fill fs-1"></i>
        <h3 class="mt-2 mb-0">Registro de Usuario</h3>
      </div>

      <!-- Alertas -->
      <div
        v-if="alertInfo"
        class="alert alert-info alert-dismissible fade show py-2 mb-2"
      >
        <i class="bi bi-info-circle-fill me-2"></i>
        Los usuarios de OFICINA no requieren número de control, carrera ni
        semestre.
        <button
          type="button"
          class="btn-close"
          @click="alertInfo = false"
        ></button>
      </div>
      <div
        v-if="alertError"
        class="alert alert-danger alert-dismissible fade show py-2 mb-2"
      >
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ alertError }}
        <button
          type="button"
          class="btn-close"
          @click="alertError = ''"
        ></button>
      </div>

      <form @submit.prevent="handleRegister" class="small">
        <!-- Tipo de Usuario -->
        <div class="mb-3 text-center">
          <label class="form-label fw-semibold">Tipo de Usuario</label>
          <div class="d-flex justify-content-center gap-2">
            <button
              type="button"
              class="btn btn-sm"
              :class="
                userType === 'oficina' ? 'btn-primary' : 'btn-outline-primary'
              "
              @click="selectUserType('oficina')"
            >
              <i class="bi bi-building me-1"></i> Oficina
            </button>
            <button
              type="button"
              class="btn btn-sm"
              :class="
                userType === 'monitor' ? 'btn-primary' : 'btn-outline-primary'
              "
              @click="selectUserType('monitor')"
            >
              <i class="bi bi-person-badge me-1"></i> Monitor
            </button>
          </div>
        </div>

        <!-- Imagen -->
        <div class="text-center mb-3">
          <img
            v-if="form.foto"
            :src="form.foto"
            alt="Foto de perfil"
            class="rounded-circle shadow-sm mb-2 border"
            width="100"
            height="100"
          />
          <div v-else class="placeholder-img mx-auto mb-2"></div>
          <input
            type="file"
            class="form-control w-auto mx-auto"
            accept="image/*"
            @change="handleImageUpload"
          />
        </div>

        <!-- Datos personales -->
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <input
              v-model="form.nombre"
              class="form-control"
              placeholder="Nombre"
              required
            />
          </div>
          <div class="col-md-4">
            <input
              v-model="form.apellidoP"
              class="form-control"
              placeholder="Apellido paterno"
              required
            />
          </div>
          <div class="col-md-4">
            <input
              v-model="form.apellidoM"
              class="form-control"
              placeholder="Apellido materno"
              required
            />
          </div>
        </div>

        <!-- Datos académicos -->
        <div v-if="userType === 'monitor'" class="row g-2 mb-2">
          <div class="col-md-3">
            <input
              v-model="form.numeroControl"
              class="form-control"
              placeholder="No. Control (8 dígitos)"
              pattern="[0-9]{8}"
              required
            />
          </div>
          <div class="col-md-3">
            <input
              v-model="form.telefono"
              class="form-control"
              placeholder="Teléfono (solo números)"
              pattern="[0-9]{10}"
              required
            />
          </div>
          <div class="col-md-3">
            <select v-model="form.carrera" class="form-select" required>
              <option value="">Carrera</option>
              <option v-for="c in carreras" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="form.semestre" class="form-select" required>
              <option value="">Semestre</option>
              <option v-for="n in 7" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
        </div>

        <!-- Datos de acceso -->
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <input
              v-model="form.usuario"
              class="form-control"
              placeholder="Usuario"
              required
            />
          </div>
          <div class="col-md-4">
            <input
              v-model="form.password"
              type="password"
              class="form-control"
              placeholder="Contraseña"
              minlength="8"
              required
            />
          </div>
          <div class="col-md-4">
            <input
              v-model="form.confirmPassword"
              type="password"
              class="form-control"
              placeholder="Confirmar contraseña"
              minlength="8"
              required
            />
          </div>
        </div>

        <!-- Botones -->
        <div class="d-flex justify-content-between mt-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm px-3"
            @click="resetForm"
          >
            <i class="bi bi-arrow-clockwise me-1"></i> Limpiar
          </button>
          <button type="submit" class="btn btn-success btn-sm px-4">
            <i class="bi bi-check-circle me-1"></i> Registrar
          </button>
        </div>

        <div class="text-center mt-2">
          <a
            href="#"
            class="text-decoration-none fw-semibold small"
            @click.prevent="router.push('/')"
          >
            <i class="bi bi-arrow-left me-1"></i> Volver al login
          </a>
        </div>
      </form>
    </div>

    <!-- Modal de Éxito -->
    <div v-if="showSuccessModal" class="modal-backdrop fade show"></div>
    <div
      v-if="showSuccessModal"
      class="modal d-block"
      tabindex="-1"
      @click.self="closeModalAndReset"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-check-circle-fill me-2"></i>Registro Exitoso
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              @click="closeModalAndReset"
            ></button>
          </div>
          <div class="modal-body text-center">
            <div v-if="registeredUser.foto" class="mb-3">
              <img
                :src="registeredUser.foto"
                alt="Foto de usuario"
                class="rounded-circle border"
                width="100"
                height="100"
              />
            </div>
            <h5 class="fw-bold">{{ registeredUser.nombre }}</h5>
            <p class="mb-0">
              <span
                class="badge"
                :class="
                  registeredUser.tipo === 'OFICINA' ? 'bg-primary' : 'bg-info'
                "
              >
                {{ registeredUser.tipo }}
              </span>
            </p>
            <p class="mt-2">Usuario registrado correctamente.</p>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="closeModalAndReset"
            >
              Registrar Otro
            </button>
            <button type="button" class="btn btn-primary" @click="goToLogin">
              Ir al Login
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
html,
body {
  height: 100%;
  margin: 0;
  padding: 0;
  overflow-y: auto;
}

.registro-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 0.5rem;
  background: linear-gradient(135deg, #2d3561, #5865a8);
}

.card {
  width: 100%;
  margin-bottom: 1rem;
  padding: 1rem;
}

.placeholder-img {
  width: 100px;
  height: 100px;
  background-color: #e9ecef;
  border-radius: 50%;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}

.modal {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
</style>
