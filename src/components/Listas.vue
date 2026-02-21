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
            <th v-for="fecha in fechasCols" :key="fecha">{{ fecha }}</th>
            <th>Acreditado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(alumno, index) in alumnosClub" :key="alumno.id || alumno.control || (alumno.nombre + '-' + alumno.apellidoP + '-' + alumno.apellidoM + '-' + index)">
            <td>
              {{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}
            </td>
            <td v-for="fecha in fechasCols" :key="fecha" class="text-center">
              <span v-if="alumno.asistencias && alumno.asistencias[fecha]" class="text-success fw-bold" style="font-size: 1.5rem">✔</span>
              <span v-else class="text-danger fw-bold" style="font-size: 1.5rem">✖</span>
            </td>
            <td>
              <span
                class="badge"
                :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'"
              >
                {{ alumno.faltas < 3 ? "Acreditado" : "No acreditado" }}
              </span>
            </td>
            <td>
              <button
                class="btn btn-sm btn-outline-info"
                @click="descargarConstancia(alumno)"
              >
                Descargar
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-end mt-3">
        <button class="btn btn-outline-secondary me-2" @click="descargarEvaluacionClub">
          Descargar evaluación del club
        </button>
        <button class="btn btn-success" @click="descargarTodas">
          Descargar todas (PDF)
        </button>
      </div>
    </div>

    <div v-else class="text-muted mt-4 text-center">
      Selecciona un club para mostrar su lista.
    </div>

    <div v-if="previewData" class="print-preview">
      <div class="preview-documento">
        <div class="constancia A4" id="constancia" style="padding: 5mm 10mm 15mm 10mm; position: relative; min-height: 260mm; box-sizing: border-box;">
          <!-- ENCABEZADO -->
          <table
            class="tabla-encabezado"
            cellpadding="0"
            cellspacing="0"
            style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 8pt; border: 1px solid #000;"
          >
            <tr>
              <!-- Logo -->
              <td rowspan="2" style="border-right: 1px solid #000; width: 120px; text-align: center; vertical-align: middle; padding: 5px;">
                <img src="../Img/Logo.jpg" alt="Logo" style="max-width: 100px; height: auto" />
              </td>
              <!-- Título -->
              <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; text-align: center; vertical-align: middle; padding: 5px;">
                Evaluación al desempeño de la Actividad Promoción Cultural y/o Deportiva
              </td>
              <!-- Detalles -->
              <td rowspan="2" style="width: 180px; padding: 0; vertical-align: top;">
                <table style="width: 100%; height: 100%; border-collapse: collapse; font-size: 8pt;">
                  <tr style="height: 33.33%;">
                    <td style="border-bottom: 1px solid #000; padding: 4px 6px; font-weight: bold;">Código: TecNM-VI-PO-003-04</td>
                  </tr>
                  <tr style="height: 33.33%;">
                    <td style="border-bottom: 1px solid #000; padding: 4px 6px;">Referencia a la Norma ISO 9001:2015 8.2.2</td>
                  </tr>
                  <tr style="height: 33.33%;">
                    <td style="padding: 4px 6px;">Revisión: 0<br>Página 2 de 2</td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <!-- Fila inferior vacía para estructura -->
              <td style="border-right: 1px solid #000; padding: 4px;"></td>
            </tr>
          </table>

          <div style="margin-top: 15px; text-align: center; font-size: 9pt; font-weight: bold;">
            INSTITUTO TECNOLÓGICO DE TLAXIACO<br>
            Subdirección de Planeación y Vinculación<br><br>
            DEPARTAMENTO DE ACTIVIDADES EXTRAESCOLARES<br>
            OFICINA DE PROMOCIÓN CULTURAL O DEPORTIVA
          </div>

          <div style="margin-top: 20px; font-size: 9pt; line-height: 1.8;">
            <div><strong>Nombre del estudiante:</strong> {{ toUpper(previewData.estudianteNombre) }}</div>
            <div><strong>Actividad Cultural y/o Deportiva:</strong> {{ toUpper(previewData.club) }}</div>
            <div><strong>Periodo de realización:</strong> {{ toUpper(previewData.mesInicio) }} - {{ toUpper(previewData.mesFin) }} {{ previewData.anioPeriodo }}</div>
          </div>

          <!-- TABLA DE CRITERIOS -->
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 8pt; border: 1px solid #000;">
            <thead>
              <tr style="background-color: #f2f2f2;">
                <th colspan="7" style="border: 1px solid #000; padding: 6px; text-align: center;">Nivel de desempeño del criterio</th>
              </tr>
              <tr style="background-color: #f2f2f2; text-align: center;">
                <th style="border: 1px solid #000; padding: 6px; width: 30px;">No.</th>
                <th style="border: 1px solid #000; padding: 6px;">Criterios a evaluar</th>
                <th style="border: 1px solid #000; padding: 6px; width: 70px;">Insuficiente</th>
                <th style="border: 1px solid #000; padding: 6px; width: 70px;">Suficiente</th>
                <th style="border: 1px solid #000; padding: 6px; width: 70px;">Bueno</th>
                <th style="border: 1px solid #000; padding: 6px; width: 70px;">Notable</th>
                <th style="border: 1px solid #000; padding: 6px; width: 70px;">Excelente</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(criterio, idx) in previewData.criterios" :key="idx">
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ idx + 1 }}</td>
                <td style="border: 1px solid #000; padding: 6px;">{{ criterio.descripcion }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ criterio.nivel === 1 ? 'X' : '' }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ criterio.nivel === 2 ? 'X' : '' }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ criterio.nivel === 3 ? 'X' : '' }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ criterio.nivel === 4 ? 'X' : '' }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ criterio.nivel === 5 ? 'X' : '' }}</td>
              </tr>
            </tbody>
          </table>

          <div style="margin-top: 15px; font-size: 9pt;">
            <div><strong>Observaciones:</strong></div>
            <div style="min-height: 40px; border-bottom: 1px solid #000; margin-top: 5px; padding: 5px;">
              {{ previewData.observaciones || '' }}
            </div>
          </div>

          <div style="margin-top: 15px; font-size: 9pt;">
            <div><strong>Valor numérico de la actividad Cultural y/o Deportiva:</strong> {{ previewData.valorNumerico }}</div>
            <div><strong>Nivel de desempeño alcanzado de la actividad Cultural y/o Deportiva:</strong> {{ toUpper(previewData.desempeno) }}</div>
          </div>

          <!-- PIE DE PÁGINA -->
          <div style="position: absolute; bottom: 5mm; left: 10mm; right: 10mm; font-size: 8pt; display: flex; justify-content: space-between;">
            <span>TecNM-VI-PO-003-04</span>
            <span>Rev. 0</span>
          </div>
        </div>
      </div>

      <div class="acciones-preview">
        <h5 class="mb-3">Evaluación del Estudiante</h5>
        <div class="campos-editar">
          <div class="campo">
            <label>Período:</label>
            <div class="d-flex gap-2">
              <select v-model="previewData.mesInicio" class="form-select form-select-sm">
                <option value="Enero">Enero</option>
                <option value="Febrero">Febrero</option>
                <option value="Marzo">Marzo</option>
                <option value="Abril">Abril</option>
                <option value="Mayo">Mayo</option>
                <option value="Junio">Junio</option>
                <option value="Julio">Julio</option>
                <option value="Agosto">Agosto</option>
                <option value="Septiembre">Septiembre</option>
                <option value="Octubre">Octubre</option>
                <option value="Noviembre">Noviembre</option>
                <option value="Diciembre">Diciembre</option>
              </select>
              <span class="align-self-center">-</span>
              <select v-model="previewData.mesFin" class="form-select form-select-sm">
                <option value="Enero">Enero</option>
                <option value="Febrero">Febrero</option>
                <option value="Marzo">Marzo</option>
                <option value="Abril">Abril</option>
                <option value="Mayo">Mayo</option>
                <option value="Junio">Junio</option>
                <option value="Julio">Julio</option>
                <option value="Agosto">Agosto</option>
                <option value="Septiembre">Septiembre</option>
                <option value="Octubre">Octubre</option>
                <option value="Noviembre">Noviembre</option>
                <option value="Diciembre">Diciembre</option>
              </select>
              <input
                v-model="previewData.anioPeriodo"
                type="number"
                min="2020"
                max="2030"
                class="form-control form-control-sm"
                style="width: 100px"
              />
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-bold">Jefa de Servicios Escolares (Manual)</label>
            <input 
              v-model="jefesSeleccionados.jefa_servicios_nombre" 
              class="form-control form-control-sm" 
              placeholder="Nombre de la jefa"
              @change="guardarPreferenciaManual('jefa_servicios')"
            />
          </div>
        </div>
        <div class="botones-acciones">
          <button
            class="btn btn-secondary w-100 mb-2"
            @click="previewData = null"
          >
            Cerrar
          </button>
          <button class="btn btn-primary w-100" @click="generarPDF">
            Descargar PDF
          </button>
        </div>
      </div>
      </div>
    </div>

    <div v-if="previewEvaluacion" class="print-preview">
      <div class="preview-documento">
        <div class="constancia A4" id="evaluacion-club" style="padding: 5mm 10mm 15mm 10mm; position: relative; min-height: 260mm; box-sizing: border-box;">
          <!-- ENCABEZADO -->
          <table
            class="tabla-encabezado"
            cellpadding="0"
            cellspacing="0"
            style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 8pt; border: 1px solid #000;"
          >
            <tr>
              <!-- Columna Logo -->
              <td rowspan="2" style="border-right: 1px solid #000; width: 120px; text-align: center; vertical-align: middle; padding: 5px;">
                <img src="../Img/Logo.jpg" alt="Logo" style="max-width: 100px; height: auto" />
              </td>
              <!-- Columna Título -->
              <td style="border-right: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; text-align: center; vertical-align: middle; padding: 5px;">
                Formato de Resultados de Actividades Culturales y/o Deportivas.
              </td>
              <!-- Columna Detalles Header -->
              <td rowspan="2" style="width: 180px; padding: 0; vertical-align: top;">
                <table style="width: 100%; height: 100%; border-collapse: collapse; font-size: 8pt;">
                  <tr style="height: 33.33%;">
                    <td style="border-bottom: 1px solid #000; padding: 4px 6px; font-weight: bold;">Código: TecNM-VI-PO-003-03</td>
                  </tr>
                  <tr style="height: 33.33%;">
                    <td style="border-bottom: 1px solid #000; padding: 4px 6px;">Revisión: 0</td>
                  </tr>
                  <tr style="height: 33.33%;">
                    <td style="padding: 4px 6px;">Página 1 de 1</td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <!-- Fila Inferior Centro: Norma -->
              <td style="border-right: 1px solid #000; text-align: left; vertical-align: middle; padding: 4px 10px;">
                Referencia a la Norma ISO 9001:2015 8.1, 8.2.1, 8.2.2
              </td>
            </tr>
          </table>

          <div style="margin-top: 20px; text-align: center; font-size: 9pt; font-weight: bold;">
            DEPARTAMENTO DE ACTIVIDADES EXTRAESCOLARES<br />
            ACTIVIDAD: {{ toUpper(clubSeleccionado) }}
          </div>

          <!-- TABLA DE RESULTADOS -->
          <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 8pt; border: 1px solid #000;">
            <thead>
              <tr style="background-color: #f2f2f2; text-align: center;">
                <th style="border: 1px solid #000; padding: 6px; width: 30px;">NO.</th>
                <th style="border: 1px solid #000; padding: 6px;">NOMBRE</th>
                <th style="border: 1px solid #000; padding: 6px; width: 80px;">NO.CONTROL</th>
                <th style="border: 1px solid #000; padding: 6px;">CARRERA</th>
                <th style="border: 1px solid #000; padding: 6px; width: 40px;">SEM</th>
                <th style="border: 1px solid #000; padding: 6px; width: 110px;">RESULTADO</th>
                <th style="border: 1px solid #000; padding: 6px;">FIRMA DE ENTERADO</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(al, idx) in previewEvaluacion.alumnos" :key="idx">
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ idx + 1 }}</td>
                <td style="border: 1px solid #000; padding: 6px;">{{ toUpper((al.apellidoP || '') + (al.apellidoM ? ' ' + al.apellidoM : '') + (al.nombre ? ' ' + al.nombre : '')) }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ al.numeroControl || al.control }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ displayCarrera(al.carrera) }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ al.semestre || '' }}</td>
                <td style="border: 1px solid #000; padding: 6px; text-align: center;">{{ al.faltas < 3 ? 'ACREDITADO' : 'NO ACREDITADO' }}</td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
              </tr>
              <!-- Filas vacías para completar el formato si hay pocos alumnos -->
              <tr v-for="n in Math.max(0, 15 - previewEvaluacion.alumnos.length)" :key="'empty-'+n">
                <td style="border: 1px solid #000; padding: 6px; color: transparent;">-</td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
                <td style="border: 1px solid #000; padding: 6px;"></td>
              </tr>
            </tbody>
          </table>

          <div style="margin-top: 30px; font-size: 9pt;">
            Lugar y fecha: {{ previewEvaluacion.lugar }}, {{ previewEvaluacion.fechaCompleta }}
          </div>

          <!-- FIRMAS -->
          <table style="width: 100%; border-collapse: collapse; margin-top: 40px; font-size: 8pt; text-align: center;">
            <tr style="border: none !important;">
              <td style="width: 33%; border: none !important; vertical-align: top;">
                <div style="border-top: 1px solid #000; margin: 40px 10px 0; padding-top: 5px;">
                  {{ getNombreJefe('jefa_servicios') || '__________________________' }}<br />
                  <strong>Jefa de Departamento de Servicios Escolares</strong>
                </div>
              </td>
              <td style="width: 33%; border: none !important; vertical-align: top;">
                <div style="border-top: 1px solid #000; margin: 40px 10px 0; padding-top: 5px;">
                  {{ getNombreJefe('jefe_promocion') || '__________________________' }}<br />
                  <strong>Jefe de Oficina de Promoción {{ getTipoActual() }}</strong>
                </div>
              </td>
              <td style="width: 33%; border: none !important; vertical-align: top;">
                <div style="border-top: 1px solid #000; margin: 40px 10px 0; padding-top: 5px;">
                  {{ getNombreJefe('jefe_actividades') || '__________________________' }}<br />
                  <strong>Jefe de Departamento de Actividades Extraescolares</strong>
                </div>
              </td>
            </tr>
          </table>

          <!-- PIE DE PAGINA -->
          <div style="position: absolute; bottom: 5mm; left: 10mm; right: 10mm; font-size: 8pt; display: flex; justify-content: space-between;">
            <span>TecNM-VI-PO-003-03</span>
            <span>Rev. 0</span>
          </div>
        </div>
      </div>

      <div class="acciones-preview p-3" style="width: 300px; background: #f8f9fa; border-left: 1px solid #dee2e6;">
        <h5 class="mb-3">Configuración de Firmas</h5>
        
        <div class="mb-3">
          <label class="form-label small fw-bold">Jefa de Servicios Escolares (Manual)</label>
          <input 
            v-model="jefesSeleccionados.jefa_servicios_nombre" 
            class="form-control form-control-sm" 
            placeholder="Nombre de la jefa"
            @change="guardarPreferenciaManual('jefa_servicios')"
          />
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Jefe de Promoción</label>
          <select v-model="jefesSeleccionados.jefe_promocion" class="form-select form-select-sm" @change="guardarPreferencia('jefe_promocion')">
            <option value="">-- Seleccionar --</option>
            <option v-for="u in usuariosOficina" :key="u.id" :value="u.id">
              {{ formatNombre(u) }}
            </option>
          </select>
        </div>

        <div class="mb-4">
          <label class="form-label small fw-bold">Jefe de Actividades</label>
          <select v-model="jefesSeleccionados.jefe_actividades" class="form-select form-select-sm" @change="guardarPreferencia('jefe_actividades')">
            <option value="">-- Seleccionar --</option>
            <option v-for="u in usuariosOficina" :key="u.id" :value="u.id">
              {{ formatNombre(u) }}
            </option>
          </select>
        </div>

        <hr>

        <h5 class="mb-3">Evaluación del Club</h5>
        <p class="text-muted small">Haz clic en descargar para obtener el documento en formato carta.</p>
        <div class="botones-acciones">
           <button class="btn btn-primary w-100 mb-2" @click="generarPDFEvaluacion">
            Descargar PDF
          </button>
          <button class="btn btn-secondary w-100" @click="previewEvaluacion = null">
            Cerrar
          </button>
        </div>
      </div>
    </div>

