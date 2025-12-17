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

    <!-- Modal constancia (sin Bootstrap) -->
    <div v-if="showCertModal" class="modal-backdrop" @click.self="closeCertModal">
      <div class="modal-card">
        <div class="modal-header">
          <h5>Expedir constancia</h5>
          <button class="btn-close" @click="closeCertModal">×</button>
        </div>
        <div class="modal-body">
          <div class="form-grid">
            <div>
              <label class="form-label">Alumno</label>
              <input class="form-control" :value="selectedAlumno ? (selectedAlumno.apellidoP + ' ' + selectedAlumno.apellidoM + ' ' + selectedAlumno.nombre) : ''" disabled />
            </div>
            <div>
              <label class="form-label">Desempeño</label>
              <select v-model="certForm.desempeno" class="form-select">
                <option>Excelente</option>
                <option>Bueno</option>
                <option>Malo</option>
              </select>
            </div>
            <div>
              <label class="form-label">Periodo</label>
              <input v-model="certForm.periodo" class="form-control" placeholder="Ej. Ago 2025 – Dic 2025" />
            </div>
            <div v-if="errorMsg" class="text-danger small">{{ errorMsg }}</div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" @click="closeCertModal">Cancelar</button>
          <button class="btn btn-primary" @click="generateCertPDF">Generar PDF</button>
        </div>
      </div>
    </div>

    <div v-else class="text-muted mt-4 text-center">
      Selecciona un club para mostrar su lista.
    </div>
  </div>
</template>

<script>
import { jsPDF } from 'jspdf';

export default {
  name: "Listas",
  props: ["clubs", "alumnos", "fechas"],
  data() {
    return {
      clubSeleccionado: "",
      showCertModal: false,
      selectedAlumno: null,
      certForm: { desempeno: 'Excelente', periodo: '' },
      errorMsg: ''
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
      this.selectedAlumno = alumno;
      this.certForm = { desempeno: 'Excelente', periodo: '' };
      this.errorMsg = '';
      this.showCertModal = true;
    },
    closeCertModal() {
      this.showCertModal = false;
      this.selectedAlumno = null;
      this.certForm = { desempeno: 'Excelente', periodo: '' };
      this.errorMsg = '';
    },
    generateCertPDF() {
      if (!this.selectedAlumno) return;
      if (!this.certForm.periodo) {
        this.errorMsg = 'Periodo es requerido';
        return;
      }
      const a = this.selectedAlumno;
      const nombreCompleto = `${a.apellidoP || ''} ${a.apellidoM || ''} ${a.nombre || ''}`.trim();
      const carrera = a.carrera || 'No especificada';
      const club = a.club || this.clubSeleccionado || '—';
      const desempeno = this.certForm.desempeno;
      const periodo = this.certForm.periodo;

      const doc = new jsPDF({ unit: 'pt', format: 'letter' });
      const margin = 56; let y = margin + 10; const lineGap = 20;

      doc.setFont('helvetica', 'bold'); doc.setFontSize(16);
      doc.text('Instituto Tecnológico - Constancia de Participación', 306, y, { align: 'center' }); y += 28;
      doc.setFontSize(14); doc.text('Constancia', 306, y, { align: 'center' }); y += 34;

      doc.setFont('helvetica', 'normal'); doc.setFontSize(12);
      doc.text(`Alumno: ${nombreCompleto}`, margin, y); y += lineGap;
      doc.text(`Carrera: ${carrera}`, margin, y); y += lineGap;
      doc.text(`Club: ${club}`, margin, y); y += lineGap;
      doc.text(`Periodo: ${periodo}`, margin, y); y += lineGap;
      doc.text(`Desempeño: ${desempeno}`, margin, y); y += lineGap * 2;

      doc.setFont('helvetica', 'bold'); doc.text('Atentamente', 306, y, { align: 'center' }); y += lineGap * 2;
      doc.setFont('helvetica', 'normal');
      doc.text('Fernando Jair Mendoza Jiménez', 306, y, { align: 'center' }); y += lineGap;
      doc.text('Jefe de la Oficina de Promoción Deportiva', 306, y, { align: 'center' }); y += lineGap;

      const file = `Constancia_${(a.control || '').trim() || 'alumno'}.pdf`;
      doc.save(file);

      this.closeCertModal();
    },
    expedirTodas() {
      const lista = this.alumnosClub.filter(a => (a.faltas || 0) < 3);
      if (!lista.length) { alert('No hay alumnos acreditados para expedir constancias.'); return; }
      if (!this.certForm.periodo) {
        const p = prompt('Periodo para todas las constancias (ej. Ago 2025 – Dic 2025):');
        if (!p) return; this.certForm.periodo = p;
      }
      const desempeno = this.certForm.desempeno || 'Excelente';
      const periodo = this.certForm.periodo;

      lista.forEach((a) => {
        const doc = new jsPDF({ unit: 'pt', format: 'letter' });
        const margin = 56; let y = margin + 10; const lineGap = 20;
        const nombreCompleto = `${a.apellidoP || ''} ${a.apellidoM || ''} ${a.nombre || ''}`.trim();
        const carrera = a.carrera || 'No especificada';
        const club = a.club || this.clubSeleccionado || '—';

        doc.setFont('helvetica', 'bold'); doc.setFontSize(16);
        doc.text('Instituto Tecnológico - Constancia de Participación', 306, y, { align: 'center' }); y += 28;
        doc.setFontSize(14); doc.text('Constancia', 306, y, { align: 'center' }); y += 34;

        doc.setFont('helvetica', 'normal'); doc.setFontSize(12);
        doc.text(`Alumno: ${nombreCompleto}`, margin, y); y += lineGap;
        doc.text(`Carrera: ${carrera}`, margin, y); y += lineGap;
        doc.text(`Club: ${club}`, margin, y); y += lineGap;
        doc.text(`Periodo: ${periodo}`, margin, y); y += lineGap;
        doc.text(`Desempeño: ${desempeno}`, margin, y); y += lineGap * 2;

        doc.setFont('helvetica', 'bold'); doc.text('Atentamente', 306, y, { align: 'center' }); y += lineGap * 2;
        doc.setFont('helvetica', 'normal');
        doc.text('Fernando Jair Mendoza Jiménez', 306, y, { align: 'center' }); y += lineGap;
        doc.text('Jefe de la Oficina de Promoción Deportiva', 306, y, { align: 'center' }); y += lineGap;

        const file = `Constancia_${(a.control || '').trim() || 'alumno'}.pdf`;
        doc.save(file);
      });
    },
  },
};
</script>

<style scoped>
.table { font-size: 0.95rem; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.modal-card { width: min(640px, 95%); background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.2); display: flex; flex-direction: column; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #eee; }
.modal-body { padding: 14px; }
.modal-footer { padding: 10px 14px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #eee; }
.btn-close { border: none; background: transparent; font-size: 22px; line-height: 1; cursor: pointer; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-label { display: block; margin-bottom: 6px; font-weight: 600; }
.form-control, .form-select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }

.text-danger { color: #d32f2f; }
.small { font-size: 0.875rem; }
</style>
