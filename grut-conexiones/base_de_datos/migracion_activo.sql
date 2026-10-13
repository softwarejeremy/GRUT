-- TAREA 13: columna "activo" para poder desactivar cuentas.
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) en la base transito_escolar
-- si ya la tenías creada ANTES de este cambio.
-- Si importas GRUT_Eq1.sql desde cero, NO hace falta: ya trae la columna.
-- Es seguro ejecutarla más de una vez (XAMPP usa MariaDB, que soporta IF NOT EXISTS).
USE transito_escolar;

ALTER TABLE usuario
  ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1;

-- 1 = activo, 0 = desactivado. Los usuarios existentes quedan en 1.
