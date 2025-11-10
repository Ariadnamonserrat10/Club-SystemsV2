<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const userType = ref('oficina')
const showSuccessModal = ref(false)
const alertError = ref('')
const registeredUser = ref(null)

const form = ref({
  nombre: '',
  apellidoP: '',
  apellidoM: '',
  numeroControl: '',
  carrera: '',
  semestre: '',
  usuario: '',
  password: '',
  confirmPassword: ''
})

const carreras = [
  'Ingeniería en Sistemas Computacionales',
  'Ingeniería Industrial',
  'Ingeniería Mecánica',
  'Ingeniería Electrónica',
  'Ingeniería Química',
  'Licenciatura en Administración',
  'Ingeniería en Gestión Empresarial'
]

const alertInfo = computed(() => userType.value === 'oficina')

const selectUserType = (type) => {
  userType.value = type
  alertError.value = ''
}

const handleRegister = () => {
  alertError.value = ''

  if (form.value.password !== form.value.confirmPassword) {
    alertError.value = 'Las contraseñas no coinciden'
    return
  }

  if (form.value.password.length < 8) {
    alertError.value = 'La contraseña debe tener al menos 8 caracteres'
    return
  }

  if (userType.value === 'monitor') {
    if (!form.value.numeroControl || !form.value.carrera || !form.value.semestre) {
      alertError.value = 'Completa todos los campos requeridos para Monitor'
      return
    }
  }

  registeredUser.value = {
    nombre: `${form.value.nombre} ${form.value.apellidoP} ${form.value.apellidoM}`,
    tipo: userType.value.toUpperCase(),
    usuario: form.value.usuario,
    numeroControl: form.value.numeroControl || 'N/A',
    carrera: form.value.carrera || 'N/A'
  }

  showSuccessModal.value = true
  
  console.log('Registrando usuario:', {
    ...form.value,
    tipo: userType.value
  })
}

const closeModalAndReset = () => {
  showSuccessModal.value = false
  resetForm()
}

const goToLogin = () => {
  showSuccessModal.value = false
  router.push('/')
}

const resetForm = () => {
  form.value = {
    nombre: '',
    apellidoP: '',
    apellidoM: '',
    numeroControl: '',
    carrera: '',
    semestre: '',
    usuario: '',
    password: '',
    confirmPassword: ''
  }
  userType.value = 'oficina'
  alertError.value = ''
}
</script>

