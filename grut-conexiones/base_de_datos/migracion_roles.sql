-- Dejar solo los 3 roles que tienen panel: Administrador (1), Estudiante (3) y Conductor (4).
-- Se quitan Tutor, Coordinador, Supervisor, Soporte, Finanzas, Secretaria y Director.
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) en la base transito_escolar
-- si la creaste ANTES de este cambio. Si importas GRUT_Eq1.sql desde cero, NO hace falta.
-- Es seguro ejecutarla más de una vez: si esos roles ya no existen, no cambia nada.
USE transito_escolar;

-- 1) Quien tenía uno de esos roles y tiene fila en "conductor" pasa a Conductor
--    (en los datos de ejemplo: usuarios 2, 3, 7, 8, 9 y 10).
UPDATE IGNORE usuario_rol ur
INNER JOIN conductor c ON c.id_usuario = ur.id_usuario
SET ur.id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Conductor')
WHERE ur.id_roles IN (SELECT id_roles FROM roles WHERE nombre NOT IN ('Administrador', 'Estudiante', 'Conductor'));

-- 2) Quien tiene fila en "estudiante" pasa a Estudiante.
UPDATE IGNORE usuario_rol ur
INNER JOIN estudiante e ON e.id_usuario = ur.id_usuario
SET ur.id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Estudiante')
WHERE ur.id_roles IN (SELECT id_roles FROM roles WHERE nombre NOT IN ('Administrador', 'Estudiante', 'Conductor'));

-- 3) A los demás se les quita ese rol (no podrán iniciar sesión hasta que
--    un administrador les asigne uno de los 3 roles en usuario_rol).
DELETE FROM usuario_rol
WHERE id_roles IN (SELECT id_roles FROM roles WHERE nombre NOT IN ('Administrador', 'Estudiante', 'Conductor'));

-- 4) Se borran los roles. Los que quedan conservan su número.
DELETE FROM roles WHERE nombre NOT IN ('Administrador', 'Estudiante', 'Conductor');
