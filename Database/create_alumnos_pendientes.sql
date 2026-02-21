-- ============================================
-- TABLA: ALUMNOS PENDIENTES (PRE-REGISTRO)
-- ============================================

CREATE TABLE IF NOT EXISTS alumnos_pendientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    nombre VARCHAR(50) NOT NULL,
    apellidoP VARCHAR(50) NOT NULL,
    apellidoM VARCHAR(50) NOT NULL,
    numeroControl CHAR(8) NOT NULL,
    telefono CHAR(10),
    
    carrera_id INT,
    semestre_id INT,
    club_solicitado_id INT,
    
    -- Se recomienda agregar esta columna para no perder las opciones al recargar
    -- Si ya creó la tabla puede correr: ALTER TABLE alumnos_pendientes ADD COLUMN opciones_nombres VARCHAR(255);
    opciones_nombres VARCHAR(255),
    
    estado ENUM('PENDIENTE','APROBADO','RECHAZADO') DEFAULT 'PENDIENTE',
    motivo_rechazo TEXT,
    
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_validacion TIMESTAMP NULL,
    
    FOREIGN KEY (carrera_id) REFERENCES carreras(id),
    FOREIGN KEY (club_solicitado_id) REFERENCES clubs(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================
-- TRIGGER: VALIDAR/FECHA (BEFORE UPDATE)
-- ============================================
DELIMITER $$
DROP TRIGGER IF EXISTS trg_alumnos_pendientes_before$$
CREATE TRIGGER trg_alumnos_pendientes_before
BEFORE UPDATE ON alumnos_pendientes
FOR EACH ROW
BEGIN
    -- Si cambia de PENDIENTE a APROBADO o RECHAZADO, fijar la fecha de validación
    IF NEW.estado IN ('APROBADO', 'RECHAZADO') AND OLD.estado = 'PENDIENTE' THEN
        SET NEW.fecha_validacion = NOW();
    END IF;
END$$
DELIMITER ;


-- ============================================
-- TRIGGER: MIGRAR A ALUMNOS (AFTER UPDATE)
-- ============================================
DELIMITER $$
DROP TRIGGER IF EXISTS trg_alumnos_pendientes_after$$
CREATE TRIGGER trg_alumnos_pendientes_after
AFTER UPDATE ON alumnos_pendientes
FOR EACH ROW
BEGIN
    -- Si es aprobado y no lo estaba antes
    IF NEW.estado = 'APROBADO' AND OLD.estado <> 'APROBADO' THEN
        INSERT INTO alumnos (
            nombre,
            apellidoP,
            apellidoM,
            numeroControl,
            telefono,
            carrera_id,
            semestre_id,
            id_club,
            fecha_registro
        )
        VALUES (
            NEW.nombre,
            NEW.apellidoP,
            NEW.apellidoM,
            NEW.numeroControl,
            NEW.telefono,
            NEW.carrera_id,
            NEW.semestre_id,
            NEW.club_solicitado_id,
            CURDATE()
        );
    END IF;
END$$
DELIMITER ;