</template>

<script>
import { getAsistenciasPorClub, getEvaluacion, getFirmas, asignarCargo, saveConfig } from "../services/api";
export default {
  name: "Listas",
  props: ["clubs", "alumnos", "fechas", "carreras", "usuarios"],
  data() {
    return {
      clubSeleccionado: "",
      previewData: null,
      previewEvaluacion: null,
      periodoActual: this.getPeriodoActual(),
      alumnosData: [],
      fechasData: [],
      jefesSeleccionados: {
        jefe_promocion: "",
        jefe_actividades: "",
        jefa_servicios_nombre: ""
      }
    };
  },
  computed: {
    fechasCols() {
      return this.fechasData && this.fechasData.length
        ? this.fechasData
        : this.fechas || [];
    },
    usuariosOficina() {
      if (!this.usuarios) return [];
      return this.usuarios.filter(u => 
        u.tipo && u.tipo.toString().toUpperCase() === 'OFICINA'
      );
    },
    alumnosClub() {
      // Preferir alumnos desde backend; si no hay, usar fallback desde props
      let base =
        this.alumnosData && this.alumnosData.length
          ? this.alumnosData.slice()
          : (this.alumnos || [])
              .filter((a) => a.club === this.clubSeleccionado)
              .map((a) => {
                const asist = a.asistencias || {};
                const faltas = Object.values(asist).filter(
                  (v) => v === false,
                ).length;
                return { ...a, asistencias: asist, faltas };
              });
      base.sort(
        (a, b) =>
          (a.apellidoP || "").localeCompare(b.apellidoP || "") ||
          (a.apellidoM || "").localeCompare(b.apellidoM || "") ||
          (a.nombre || "").localeCompare(b.nombre || ""),
      );
      return base;
    },
    fechaHoy() {
      const ahora = new Date();
      const meses = [
        "enero",
        "febrero",
        "marzo",
        "abril",
        "mayo",
        "junio",
        "julio",
        "agosto",
        "septiembre",
        "octubre",
        "noviembre",
        "diciembre",
      ];
      return {
        dia: ahora.getDate(),
        mes: meses[ahora.getMonth()],
        anio: ahora.getFullYear(),
      };
    },
  },
  methods: {
    async loadAsistencias() {
      // Limpiar datos anteriores para evitar mezcla visual mientras se actualiza
      this.alumnosData = [];
      const club = (this.clubs || []).find(c => c.nombre === this.clubSeleccionado);

      // Si no hay club o ID, usar los datos de props como respaldo
      if (!club || !club.id) {
        this.fechasData = [];
        if (this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          this.alumnosData = [];
        }
        return;
      }

      try {
        const data = await getAsistenciasPorClub(club.id);
        this.fechasData = Array.isArray(data.fechas) ? data.fechas : [];
        const alumnos = Array.isArray(data.alumnos) ? data.alumnos : [];

        // Si el backend no devuelve alumnos, usar props
        if (!alumnos.length && this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          const asist = data.asistencias || {};
          this.alumnosData = alumnos.map(al => {
            const map = { ...(asist[al.id] || {}) };
            const faltas = Object.values(map).filter(v => v === false).length;
            // Enriquecer con carrera y semestre desde props (this.alumnos) si faltan
            const alumnoInfo = (this.alumnos || []).find(a => String(a.id) === String(al.id) || a.control === al.numeroControl);
            return { 
              ...al, 
              asistencias: map, 
              faltas,
              carrera: al.carrera || (alumnoInfo ? alumnoInfo.carrera : ''),
              semestre: al.semestre || (alumnoInfo ? alumnoInfo.semestre : '')
            };
          });
        }
      } catch (e) {
        console.error('Error cargando asistencias:', e);
        this.fechasData = [];
        // Si hay error, cargar desde props
        if (this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          this.alumnosData = [];
        }
      }
    },
    actualizarFaltas(alumno) {
      const asist = alumno.asistencias || {};
      const totalFaltas = Object.values(asist).filter(
        (v) => v === false,
      ).length;
      this.$set
        ? this.$set(alumno, "faltas", totalFaltas)
        : (alumno.faltas = totalFaltas);
    },
    toUpper(v) {
      return (v == null ? "" : String(v)).toUpperCase();
    },
    displayCarrera(nombre) {
      if (!nombre) return '';
      const found = Array.isArray(this.carreras) ? this.carreras.find(c => c.nombre === nombre) : null;
      return found ? (found.abreviatura || found.nombre) : nombre;
    },
    desempenoValor(desempeno) {
      const mapa = { EXCELENTE: 4, NOTABLE: 4, BUENO: 3, REGULAR: 2, SUFICIENTE: 2, DEFICIENTE: 1, INSUFICIENTE: 1 };
      return mapa[(desempeno || "").toUpperCase()] || 0;
    },
    getPeriodoActual() {
      const f = new Date();
      const mes = f.getMonth();
      const anio = f.getFullYear();
      if (mes >= 0 && mes <= 5) {
        return { mesInicio: 'Enero', mesFin: 'Junio', anioPeriodo: anio };
      } else {
        return { mesInicio: 'Agosto', mesFin: 'Diciembre', anioPeriodo: anio };
      }
    },
    isCulturalName(nombre) {
      const n = (nombre || "").toString().trim().toLowerCase();
      const culturales = [
        "danza",
        "rondalla",
        "banda de musica",
        "banda de música",
      ];
      return culturales.includes(n);
    },
    tipoActividad(club) {
      if (club && club.tipo) return club.tipo.toUpperCase();
      const nombre = typeof club === 'string' ? club : (club?.nombre || "");
      return this.isCulturalName(nombre) ? "CULTURAL" : "DEPORTIVA";
    },
    async imprimirConstancia(alumno) {
      const clubNombre = this.clubSeleccionado || "";
      const periodo = this.getPeriodoActual();
      let desempeno = '';
      let valorNumerico = null;
      let observaciones = '';
      let criterios = [];

      // Descripciones de los 7 criterios
      const criteriosDescripciones = [
        'Cumple en tiempo y forma con las actividades encomendadas alcanzando los objetivos.',
        'Trabaja en equipo y se adapta a nuevas situaciones.',
        'Muestra liderazgo en las actividades encomendadas.',
        'Organiza su tiempo y trabaja de manera proactiva.',
        'Interpreta la realidad y se sensibiliza aportando soluciones a la problemática con la actividad Cultural y/o Deportiva.',
        'Realiza sugerencias innovadoras para beneficio o mejora del programa en el que participa.',
        'Tiene iniciativa para ayudar en las actividades encomendadas y muestra espíritu de servicio.'
      ];

      try {
        const nombreFull = `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim();
        console.log('Buscando evaluación para:', nombreFull, 'Club:', alumno.club || clubNombre);
        
        const evalData = await getEvaluacion({ 
          nombre_estudiante: nombreFull, 
          nombre_club: (alumno.club || clubNombre || '') 
        });

        console.log('Datos de evaluación recibidos:', evalData);

        if (evalData) {
          // Obtener nivel de desempeño
          const nivel = parseInt(evalData.nivel_desempeno);
          console.log('Nivel de desempeño:', nivel);
          
          if (nivel === 5) desempeno = 'EXCELENTE';
          else if (nivel === 4) desempeno = 'NOTABLE';
          else if (nivel === 3) desempeno = 'BUENO';
          else if (nivel === 2) desempeno = 'SUFICIENTE';
          else desempeno = 'INSUFICIENTE';
          
          valorNumerico = parseFloat(evalData.valor_numerico).toFixed(2);
          observaciones = evalData.observaciones || '';

          console.log('Criterios desde BD:', {
            c1: evalData.criterio_1,
            c2: evalData.criterio_2,
            c3: evalData.criterio_3,
            c4: evalData.criterio_4,
            c5: evalData.criterio_5,
            c6: evalData.criterio_6,
            c7: evalData.criterio_7
          });

          // Construir array de criterios con sus niveles
          criterios = [
            { descripcion: criteriosDescripciones[0], nivel: parseInt(evalData.criterio_1) || 1 },
            { descripcion: criteriosDescripciones[1], nivel: parseInt(evalData.criterio_2) || 1 },
            { descripcion: criteriosDescripciones[2], nivel: parseInt(evalData.criterio_3) || 1 },
            { descripcion: criteriosDescripciones[3], nivel: parseInt(evalData.criterio_4) || 1 },
            { descripcion: criteriosDescripciones[4], nivel: parseInt(evalData.criterio_5) || 1 },
            { descripcion: criteriosDescripciones[5], nivel: parseInt(evalData.criterio_6) || 1 },
            { descripcion: criteriosDescripciones[6], nivel: parseInt(evalData.criterio_7) || 1 }
          ];
          
          console.log('Criterios procesados:', criterios);
        } else {
          console.log('No se encontró evaluación para este estudiante');
          // Si no hay evaluación, crear criterios vacíos
          criterios = criteriosDescripciones.map(desc => ({ descripcion: desc, nivel: 1 }));
        }
      } catch (e) {
        console.error('Error obteniendo evaluación:', e);
        // Si hay error, crear criterios vacíos
        criterios = criteriosDescripciones.map(desc => ({ descripcion: desc, nivel: 1 }));
      }

      const data = {
        estudianteNombre: `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim(),
        numeroControl: alumno.numeroControl || 'SIN CONTROL',
        carrera: alumno.carrera || (this.carreras && this.carreras[0] ? this.carreras[0].nombre : 'SIN CARRERA'),
        club: (alumno.club || clubNombre || '').toLowerCase(),
        desempeno: desempeno || (
          alumno.desempeno ||
          (alumno.faltas <= 1
            ? 'EXCELENTE'
            : alumno.faltas === 2
              ? 'BUENO'
              : 'REGULAR')
        ).toString(),
        valorNumerico: valorNumerico || '0.00',
        observaciones: observaciones,
        criterios: criterios,
        mesInicio: periodo.mesInicio,
        mesFin: periodo.mesFin,
        anioPeriodo: periodo.anioPeriodo,
      };
      
      this.previewData = {
        tipoActividad: this.tipoActividad(data.club),
        ...data,
      };
    },
    descargarConstancia(alumno) {
      this.imprimirConstancia(alumno);
    },
    generarPDF() {
      this.$nextTick(() => {
        setTimeout(() => {
          const nodo = document.getElementById("constancia");
          if (!nodo || !this.previewData) return;

          const nombre = `${this.previewData.estudianteNombre.replace(/\s+/g, "_")}_${this.previewData.numeroControl}`;
          const opt = {
            margin: [10, 10, 10, 10],
            filename: `Evaluacion_${nombre}.pdf`,
            image: { type: "jpeg", quality: 0.98 },
            html2canvas: { 
              scale: 2,
              allowTaint: true,
              useCORS: true,
              logging: false,
              windowHeight: nodo.scrollHeight
            },
            jsPDF: { orientation: "portrait", unit: "mm", format: "letter" },
          };

          // Cargar html2pdf desde CDN
          if (typeof html2pdf === "undefined") {
            const script = document.createElement("script");
            script.src =
              "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
            document.head.appendChild(script);
            script.onload = () => {
              setTimeout(() => {
                html2pdf().set(opt).from(nodo).save();
              }, 200);
            };
          } else {
            setTimeout(() => {
              html2pdf().set(opt).from(nodo).save();
            }, 200);
          }
        }, 300);
      });
    },
    descargarTodas() {
      console.warn('Descargar todas (PDF) no implementado');
    },
    async descargarEvaluacionClub() {
      if (!this.clubSeleccionado) {
        return alert('Seleccione un club primero');
      }

      const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
      const ahora = new Date();
      
      this.previewEvaluacion = {
        club: this.clubSeleccionado,
        alumnos: this.alumnosClub,
        fechaCompleta: `${ahora.getDate()} de ${meses[ahora.getMonth()]} de ${ahora.getFullYear()}`,
        lugar: 'Heroica Ciudad de Tlaxiaco',
        promotor: 'FERNANDO JAIR MENDOZA JIMENEZ' // Por defecto
      };
    },
    generarPDFEvaluacion() {
      this.$nextTick(() => {
        setTimeout(() => {
          const nodo = document.getElementById("evaluacion-club");
          if (!nodo || !this.previewEvaluacion) return;

          const nombreClub = this.previewEvaluacion.club.replace(/\s+/g, "_");
          const opt = {
            margin: [10, 10, 10, 10],
            filename: `Evaluacion_Club_${nombreClub}.pdf`,
            image: { type: "jpeg", quality: 0.98 },
            html2canvas: { 
              scale: 2, 
              useCORS: true, 
              allowTaint: true, 
              logging: false, 
              windowHeight: nodo.scrollHeight 
            },
            jsPDF: { orientation: "portrait", unit: "mm", format: "letter" },
          };

          if (typeof html2pdf === "undefined") {
            const script = document.createElement("script");
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
            document.head.appendChild(script);
            script.onload = () => {
              html2pdf().set(opt).from(nodo).save();
            };
          } else {
            html2pdf().set(opt).from(nodo).save();
          }
        }, 300);
      });
    },
    printStyles() {
      return `
        @page { size: A4; margin: 20mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; }
        .A4 { width: 190mm; margin: 0 auto; }
        .header { text-align: center; }
        .logos { display: grid; grid-template-columns: 80px 1fr 80px; align-items: center; gap: 10px; }
        .logo.izq, .logo.der { width: 70px; height: 70px; background: #eee; border-radius: 6px; }
        .titulos .top-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; }
        .titulos .meta { display: flex; justify-content: space-between; font-size: 10px; margin-top: 4px; }
        .separador { height: 2px; background: #000; margin: 10px 0; }
        h2 { font-size: 16px; margin: 10px 0 20px; text-align: center; }
        .cuerpo { font-size: 12px; line-height: 1.6; }
        .destinatario { font-weight: bold; text-align: left; }
        .texto.justificado { text-align: justify; }
        .lugar-fecha { margin-top: 16px; }
        .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px; text-align: center; }
        .firmas .linea { border-top: 1px solid #000; margin: 36px 40px 6px; }
        .firmas .nombre { font-weight: bold; }
        .firmas .cargo { font-size: 11px; }
      `;
    },
    formatNombre(u) {
      if (!u) return '';
      const np = u.nombre || '';
      const ap = u.apellidoP || '';
      const am = u.apellidoM || '';
      return `${np} ${ap} ${am}`.trim().toUpperCase();
    },
    async guardarPreferencia(cargo) {
      const idUsuario = this.jefesSeleccionados[cargo];
      if (idUsuario) {
        try {
          await asignarCargo(cargo, idUsuario);
        } catch (e) {
          console.error('Error al guardar cargo en BD:', e);
        }
      }
    },
    async guardarPreferenciaManual(cargo) {
      const valor = this.jefesSeleccionados[cargo + '_nombre'];
      try {
        await saveConfig('firma_' + cargo, valor);
      } catch (e) {
        console.error('Error al guardar firma manual:', e);
      }
    },
    async loadCargos() {
       try {
        const firmas = await getFirmas();
        if (firmas.jefe_actividades) this.jefesSeleccionados.jefe_actividades = firmas.jefe_actividades.id;
        if (firmas.jefe_promocion) this.jefesSeleccionados.jefe_promocion = firmas.jefe_promocion.id;
        if (firmas.jefa_servicios) {
          this.jefesSeleccionados.jefa_servicios_nombre = firmas.jefa_servicios.nombre;
        }
      } catch (e) {
        console.error('Error al cargar cargos:', e);
      }
    },
    getTipoActual() {
      const club = (this.clubs || []).find(c => c.nombre === this.clubSeleccionado);
      return this.tipoActividad(club || this.clubSeleccionado);
    },
    getNombreJefe(cargo) {
      if (cargo === 'jefa_servicios') {
        const nom = this.jefesSeleccionados.jefa_servicios_nombre;
        return nom ? 'C. ' + nom.toUpperCase() : '';
      }
      const id = this.jefesSeleccionados[cargo];
      if (!id) return '';
      const u = this.usuariosOficina.find(user => user.id == id);
      return u ? this.formatNombre(u) : '';
    },
    formatNombre(u) {
      if (!u) return '';
      const np = u.nombre || '';
      const ap = u.apellidoP || '';
      const am = u.apellidoM || '';
      return `${np} ${ap} ${am}`.trim().toUpperCase();
    },
  },
  watch: {
    clubSeleccionado() {
      this.loadAsistencias();
    },
  },
  async mounted() {
    await this.loadCargos();
    // si ya hay un club seleccionado inicial, cargar
    if (this.clubSeleccionado) this.loadAsistencias();
  },
};
</script>

