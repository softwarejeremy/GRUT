-- Quitar el rol "Tutor" (ya no se usa en el proyecto).
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) en la base transito_escolar
-- si la creaste ANTES de este cambio. Si importas GRUT_Eq1.sql desde cero, NO hace falta.
-- Es seguro ejecutarla más de una vez: si el rol ya no existe, no cambia nada.
USE transito_escolar;

-- 1) Los usuarios con rol Tutor que tienen fila en "conductor" pasan a Conductor
--    (son los usuarios 2 y 3 de los datos de ejemplo).
UPDATE IGNORE usuario_rol ur
INNER JOIN conductor c ON c.id_usuario = ur.id_usuario
SET ur.id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Conductor')
WHERE ur.id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Tutor');

-- 2) A los demás tutores se les quita el rol (no podrán iniciar sesión
--    hasta que un administrador les asigne otro en usuario_rol).
DELETE FROM usuario_rol
WHERE id_roles = (SELECT id_roles FROM roles WHERE nombre = 'Tutor');

-- 3) Se borra el rol. Los demás roles conservan su número
--    (1 = Administrador, 3 = Estudiante, 4 = Conductor).
DELETE FROM roles WHERE nombre = 'Tutor';
