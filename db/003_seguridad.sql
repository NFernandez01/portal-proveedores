-- Migración 003 (idempotente: se puede correr más de una vez sin romper nada)

-- Punto 1: el rate limit del login cuenta por IP + email.
-- Antes, un login correcto borraba TODOS los intentos de la IP: con una cuenta propia
-- se podía "resetear" el contador y seguir probando contraseñas contra otra cuenta.
ALTER TABLE login_intentos ADD COLUMN IF NOT EXISTS email VARCHAR(150) NOT NULL DEFAULT '';
CREATE INDEX IF NOT EXISTS idx_login_ip_email_fecha ON login_intentos (ip, email, creado);

-- Punto 5: los emails se guardan siempre en minúsculas (lo garantiza la base, no solo PHP).
UPDATE usuarios SET email = lower(trim(email)) WHERE email <> lower(trim(email));
ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_email_minusculas;
ALTER TABLE usuarios ADD CONSTRAINT usuarios_email_minusculas CHECK (email = lower(email));
