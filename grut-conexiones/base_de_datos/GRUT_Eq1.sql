CREATE DATABASE IF NOT EXISTS transito_escolar
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE transito_escolar;

CREATE TABLE roles (
  id_roles     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(50)  NOT NULL,
  descripcion  VARCHAR(255) NULL
);

INSERT INTO roles (nombre, descripcion) VALUES
('Administrador', 'Control total del sistema'),
('Tutor', 'Padre o encargado del estudiante'),
('Estudiante', 'Usuario que recibe el servicio de transporte'),
('Conductor', 'Encargado de conducir el bus'),
('Coordinador', 'Coordina las rutas y horarios'),
('Supervisor', 'Supervisa el cumplimiento de las rutas'),
('Soporte', 'Soporte tecnico del sistema'),
('Finanzas', 'Encargado de la facturacion'),
('Secretaria', 'Encargada de tramites administrativos'),
('Director', 'Director del centro educativo');

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
('Maria', 'Gonzalez', 'maria.gonzalez@correo.com', '88451234', 'hashed_pw_1'),
('Carlos', 'Ramirez', 'carlos.ramirez@correo.com', '88451235', 'hashed_pw_2'),
('Ana', 'Fernandez', 'ana.fernandez@correo.com', '88451236', 'hashed_pw_3'),
('Jose', 'Mora', 'jose.mora@correo.com', '88451237', 'hashed_pw_4'),
('Laura', 'Jimenez', 'laura.jimenez@correo.com', '88451238', 'hashed_pw_5'),
('Pedro', 'Vargas', 'pedro.vargas@correo.com', '88451239', 'hashed_pw_6'),
('Sofia', 'Castro', 'sofia.castro@correo.com', '88451240', 'hashed_pw_7'),
('Diego', 'Solano', 'diego.solano@correo.com', '88451241', 'hashed_pw_8'),
('Valeria', 'Rojas', 'valeria.rojas@correo.com', '88451242', 'hashed_pw_9'),
('Andres', 'Chacon', 'andres.chacon@correo.com', '88451243', 'hashed_pw_10');

CREATE TABLE buses (
  id_bus     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  placa      VARCHAR(20) NOT NULL,
  capacidad  SMALLINT UNSIGNED NOT NULL,
  estado     VARCHAR(30) NULL,
  UNIQUE KEY uq_buses_placa (placa)
);

INSERT INTO buses (placa, capacidad, estado) VALUES
('SJB-001', 40, 'activo'),
('SJB-002', 35, 'activo'),
('SJB-003', 42, 'mantenimiento'),
('SJB-004', 30, 'activo'),
('SJB-005', 45, 'activo'),
('SJB-006', 38, 'inactivo'),
('SJB-007', 40, 'activo'),
('SJB-008', 32, 'activo'),
('SJB-009', 36, 'mantenimiento'),
('SJB-010', 44, 'activo');

CREATE TABLE paradas (
  id_parada  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  direccion  VARCHAR(255) NULL,
  latitud    DECIMAL(10,7) NULL,
  longitud   DECIMAL(10,7) NULL
);

INSERT INTO paradas (nombre, direccion, latitud, longitud) VALUES
('Parada Central', 'Av. Central, San Jose', 9.9325000, -84.0795600),
('Parada Norte', 'Barrio Norte, San Jose', 9.9450000, -84.0700000),
('Parada Sur', 'Barrio Sur, San Jose', 9.9200000, -84.0850000),
('Parada Escuela Roble', 'Calle Roble', 9.9300000, -84.0600000),
('Parada Plaza Mayor', 'Plaza Mayor', 9.9350000, -84.0900000),
('Parada Rio Frio', 'Sector Rio Frio', 9.9100000, -84.0750000),
('Parada Los Angeles', 'Barrio Los Angeles', 9.9500000, -84.0650000),
('Parada El Carmen', 'Barrio El Carmen', 9.9280000, -84.0820000),
('Parada Santa Lucia', 'Sector Santa Lucia', 9.9400000, -84.0780000),
('Parada Vista Hermosa', 'Barrio Vista Hermosa', 9.9150000, -84.0680000);

