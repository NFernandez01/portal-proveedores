-- pgcrypto permite generar hashes bcrypt desde SQL (compatibles con password_verify de PHP)
CREATE EXTENSION IF NOT EXISTS pgcrypto;

INSERT INTO proveedores (razon_social, cuit, email) VALUES
    ('Insumos del Sur S.A.',   '30712345671', 'contacto@insumosdelsur.test'),
    ('Logística Norte S.R.L.', '30654321090', 'admin@logisticanorte.test');

INSERT INTO usuarios (email, password_hash, rol, proveedor_id) VALUES
    ('admin@portal.test',      crypt('Demo1234!', gen_salt('bf', 12)), 'admin',     NULL),
    ('proveedor1@portal.test', crypt('Demo1234!', gen_salt('bf', 12)), 'proveedor', 1),
    ('proveedor2@portal.test', crypt('Demo1234!', gen_salt('bf', 12)), 'proveedor', 2);

INSERT INTO ordenes_compra (numero, proveedor_id, fecha, total) VALUES
    ('OC-0001', 1, '2026-10-01', 150000.00),
    ('OC-0002', 1, '2026-10-10',  82500.50),
    ('OC-0003', 1, '2026-10-20',  40000.00),
    ('OC-0004', 2, '2026-10-05', 230000.00),
    ('OC-0005', 2, '2026-10-18',  61200.00);