<style scoped>
.table {
  font-size: 0.95rem;
}

.print-preview {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  gap: 20px;
  align-items: flex-start;
  justify-content: flex-start;
  z-index: 2000;
  padding: 20px;
  overflow-y: auto;
}

.preview-documento {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.print-preview .A4 {
  background: #fff;
  max-width: 215.9mm;
  padding: 15mm;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  width: 100%;
  font-family: Arial, sans-serif;
  color: #000;
  font-size: 11pt;
  line-height: 1.2;
}

/* Tablas */
.tabla-encabezado {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #000;
  margin-bottom: 30px;
  background: #fff;
}

.tabla-encabezado tr {
  border: 1px solid #000;
}

.tabla-encabezado td {
  border: 1px solid #000;
  padding: 8px;
}

.celda-logo {
  width: 80px;
  text-align: center;
  vertical-align: middle;
  background: #fff;
}

.logo-img {
  width: 70px;
  height: 70px;
  object-fit: contain;
}

.celda-titulo-principal {
  text-align: left;
  vertical-align: middle;
  font-size: 9pt;
  background: #fff;
  padding: 8px;
  padding-left: 10px;
}

.celda-codigo {
  width: 120px;
  text-align: left;
  vertical-align: top;
  background: #fff;
  padding: 8px;
  border-left: 1px solid #000;
  padding-left: 10px;
}

.celda-norma {
  text-align: left;
  font-size: 8pt;
  background: #fff;
  vertical-align: middle;
  padding-left: 10px;
}

.codigo-box {
  font-weight: bold;
  font-size: 9pt;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid #000;
}

.revision-box {
  font-size: 9pt;
  margin-bottom: 8px;
  padding: 8px 0;
  border-bottom: 1px solid #000;
}

.pagina-box {
  font-size: 9pt;
  padding-top: 8px;
}

.separador-grande {
  height: 2px;
  background: #000;
  margin: 15px 0;
}

.titulo-constancia {
  text-align: center;
  font-size: 12pt;
  font-weight: bold;
  margin: 50px 0 20px 0;
}

.espacios {
  height: 15px;
}

.espacios-mediano {
  height: 30px;
}

.espacios-firma {
  height: 30px;
}

.espacios-firma-grandes {
  height: 80px;
}

.cuerpo {
  font-size: 11pt;
  line-height: 1.5;
}

.destinatario {
  font-weight: bold;
  margin-bottom: 15px;
  line-height: 1.4;
}

.texto {
  text-align: justify;
  margin-bottom: 15px;
}

.texto.justificado {
  text-align: justify;
}

.lugar-fecha {
  text-align: left;
  margin-bottom: 15px;
}

.tabla-firmas {
  width: 100%;
  border-collapse: collapse;
  margin-top: 30px;
}

.celda-firma-iz,
.celda-firma-der {
  width: 50%;
  text-align: center;
  font-weight: bold;
  padding: 5px;
}

.celda-firma-nombre {
  width: 50%;
  text-align: center;
  padding: 5px;
}

.linea-firma {
  border-top: 1px solid #000;
  margin: 50px 20px 5px;
}

.nombre-firma {
  font-weight: bold;
  margin-top: 5px;
  font-size: 10pt;
}

.cargo-firma {
  font-size: 9pt;
  margin-top: 2px;
}

.pie-pagina {
  text-align: left;
  font-size: 10pt;
  margin-top: 20px;
  margin-bottom: 10px;
}

.tabla-pie {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  padding-top: 5px;
}

.pie-izq {
  text-align: left;
  font-size: 9pt;
  padding-top: 5px;
}

.pie-der {
  text-align: right;
  font-size: 9pt;
  padding-top: 5px;
}

.acciones-preview {
  width: 350px;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  height: fit-content;
  position: sticky;
  top: 20px;
}

.acciones-preview h5 {
  border-bottom: 2px solid #0d6efd;
  padding-bottom: 10px;
  color: #0d6efd;
}

.campos-editar {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.campo label {
  font-weight: 600;
  font-size: 0.9rem;
  color: #333;
}

.campo input,
.campo select {
  padding: 8px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
}

.campo input:focus,
.campo select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
  outline: none;
}

.botones-acciones {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.botones-acciones button {
  padding: 10px 20px;
  font-weight: 600;
}

@media (max-width: 1200px) {
  .print-preview {
    flex-direction: column;
    align-items: center;
  }

  .acciones-preview {
    width: 100%;
    position: static;
  }
}
</style>
