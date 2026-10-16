-- =====================================================================
-- GRUT - base de datos "transito_escolar"
-- Estructura (tablas, claves primarias y foráneas) + datos de prueba.
--
-- Datos de prueba: todas las cuentas tienen la contraseña  Grut2026
--   Administrador: admin@grut.com
--   Conductores:   juan.perez@grut.com, maria.campos@grut.com, jose.aguilar@grut.com
--   Estudiantes:   ana.martinez@correo.com, luis.hernandez@correo.com,
--                  sofia.ramirez@correo.com, diego.flores@correo.com,
--                  valeria.rivas@correo.com, carlos.mejia@correo.com
--   (carlos.mejia no tiene rutas: sirve para ver el mensaje "Aún no tienes rutas asignadas")
--
-- OJO: la primera línea BORRA la base si ya existe, para poder importar de nuevo.
-- Los usuarios que hayas registrado a mano en tu XAMPP se pierden.
-- =====================================================================
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS transito_escolar;

CREATE DATABASE IF NOT EXISTS transito_escolar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE transito_escolar;

CREATE TABLE roles (
  id_roles     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(50)  NOT NULL,
  descripcion  VARCHAR(255) NULL
);

-- Solo existen los 3 roles que tienen panel. Los id van escritos para que no
-- cambien: el LEEME y el codigo usan 1 = Administrador, 3 = Estudiante, 4 = Conductor.
INSERT INTO roles (id_roles, nombre, descripcion) VALUES
(1, 'Administrador', 'Control total del sistema'),
(3, 'Estudiante', 'Usuario que recibe el servicio de transporte'),
(4, 'Conductor', 'Encargado de conducir el bus');

CREATE TABLE usuario (
  id_usuario   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(100) NOT NULL,
  apellido     VARCHAR(100) NOT NULL,
  correo       VARCHAR(150) NOT NULL,
  telefono     VARCHAR(20)  NULL,
  contrasena   VARCHAR(255) NOT NULL,
  activo       TINYINT(1)   NOT NULL DEFAULT 1,
  UNIQUE KEY uq_usuario_correo (correo)
);