CREATE TABLE estudiante (
  id_estudiante     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario        INT UNSIGNED NOT NULL,
  curso_educacion   VARCHAR(50)  NULL,
  centro_educativo  VARCHAR(150) NULL,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

INSERT INTO estudiante (id_usuario, curso_educacion, centro_educativo) VALUES
(1, '5to grado', 'Escuela Central'),
(1, '3ro grado', 'Escuela Central'),
(2, '6to grado', 'Escuela San Rafael'),
(2, '4to grado', 'Escuela San Rafael'),
(3, '2do grado', 'Escuela Roble'),
(3, '1ro grado', 'Escuela Roble'),
(4, '7mo grado', 'Colegio Vista Hermosa'),
(5, '8vo grado', 'Colegio Vista Hermosa'),
(6, '5to grado', 'Escuela Santa Lucia'),
(7, '3ro grado', 'Escuela Santa Lucia');

CREATE TABLE conductor (
  id_conductor  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario    INT UNSIGNED NOT NULL,
  licencia      VARCHAR(50) NOT NULL,
  UNIQUE KEY uq_conductor_usuario (id_usuario),
  UNIQUE KEY uq_conductor_licencia (licencia),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

INSERT INTO conductor (id_usuario, licencia) VALUES
(1, 'LIC-0001'),
(2, 'LIC-0002'),
(3, 'LIC-0003'),
(4, 'LIC-0004'),
(5, 'LIC-0005'),
(6, 'LIC-0006'),
(7, 'LIC-0007'),
(8, 'LIC-0008'),
(9, 'LIC-0009'),
(10, 'LIC-0010');

CREATE TABLE usuario_rol (
  id_usuario        INT UNSIGNED NOT NULL,
  id_roles          INT UNSIGNED NOT NULL,
  fecha_asignacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_roles),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  FOREIGN KEY (id_roles) REFERENCES roles(id_roles)
);

INSERT INTO usuario_rol (id_usuario, id_roles, fecha_asignacion) VALUES
(1, 1, '2025-01-10 08:00:00'),
(2, 2, '2025-01-10 08:05:00'),
(3, 2, '2025-01-11 08:00:00'),
(4, 3, '2025-01-11 08:05:00'),
(5, 4, '2025-01-12 08:00:00'),
(6, 4, '2025-01-12 08:05:00'),
(7, 5, '2025-01-13 08:00:00'),
(8, 6, '2025-01-13 08:05:00'),
(9, 8, '2025-01-14 08:00:00'),
(10, 9, '2025-01-14 08:05:00');

CREATE TABLE rutas (
  id_ruta       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(100) NOT NULL,
  descripcion   VARCHAR(255) NULL,
  id_conductor  INT UNSIGNED NULL,
  FOREIGN KEY (id_conductor) REFERENCES conductor(id_conductor)
);

INSERT INTO rutas (nombre, descripcion, id_conductor) VALUES
('Ruta Norte', 'Recorrido zona norte', 1),
('Ruta Sur', 'Recorrido zona sur', 2),
('Ruta Central', 'Recorrido zona central', 3),
('Ruta Este', 'Recorrido zona este', 4),
('Ruta Oeste', 'Recorrido zona oeste', 5),
('Ruta Roble', 'Recorrido barrio Roble', 6),
('Ruta Vista Hermosa', 'Recorrido Vista Hermosa', 7),
('Ruta Santa Lucia', 'Recorrido Santa Lucia', 8),
('Ruta El Carmen', 'Recorrido El Carmen', 9),
('Ruta Rio Frio', 'Recorrido Rio Frio', 10);

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
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8),
(9, 9),
(10, 10);

CREATE TABLE estudiante_ruta (
  id_estudiante_ruta  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_estudiante       INT UNSIGNED NOT NULL,
  id_ruta             INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_estudiante_ruta (id_estudiante, id_ruta),
  FOREIGN KEY (id_estudiante) REFERENCES estudiante(id_estudiante),
  FOREIGN KEY (id_ruta) REFERENCES rutas(id_ruta)
);

INSERT INTO estudiante_ruta (id_estudiante, id_ruta) VALUES
(1, 1),
(2, 1),
(3, 2),
(4, 2),
(5, 3),
(6, 3),
(7, 4),
(8, 5),
(9, 6),
(10, 7);

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
(1, 1, 1),
(2, 2, 1),
(3, 3, 1),
(4, 4, 1),
(5, 5, 1),
(6, 6, 1),
(7, 7, 1),
(8, 8, 1),
(9, 9, 1),
(10, 10, 1);

CREATE TABLE horarios (
  id_horario      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_ruta_parada  INT UNSIGNED NOT NULL,
  dia_semana      ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  hora_salida     TIME NOT NULL,
  hora_llegada    TIME NOT NULL,
  FOREIGN KEY (id_ruta_parada) REFERENCES ruta_parada(id_ruta_parada)
);

INSERT INTO horarios (id_ruta_parada, dia_semana, hora_salida, hora_llegada) VALUES
(1, 'lunes', '06:00:00', '06:45:00'),
(2, 'martes', '06:10:00', '06:55:00'),
(3, 'miercoles', '06:05:00', '06:50:00'),
(4, 'jueves', '06:15:00', '07:00:00'),
(5, 'viernes', '06:20:00', '07:05:00'),
(6, 'lunes', '06:25:00', '07:10:00'),
(7, 'martes', '06:30:00', '07:15:00'),
(8, 'miercoles', '06:35:00', '07:20:00'),
(9, 'jueves', '06:40:00', '07:25:00'),
(10, 'viernes', '06:45:00', '07:30:00');

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
(1, '2025-01-01', '2025-01-15', 25000.00, 'pagado', 'transferencia', 'Mensualidad enero'),
(2, '2025-01-01', '2025-01-15', 25000.00, 'pendiente', 'efectivo', 'Mensualidad enero'),
(3, '2025-01-01', '2025-01-15', 25000.00, 'pagado', 'tarjeta', 'Mensualidad enero'),
(4, '2025-01-01', '2025-01-15', 25000.00, 'vencido', 'efectivo', 'Mensualidad enero'),
(5, '2025-02-01', '2025-02-15', 25000.00, 'pagado', 'transferencia', 'Mensualidad febrero'),
(6, '2025-02-01', '2025-02-15', 25000.00, 'pendiente', 'efectivo', 'Mensualidad febrero'),
(7, '2025-02-01', '2025-02-15', 25000.00, 'pagado', 'tarjeta', 'Mensualidad febrero'),
(8, '2025-02-01', '2025-02-15', 25000.00, 'pagado', 'transferencia', 'Mensualidad febrero'),
(9, '2025-03-01', '2025-03-15', 25000.00, 'pendiente', 'efectivo', 'Mensualidad marzo'),
(10, '2025-03-01', '2025-03-15', 25000.00, 'pagado', 'tarjeta', 'Mensualidad marzo');
