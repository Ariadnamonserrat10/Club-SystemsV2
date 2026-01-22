<template>
  <div>
    <h3>Constancias</h3>

    <div
      v-for="(club, index) in clubs"
      :key="club.nombre + index"
      class="card mb-4 shadow-sm"
    >
      <div
        class="card-header bg-primary text-white d-flex justify-content-between align-items-center"
      >
        <div>
          {{ club.nombre }} — Monitor: {{ club.monitor || "Sin asignar" }}
        </div>
        <div class="d-flex gap-2">
          <button
            class="btn btn-sm btn-light"
            @click="openConstanciaPreview(club)"
          >
            Vista previa
          </button>
        </div>
      </div>
      <div class="card-body">
        <p>{{ club.descripcion }}</p>

        <div
          v-if="filteredAlumnos(club.nombre).length === 0"
          class="text-muted"
        >
          No hay alumnos en este club.
        </div>

        <table v-else class="table table-bordered align-middle">
          <thead class="table-secondary">
            <tr>
              <th>Alumno</th>
              <th
                v-for="fecha in fechasData.length ? fechasData : fechas"
                :key="fecha"
              >
                {{ fecha }}
              </th>
              <th>Constancia</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(alumno, i) in filteredAlumnos(club.nombre)"
              :key="'c-' + i"
            >
              <td>
                {{ alumno.nombre }} {{ alumno.apellidoP }}
                {{ alumno.apellidoM }}
              </td>
              <td
                v-for="fecha in fechasData.length ? fechasData : fechas"
                :key="fecha"
                class="text-center"
              >
                <span
                  v-if="alumno.asistencias?.[fecha]"
                  class="text-success fw-bold"
                  style="font-size: 1.5rem"
                  >✔</span
                >
                <span
                  v-else
                  class="text-danger fw-bold"
                  style="font-size: 1.5rem"
                  >✖</span
                >
                <tr
                  v-for="(alumno, i) in filteredAlumnos(club.nombre)"
                  :key="'c-' + i"
                >
                  <td>
                    {{ alumno.nombre }} {{ alumno.apellidoP }}
                    {{ alumno.apellidoM }}
                  </td>
                  <td v-for="fecha in fechas" :key="fecha">
                    <span
                      v-if="alumno.asistencias?.[fecha]"
                      class="text-success fw-bold"
                      >✔</span
                    >
                    <span v-else class="text-danger fw-bold">✖</span>
                  </td>
                  <td>
                    <span
                      :class="
                        alumno.faltas <= 2 ? 'text-success' : 'text-danger'
                      "
                    >
                      {{ alumno.faltas <= 2 ? "Sí" : "No" }}
                    </span>
                  </td>
                  <td>
                    <button
                      class="btn btn-sm btn-outline-info"
                      @click="
                        descargarConstancia({
                          estudianteNombre:
                            `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim(),
                          numeroControl: alumno.control,
                          carrera:
                            alumno.carrera ||
                            'INGENIERÍA EN SISTEMAS COMPUTACIONALES',
                          club: (
                            alumno.club ||
                            club.nombre ||
                            ''
                          ).toLowerCase(),
                          desempeno: (
                            alumno.desempeno ||
                            (alumno.faltas <= 1
                              ? 'EXCELENTE'
                              : alumno.faltas === 2
                                ? 'BUENO'
                                : 'REGULAR')
                          ).toString(),
                          periodo: alumno.periodo || periodoActual,
                        })
                      "
                    >
                      Descargar
                    </button>
                  </td>
                </tr>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <button class="btn btn-outline-primary" @click="downloadAll">
      Descargar todas las constancias
    </button>

    <!-- Modal/Vista previa imprimible -->
    <div v-if="previewData" class="print-preview">
      <div class="preview-documento">
        <div class="constancia A4" id="constancia">
          <!-- ENCABEZADO CON TABLA Y LOGO -->
          <table
            class="tabla-encabezado"
            cellpadding="8"
            cellspacing="0"
            style="
              width: 100%;
              border-collapse: collapse;
              font-family: Arial, sans-serif;
              font-size: 9pt;
            "
          >
            <tr>
              <!-- Logo -->
              <td
                rowspan="2"
                style="
                  border: 1px solid;
                  width: 90px;
                  text-align: center;
                  vertical-align: middle;
                "
              >
                <img
                  src="../Img/Logo.jpg"
                  alt="Logo"
                  style="max-width: 70px; height: auto"
                />
              </td>

              <!-- Título -->
              <td style="border: 1px solid #000; vertical-align: middle">
                Constancia de cumplimiento de actividad Cultural y/o Deportiva
              </td>

              <!-- Codigo / Revision / Pagina -->
              <td
                rowspan="2"
                style="
                  border: 1px solid #000;
                  width: 32%;
                  vertical-align: top;
                  padding: 0;
                "
              >
                <table
                  style="width: 100%; border-collapse: collapse; font-size: 8pt"
                >
                  <tr>
                    <td
                      style="
                        border-bottom: 1px solid black;
                        padding: 6px;
                        white-space: nowrap;
                      "
                    >
                      <strong>Codigo:TecNM-VI-PO-003-05</strong>
                    </td>
                  </tr>
                  <tr>
                    <td style="border-bottom: 1px solid black; padding: 6px">
                      Revision: 0
                    </td>
                  </tr>
                  <tr>
                    <td style="padding: 6px">Pagina 1 de 1</td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <!-- Norma -->
              <td style="border: 1px solid; vertical-align: middle">
                Referencia a la Norma ISO 9001:2015&nbsp;&nbsp;&nbsp;8.1
              </td>
            </tr>
          </table>

          <h2
            class="titulo-constancia"
            style="font-size: 11pt; margin: 60px 0 15px 0"
          >
            CONSTANCIA DE CUMPLIMIENTO DE ACTIVIDAD CULTURAL Y/O DEPORTIVA
          </h2>

          <div class="espacios-mediano"></div>

          <div class="cuerpo">
            <p
              class="destinatario"
              style="font-size: 10pt; margin: 0 0 20px 0; line-height: 1.6"
            >
              C. BLANCA ANSELMA CASTRO CASTRO<br />
              JEFA DEL DEPARTAMENTO DE SERVICIOS ESCOLARES<br />
              PRESENTE
            </p>

            <div class="espacios"></div>

            <p class="texto justificado">
              La que suscribe Olimpia Cruz Reyes, Jefa del Departamento de
              Actividades Extraescolares, por este medio se permite hacer de su
              conocimiento que la estudiante
              <strong>{{ toUpper(previewData.estudianteNombre) }}</strong> con
              número de control
              <strong>{{ toUpper(previewData.numeroControl) }}</strong> de la
              carrera de <strong>{{ toUpper(previewData.carrera) }}</strong
              >, ha cumplido su actividad extraescolar en el club de
              <strong>{{ toUpper(previewData.club) }}</strong> con el nivel de
              desempeño <strong>{{ toUpper(previewData.desempeno) }}</strong> y
              un valor numérico de
              <strong>{{ desempenoValor(previewData.desempeno) }}</strong>
              durante el periodo escolar
              <strong>{{ toUpper(previewData.periodo) }}</strong
              >, con un valor curricular de 1 crédito.
            </p>

            <div class="espacios"></div>

            <p class="lugar-fecha">
              Se extiende la presente en la Heroica ciudad de Tlaxiaco a los
              {{ fechaHoy.dia }} días del mes de {{ fechaHoy.mes }} de
              {{ fechaHoy.anio }}.
            </p>

            <div class="espacios-firma"></div>

            <table
              class="tabla-firmas"
              style="width: 100%; border-collapse: collapse"
            >
              <tr>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
                  ATENTAMENTE
                </td>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
                  Vo. Bo.
                </td>
              </tr>
              <tr>
                <td colspan="2" class="espacios-firma-grandes"></td>
              </tr>
              <tr>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
                  <div class="linea-firma"></div>
                  <div class="nombre-firma">FERNANDO JAIR MENDOZA JIMENEZ</div>
                  <div class="cargo-firma">
                    JEFE DE LA OFICINA DE PROMOCIÓN DEPORTIVA
                  </div>
                </td>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
                  <div class="linea-firma"></div>
                  <div class="nombre-firma">OLIMPIA CRUZ REYES</div>
                  <div class="cargo-firma">
                    JEFA DEL DEPTO. DE ACTIVIDADES EXTRAESCOLARES
                  </div>
                </td>
              </tr>
            </table>

            <div class="pie-pagina">
              c.c.p. Jefe (a) de Departamento Correspondiente
            </div>

            <table class="tabla-pie">
              <tr>
                <td class="pie-izq">TecNM-VI-PO-003-05</td>
                <td class="pie-der">Rev. 0</td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <div class="acciones-preview">
        <h5 class="mb-3">Editar Constancia</h5>
        <div class="campos-editar">
          <div class="campo">
            <label>Período:</label>
            <input
              v-model="previewData.periodo"
              type="text"
              class="form-control form-control-sm"
            />
          </div>
          <div class="campo">
            <label>Desempeño:</label>
            <select
              v-model="previewData.desempeno"
              class="form-select form-select-sm"
            >
              <option value="EXCELENTE">EXCELENTE</option>
              <option value="BUENO">BUENO</option>
              <option value="REGULAR">REGULAR</option>
              <option value="INSUFICIENTE">INSUFICIENTE</option>
            </select>
          </div>
          <div class="campo">
            <label>Tipo de club:</label>
            <select
              v-model="previewData.tipoActividad"
              class="form-select form-select-sm"
            >
              <option value="CULTURAL">CULTURAL</option>
              <option value="DEPORTIVA">DEPORTIVA</option>
            </select>
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
</template>

<script>
import { getAsistenciasPorClub } from '../services/api';

export default {
  name: "Constancias",
  props: ["clubs", "alumnos", "fechas"],
  data() {
    return {
      previewData: null,
      periodoActual: this.getPeriodoActual(),
      alumnosPorClub: {}, // Objeto que almacena alumnos por club_id
      fechasData: [] // Fechas desde BD
    };
  },
  computed: {
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
    async loadAsistenciasPorClubs() {
      try {
        // Cargar asistencias para cada club
        for (const club of (this.clubs || [])) {
          if (club.id) {
            try {
              const data = await getAsistenciasPorClub(club.id);
              this.alumnosPorClub[club.id] = Array.isArray(data.alumnos) ? data.alumnos : [];

              // Agregar fechas únicas
              if (Array.isArray(data.fechas)) {
                this.fechasData = [...new Set([...this.fechasData, ...data.fechas])].sort();
              }

              // Mapear asistencias
              const asist = data.asistencias || {};
              this.alumnosPorClub[club.id] = (this.alumnosPorClub[club.id] || []).map(al => {
                const map = { ...(asist[al.id] || {}) };
                const faltas = Object.values(map).filter(v => v === false).length;
                return { ...al, asistencias: map, faltas };
              });
            } catch (e) {
              console.error(`Error cargando asistencias para club ${club.id}:`, e);
              this.alumnosPorClub[club.id] = [];
            }
          }
        }
      } catch (e) {
        console.error('Error cargando asistencias por clubs:', e);
      }
    },
    filteredAlumnos(clubName) {
      // Buscar el club por nombre
      const club = (this.clubs || []).find(c => c.nombre === clubName);

      // Si tenemos datos de BD para este club, usarlos
      if (club && club.id && this.alumnosPorClub[club.id]) {
        return this.alumnosPorClub[club.id];
      }

      // Fallback a props
      return (this.alumnos || []).filter(a => a.club === clubName);
    },
    downloadAll() {
      const rows = [];
      for (const club of this.clubs) {
        const alumnos = this.filteredAlumnos(club.nombre);
        for (const a of alumnos) {
          rows.push([
            club.nombre,
            a.nombre + " " + a.apellidoP + " " + (a.apellidoM || ""),
            a.control,
            a.faltas,
          ]);
        }
      }
      const csv = rows
        .map((r) =>
          r.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(","),
        )
        .join("\n");
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.setAttribute("href", url);
      link.setAttribute("download", "constancias.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      this.$emit("log", {
        usuario: "Usuario Oficina",
        accion: "Exportar",
        tipo: "constancias",
        descripcion: "Descarga de constancias",
      });
    },
    desempenoValor(desempeno) {
      const mapa = { EXCELENTE: 4, BUENO: 3, REGULAR: 2, DEFICIENTE: 1 };
      return mapa[(desempeno || "").toUpperCase()] || 0;
    },
    getPeriodoActual() {
      const f = new Date();
      const mes = f.getMonth();
      const anio = f.getFullYear();
      return mes >= 0 && mes <= 5
        ? `Enero-Junio ${anio}`
        : `Agosto-Diciembre ${anio}`;
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
    tipoActividad(nombreClub) {
      return this.isCulturalName(nombreClub) ? "CULTURAL" : "DEPORTIVA";
    },
    openConstanciaPreview(club) {
      this.previewData = {
        estudianteNombre: "NOMBRE DEL ESTUDIANTE",
        numeroControl: "00000000",
        carrera: "INGENIERÍA EN SISTEMAS COMPUTACIONALES",
        club: (club?.nombre || "club").toLowerCase(),
        desempeno: "EXCELENTE",
        periodo: this.periodoActual,
        tipoActividad: this.tipoActividad(club?.nombre),
      };
    },
    printConstancia(data) {
      this.previewData = {
        tipoActividad: this.tipoActividad(data.club),
        ...data,
      };
    },
    descargarConstancia(data) {
      this.previewData = {
        tipoActividad: this.tipoActividad(data.club),
        ...data,
      };
    },
    generarPDF() {
      const nodo = document.getElementById("constancia");
      if (!nodo || !this.previewData) return;

      const nombre = `${this.previewData.estudianteNombre.replace(/\s+/g, "_")}_${this.previewData.numeroControl}`;
      const opt = {
        margin: [15, 15, 15, 15],
        filename: `Constancia_${nombre}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: "portrait", unit: "mm", format: "letter" },
      };

      // Cargar html2pdf desde CDN
      if (typeof html2pdf === "undefined") {
        const script = document.createElement("script");
        script.src =
          "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
        document.head.appendChild(script);
        script.onload = () => {
          html2pdf().set(opt).from(nodo).save();
        };
      } else {
        html2pdf().set(opt).from(nodo).save();
      }
    },
    toUpper(v) {
      return (v == null ? "" : String(v)).toUpperCase();
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
    }
  },
  watch: {
    clubs: {
      handler() {
        this.loadAsistenciasPorClubs();
      },
      deep: true
    }
  },
  mounted() {
    this.loadAsistenciasPorClubs();
  }
};
</script>

<style scoped>
.print-preview {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  gap: 20px;
  align-items: flex-start;
  justify-content: flex-start;
  padding: 20px;
  z-index: 1000;
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
  height: 20px;
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
  border-top: 1px solid #000;
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