INSERT INTO usuario (nombre, apellido, correo, telefono, contrasena) VALUES
('Carmen', 'López', 'admin@grut.com', '2250-1000', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Juan', 'Pérez', 'juan.perez@grut.com', '7012-3456', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('María', 'Campos', 'maria.campos@grut.com', '7123-4567', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('José', 'Aguilar', 'jose.aguilar@grut.com', '7234-5678', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Ana', 'Martínez', 'ana.martinez@correo.com', '7345-6789', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Luis', 'Hernández', 'luis.hernandez@correo.com', '7456-7890', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Sofía', 'Ramírez', 'sofia.ramirez@correo.com', '6012-3456', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Diego', 'Flores', 'diego.flores@correo.com', '6123-4567', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Valeria', 'Rivas', 'valeria.rivas@correo.com', '7567-8901', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi'),
('Carlos', 'Mejía', 'carlos.mejia@correo.com', '7678-9012', '$2y$10$MoZmHFnKiIS7o0pemZludOPXTSHzivq6Vq5MY7Vb9B1WEipYE7TVi');

CREATE TABLE buses (
  id_bus     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  placa      VARCHAR(20) NOT NULL,
  capacidad  SMALLINT UNSIGNED NOT NULL,
  estado     VARCHAR(30) NULL,
  UNIQUE KEY uq_buses_placa (placa)
);

INSERT INTO buses (placa, capacidad, estado) VALUES
('AB-101-234', 40, 'activo'),
('AB-102-345', 35, 'activo'),
('AB-103-456', 42, 'activo'),
('AB-104-567', 30, 'mantenimiento'),
('AB-105-678', 45, 'inactivo');

CREATE TABLE paradas (
  id_parada  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  direccion  VARCHAR(255) NULL,
  latitud    DECIMAL(10,7) NULL,
  longitud   DECIMAL(10,7) NULL
);

INSERT INTO paradas (nombre, direccion, latitud, longitud) VALUES
('Metrocentro', 'Boulevard de Los Héroes, San Salvador', 13.7058000, -89.2124000),
('Redondel Masferrer', 'Colonia Escalón, San Salvador', 13.7096000, -89.2393000),
('Plaza Las Américas', 'Colonia Flor Blanca, San Salvador', 13.6990000, -89.2235000),
('Universidad Tecnológica (UTEC)', 'Calle Arce, San Salvador', 13.7003000, -89.1983000),
('Colegio Bautista', 'Alameda Juan Pablo II, San Salvador', 13.7072000, -89.2010000),
('Colegio San Patricio', 'Colonia San Benito, San Salvador', 13.6955000, -89.2405000),
('Universidad Don Bosco', 'Calle a Plan del Pino, Soyapango', 13.7150000, -89.1530000),
('Centro Comercial Plaza Mundo', 'Boulevard del Ejército, Soyapango', 13.7005000, -89.1490000),
('Centro Escolar Cantón Dolores Apulo', 'Cantón Dolores Apulo, Ilopango', 13.6760000, -89.0630000),
('Parque Central de Ilopango', 'Centro de Ilopango', 13.7016000, -89.1094000);

CREATE TABLE estudiante (
  id_estudiante     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario        INT UNSIGNED NOT NULL,
  curso_educacion   VARCHAR(50)  NULL,
  centro_educativo  VARCHAR(150) NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

INSERT INTO estudiante (id_usuario, curso_educacion, centro_educativo) VALUES
(5, 'Universitario', 'Universidad Tecnológica de El Salvador'),
(6, 'Bachillerato', 'Colegio Bautista'),
(7, 'Secundaria', 'Colegio San Patricio'),
(8, 'Universitario', 'Universidad Don Bosco'),
(9, 'Primaria', 'Centro Escolar Cantón Dolores Apulo'),
(10, 'Bachillerato', 'Colegio San Patricio');

CREATE TABLE conductor (
  id_conductor  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario    INT UNSIGNED NOT NULL,
  licencia      VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_conductor_usuario (id_usuario),
  UNIQUE KEY uq_conductor_licencia (licencia),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

INSERT INTO conductor (id_usuario, licencia) VALUES
(2, '0614-150385-101-1'),
(3, '0614-220790-102-5'),
(4, '0511-030588-103-2');

CREATE TABLE usuario_rol (
  id_usuario        INT UNSIGNED NOT NULL,
  id_roles          INT UNSIGNED NOT NULL,
  fecha_asignacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_roles),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  FOREIGN KEY (id_roles) REFERENCES roles(id_roles)
);

INSERT INTO usuario_rol (id_usuario, id_roles, fecha_asignacion) VALUES
(1, 1, '2026-01-10 08:00:00'),
(2, 4, '2026-01-10 08:05:00'),
(3, 4, '2026-01-10 08:10:00'),
(4, 4, '2026-01-10 08:15:00'),
(5, 3, '2026-01-12 09:00:00'),
(6, 3, '2026-01-12 09:05:00'),
(7, 3, '2026-01-12 09:10:00'),
(8, 3, '2026-01-12 09:15:00'),
(9, 3, '2026-01-12 09:20:00'),
(10, 3, '2026-01-12 09:25:00');

CREATE TABLE rutas (
  id_ruta       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  descripcion   VARCHAR(255) NULL,
  id_conductor  INT UNSIGNED NULL,
  FOREIGN KEY (id_conductor) REFERENCES conductor(id_conductor)
);

INSERT INTO rutas (nombre, descripcion, id_conductor) VALUES
('Ruta 15', 'Escalón - San Benito - Centro de San Salvador', 1),
('Ruta 41-A', 'Metrocentro - Colegio Bautista - UTEC', 2),
('Ruta 41-D', 'Soyapango - Ilopango', 3),
('Ruta 29', 'Ruta nueva, todavía sin conductor', NULL);

CREATE TABLE ruta_bus (
  id_ruta  INT UNSIGNED NOT NULL,
  id_bus   INT UNSIGNED NOT NULL,
  PRIMARY KEY (id_ruta, id_bus),
  FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta),
  FOREIGN KEY (id_bus) REFERENCES buses(id_bus)
);

INSERT INTO ruta_bus (id_ruta, id_bus) VALUES
(1, 1),
(2, 2),
(3, 3);

CREATE TABLE estudiante_ruta (
  id_estudiante_ruta  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_estudiante       INT UNSIGNED NOT NULL,
  id_ruta             INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_estudiante_ruta (id_estudiante, id_ruta),
  FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante),
  FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta)
);