<template>
  <div class="registro-container d-flex align-items-start justify-content-center py-4">
    <div class="container-fluid px-3 px-md-5 h-100">
      <div class="row justify-content-center h-100">
        <div class="col-12 col-xl-11">
          <!-- Card Principal -->
          <div class="card shadow-lg border-0 rounded-4 overflow-hidden main-card my-3">
            <!-- Header -->
            <div class="card-header-custom text-center text-white py-4">
              <div class="mb-3">
                <i class="bi bi-person-plus-fill" style="font-size: 3rem;"></i>
              </div>
              <h2 class="fw-light mb-2">Crear Nueva Cuenta</h2>
              <p class="mb-0 opacity-75">Registra un nuevo usuario en el sistema</p>
            </div>

            <!-- Body -->
            <div class="card-body bg-light p-4 p-md-5 p-lg-5">
              <!-- Alerta Informativa -->
              <div v-if="alertInfo" class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                Los usuarios de OFICINA no requieren número de control, carrera ni semestre.
                <button type="button" class="btn-close" @click="alertInfo = false"></button>
              </div>

              <!-- Alerta de Error -->
              <div v-if="alertError" class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4 rounded-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ alertError }}
                <button type="button" class="btn-close" @click="alertError = ''"></button>
              </div>

              <form @submit.prevent="handleRegister">
                <!-- Selector de Tipo de Usuario -->
                <div class="mb-4">
                  <label class="form-label fw-bold text-secondary mb-3">Tipo de Usuario</label>
                  <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <button
                      type="button"
                      class="btn btn-lg px-4"
                      :class="userType === 'oficina' ? 'btn-primary' : 'btn-outline-primary'"
                      @click="selectUserType('oficina')"
                    >
                      <i class="bi bi-building me-2"></i>
                      Usuario de Oficina
                    </button>
                    <button
                      type="button"
                      class="btn btn-lg px-4"
                      :class="userType === 'monitor' ? 'btn-primary' : 'btn-outline-primary'"
                      @click="selectUserType('monitor')"
                    >
                      <i class="bi bi-person-badge me-2"></i>
                      Monitor
                    </button>
                  </div>
                </div>

                <!-- Sección: Datos Personales -->
                <div class="mb-5">
                  <h5 class="fw-bold text-secondary mb-4 pb-2 border-bottom">
                    <i class="bi bi-person-fill me-2"></i>
                    Datos Personales
                  </h5>
                  <div class="row g-3 g-md-4">
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                      <input 
                        v-model="form.nombre" 
                        type="text" 
                        class="form-control" 
                        placeholder="Ej: María"
                        required 
                      />
                    </div>

                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Apellido Paterno <span class="text-danger">*</span></label>
                      <input 
                        v-model="form.apellidoP" 
                        type="text" 
                        class="form-control" 
                        placeholder="Ej: González"
                        required 
                      />
                    </div>

                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Apellido Materno <span class="text-danger">*</span></label>
                      <input 
                        v-model="form.apellidoM" 
                        type="text" 
                        class="form-control" 
                        placeholder="Ej: López"
                        required 
                      />
                    </div>
                  </div>
                </div>

                <!-- Sección: Datos Académicos (Solo Monitor) -->
                <div v-if="userType === 'monitor'" class="mb-5">
                  <h5 class="fw-bold text-secondary mb-4 pb-2 border-bottom">
                    <i class="bi bi-mortarboard-fill me-2"></i>
                    Datos Académicos
                  </h5>
                  <div class="row g-3 g-md-4">
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Número de Control <span class="text-danger">*</span></label>
                      <input 
                        v-model="form.numeroControl" 
                        type="text" 
                        class="form-control" 
                        placeholder="Ej: 20240001"
                        required 
                      />
                    </div>

                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Carrera <span class="text-danger">*</span></label>
                      <select v-model="form.carrera" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <option v-for="carrera in carreras" :key="carrera" :value="carrera">
                          {{ carrera }}
                        </option>
                      </select>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Semestre <span class="text-danger">*</span></label>
                      <select v-model="form.semestre" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <option v-for="n in 9" :key="n" :value="n">{{ n }}</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Sección: Datos de Acceso -->
                <div class="mb-5">
                  <h5 class="fw-bold text-secondary mb-4 pb-2 border-bottom">
                    <i class="bi bi-key-fill me-2"></i>
                    Datos de Acceso
                  </h5>
                  <div class="row g-3 g-md-4">
                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-person"></i>
                        </span>
                        <input 
                          v-model="form.usuario" 
                          type="text" 
                          class="form-control" 
                          placeholder="Ej: mgonzalez"
                          required 
                        />
                      </div>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-lock"></i>
                        </span>
                        <input
                          v-model="form.password"
                          type="password"
                          class="form-control"
                          placeholder="Mínimo 8 caracteres"
                          required
                          minlength="8"
                        />
                      </div>
                    </div>

                    <div class="col-md-4">
                      <label class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                      <div class="input-group">
                        <span class="input-group-text">
                          <i class="bi bi-lock-fill"></i>
                        </span>
                        <input
                          v-model="form.confirmPassword"
                          type="password"
                          class="form-control"
                          placeholder="Repite la contraseña"
                          required
                          minlength="8"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Botones de Acción -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                  <button type="button" class="btn btn-outline-secondary btn-lg px-4" @click="resetForm">
                    <i class="bi bi-arrow-clockwise me-2"></i>
                    Limpiar Formulario
                  </button>
                  <button type="submit" class="btn btn-success btn-lg px-5">
                    <i class="bi bi-check-circle me-2"></i>
                    Crear Cuenta
                  </button>
                </div>

                <!-- Link a Login -->
                <div class="text-center mt-4">
                  <a href="#" class="text-decoration-none fw-semibold" @click.prevent="router.push('/')">
                    <i class="bi bi-arrow-left me-2"></i>
                    Volver al inicio de sesión
                  </a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Éxito -->
    <div 
      class="modal fade" 
      :class="{ show: showSuccessModal }" 
      :style="{ display: showSuccessModal ? 'block' : 'none' }"
      tabindex="-1"
      @click.self="closeModalAndReset"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-5">
          <div class="modal-header bg-success text-white border-0 rounded-top-5">
            <h5 class="modal-title">
              <i class="bi bi-check-circle-fill me-2"></i>
              Registro Exitoso
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="closeModalAndReset"></button>
          </div>
          <div class="modal-body p-4" v-if="registeredUser">
            <div class="text-center mb-4">
              <div class="success-icon mb-3">
                <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
              </div>
              <h4 class="text-success fw-bold mb-3">Usuario Registrado Correctamente</h4>
            </div>
            
            <div class="card bg-light border-0 rounded-4">
              <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-secondary">Datos del Usuario:</h6>
                <ul class="list-unstyled mb-0">
                  <li class="mb-2">
                    <i class="bi bi-person-fill text-primary me-2"></i>
                    <strong>Nombre:</strong> {{ registeredUser.nombre }}
                  </li>
                  <li class="mb-2">
                    <i class="bi bi-tag-fill text-primary me-2"></i>
                    <strong>Tipo:</strong> 
                    <span class="badge" :class="registeredUser.tipo === 'OFICINA' ? 'bg-primary' : 'bg-info'">
                      {{ registeredUser.tipo }}
                    </span>
                  </li>
                  <li class="mb-2">
                    <i class="bi bi-person-badge text-primary me-2"></i>
                    <strong>Usuario:</strong> {{ registeredUser.usuario }}
                  </li>
                  <li v-if="registeredUser.numeroControl !== 'N/A'" class="mb-2">
                    <i class="bi bi-card-text text-primary me-2"></i>
                    <strong>No. Control:</strong> {{ registeredUser.numeroControl }}
                  </li>
                  <li v-if="registeredUser.carrera !== 'N/A'">
                    <i class="bi bi-mortarboard text-primary me-2"></i>
                    <strong>Carrera:</strong> {{ registeredUser.carrera }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 rounded-bottom-5 p-4">
            <button type="button" class="btn btn-outline-secondary" @click="closeModalAndReset">
              <i class="bi bi-plus-circle me-2"></i>
              Registrar Otro Usuario
            </button>
            <button type="button" class="btn btn-primary" @click="goToLogin">
              <i class="bi bi-box-arrow-in-right me-2"></i>
              Ir al Login
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Backdrop del Modal -->
    <div 
      v-if="showSuccessModal" 
      class="modal-backdrop fade show"
      @click="closeModalAndReset"
    ></div>
  </div>
</template>

<style scoped>
.registro-container {
  background: linear-gradient(135deg, #2d3561 0%, #3f4e7a 50%, #5865a8 100%);
  min-height: 100vh;
  max-height: 100vh;
  overflow-y: auto;
}

.main-card {
  max-width: 100%;
  margin: 0 auto;
}

.card-header-custom {
  background: linear-gradient(135deg, #4a5f8f 0%, #5865a8 100%);
  padding: 2rem 2rem;
}

.card-body {
  max-height: calc(100vh - 250px);
  overflow-y: auto;
}

.btn-primary {
  background: linear-gradient(135deg, #4a5f8f 0%, #5865a8 100%);
  border: none;
  padding: 0.75rem 2rem;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #3f4e7a 0%, #4a5f8f 100%);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(74, 95, 143, 0.4);
}

.btn-outline-primary {
  border-color: #4a5f8f;
  color: #4a5f8f;
  padding: 0.75rem 2rem;
}

.btn-outline-primary:hover {
  background-color: #4a5f8f;
  border-color: #4a5f8f;
}

.form-control,
.form-select {
  padding: 0.75rem 1rem;
  font-size: 1rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #4a5f8f;
  box-shadow: 0 0 0 0.25rem rgba(74, 95, 143, 0.25);
}

.input-group-text {
  background-color: #f8f9fa;
  border-right: none;
  padding: 0.75rem 1rem;
}

.form-control {
  border-left: none;
}

.input-group:focus-within .input-group-text {
  border-color: #4a5f8f;
}

.form-label {
  font-size: 0.95rem;
  margin-bottom: 0.5rem;
}

.success-icon {
  animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  border-radius: 1.5rem !important;
}

.rounded-5 {
  border-radius: 1.5rem !important;
}

.rounded-top-5 {
  border-top-left-radius: 1.5rem !important;
  border-top-right-radius: 1.5rem !important;
}

.rounded-bottom-5 {
  border-bottom-left-radius: 1.5rem !important;
  border-bottom-right-radius: 1.5rem !important;
}

.alert {
  border-radius: 0.75rem;
  padding: 1rem 1.25rem;
}

/* Scrollbar personalizado para el card-body */
.card-body::-webkit-scrollbar {
  width: 8px;
}

.card-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.card-body::-webkit-scrollbar-thumb {
  background: #4a5f8f;
  border-radius: 10px;
}

.card-body::-webkit-scrollbar-thumb:hover {
  background: #3f4e7a;
}

/* Responsivo */
@media (max-width: 768px) {
  .card-header-custom {
    padding: 1.5rem 1rem;
  }
  
  .card-header-custom h2 {
    font-size: 1.5rem;
  }
  
  .card-header-custom i {
    font-size: 2rem !important;
  }
  
  .card-body {
    padding: 2rem 1.5rem !important;
    max-height: calc(100vh - 200px);
  }
  
  .btn-lg {
    padding: 0.65rem 1.5rem;
    font-size: 0.95rem;
  }
  
  .form-label {
    font-size: 0.9rem;
  }
  
  .mb-5 {
    margin-bottom: 2rem !important;
  }
}

@media (max-width: 576px) {
  .registro-container {
    padding: 0.5rem !important;
  }
  
  .main-card {
    margin: 0.5rem 0 !important;
  }
  
  .card-header-custom {
    padding: 1.25rem 1rem;
  }
  
  .card-body {
    padding: 1.5rem 1rem !important;
  }
  
  .d-grid.gap-2.d-md-flex {
    flex-direction: column;
  }
  
  .btn-lg {
    width: 100%;
  }
  
  h5 {
    font-size: 1rem;
  }
}

@media (min-height: 900px) {
  .card-body {
    max-height: calc(100vh - 300px);
  }
}
</style>