INSERT INTO estudiante_ruta (id_estudiante, id_ruta) VALUES
(1, 2),
(2, 2),
(3, 1),
(4, 3),
(5, 3),
(1, 1);

CREATE TABLE ruta_parada (
  id_ruta_parada  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_ruta         INT UNSIGNED NOT NULL,
  id_parada       INT UNSIGNED NOT NULL,
  orden           SMALLINT UNSIGNED NOT NULL,
  UNIQUE KEY uq_ruta_parada (id_ruta, id_parada),
  FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta),
  FOREIGN KEY (id_parada) REFERENCES paradas(id_parada)
);

INSERT INTO ruta_parada (id_ruta, id_parada, orden) VALUES
(1, 2, 1),
(1, 6, 2),
(1, 3, 3),
(2, 1, 1),
(2, 5, 2),
(2, 4, 3),
(3, 8, 1),
(3, 7, 2),
(3, 10, 3),
(3, 9, 4);

CREATE TABLE horarios (
  id_horario      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_ruta_parada  INT UNSIGNED NOT NULL,
  dia_semana      ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  hora_salida     TIME NOT NULL,
  hora_llegada    TIME NOT NULL,
  FOREIGN KEY (id_ruta_parada) REFERENCES ruta_parada(id_ruta_parada)
);

INSERT INTO horarios (id_ruta_parada, dia_semana, hora_salida, hora_llegada) VALUES
(1, 'lunes', '06:00:00', '06:05:00'),
(1, 'miercoles', '06:00:00', '06:05:00'),
(1, 'viernes', '06:00:00', '06:05:00'),
(2, 'lunes', '06:20:00', '06:25:00'),
(2, 'miercoles', '06:20:00', '06:25:00'),
(2, 'viernes', '06:20:00', '06:25:00'),
(3, 'lunes', '06:40:00', '06:45:00'),
(4, 'lunes', '06:15:00', '06:20:00'),
(4, 'martes', '06:15:00', '06:20:00'),
(4, 'jueves', '06:15:00', '06:20:00'),
(5, 'lunes', '06:35:00', '06:40:00'),
(5, 'martes', '06:35:00', '06:40:00'),
(6, 'lunes', '06:55:00', '07:00:00'),
(7, 'lunes', '05:45:00', '05:50:00'),
(7, 'jueves', '05:45:00', '05:50:00'),
(8, 'lunes', '06:05:00', '06:10:00'),
(9, 'lunes', '06:25:00', '06:30:00'),
(10, 'lunes', '06:45:00', '06:50:00');

CREATE TABLE facturas (
  id_factura         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_estudiante      INT UNSIGNED NOT NULL,
  fecha_emision      DATE NOT NULL,
  fecha_vencimiento  DATE NULL,
  monto              DECIMAL(10,2) NOT NULL,
  estado_pago        VARCHAR(30) NULL,
  metodo_pago        VARCHAR(50) NULL,
  descripcion        VARCHAR(255) NULL,
  FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante)
);

INSERT INTO facturas (id_estudiante, fecha_emision, fecha_vencimiento, monto, estado_pago, metodo_pago, descripcion) VALUES
(1, '2026-08-01', '2026-08-15', 25.00, 'pagado', 'transferencia', 'Mensualidad agosto'),
(1, '2026-09-01', '2026-09-15', 25.00, 'pagado', 'tarjeta', 'Mensualidad septiembre'),
(1, '2026-10-01', '2026-10-15', 25.00, 'pendiente', NULL, 'Mensualidad octubre'),
(2, '2026-09-01', '2026-09-15', 25.00, 'vencido', NULL, 'Mensualidad septiembre'),
(2, '2026-10-01', '2026-10-15', 25.00, 'pendiente', NULL, 'Mensualidad octubre'),
(3, '2026-10-01', '2026-10-15', 25.00, 'pagado', 'efectivo', 'Mensualidad octubre'),
(4, '2026-10-01', '2026-10-15', 25.00, 'pendiente', NULL, 'Mensualidad octubre'),
(5, '2026-10-01', '2026-10-15', 25.00, 'pagado', 'transferencia', 'Mensualidad octubre');
