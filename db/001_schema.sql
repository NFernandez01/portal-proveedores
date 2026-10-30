CREATE TABLE proveedores (
    id           SERIAL PRIMARY KEY,
    razon_social VARCHAR(150) NOT NULL,
    cuit         CHAR(11)     NOT NULL UNIQUE,
    email        VARCHAR(150) NOT NULL
);

CREATE TABLE usuarios (
    id            SERIAL PRIMARY KEY,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol           VARCHAR(20)  NOT NULL CHECK (rol IN ('admin', 'proveedor')),
    proveedor_id  INT REFERENCES proveedores(id),
    -- un usuario proveedor SIEMPRE tiene que estar asociado a un proveedor
    CHECK (rol = 'admin' OR proveedor_id IS NOT NULL)
);

CREATE TABLE ordenes_compra (
    id           SERIAL PRIMARY KEY,
    numero       VARCHAR(20)   NOT NULL UNIQUE,
    proveedor_id INT           NOT NULL REFERENCES proveedores(id),
    fecha        DATE          NOT NULL,
    total        NUMERIC(14,2) NOT NULL CHECK (total > 0),
    estado       VARCHAR(20)   NOT NULL DEFAULT 'abierta'
                 CHECK (estado IN ('abierta', 'facturada', 'cerrada'))
);

CREATE TABLE facturas (
    id             SERIAL PRIMARY KEY,
    orden_id       INT           NOT NULL REFERENCES ordenes_compra(id),
    proveedor_id   INT           NOT NULL REFERENCES proveedores(id),
    tipo           CHAR(1)       NOT NULL CHECK (tipo IN ('A', 'B', 'C')),
    punto_venta    INT           NOT NULL CHECK (punto_venta > 0),
    numero         INT           NOT NULL CHECK (numero > 0),
    fecha          DATE          NOT NULL,
    importe        NUMERIC(14,2) NOT NULL CHECK (importe > 0),
    archivo        VARCHAR(255)  NOT NULL,
    estado         VARCHAR(20)   NOT NULL DEFAULT 'pendiente'
                   CHECK (estado IN ('pendiente', 'aprobada', 'rechazada')),
    motivo_rechazo TEXT,
    created_at     TIMESTAMPTZ   NOT NULL DEFAULT now(),
    -- la misma factura no se puede cargar dos veces
    UNIQUE (proveedor_id, tipo, punto_venta, numero)
);

-- Para el rate limit del login (día 3)
CREATE TABLE login_intentos (
    id     SERIAL PRIMARY KEY,
    ip     VARCHAR(45) NOT NULL,
    creado TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_ordenes_proveedor ON ordenes_compra (proveedor_id);
CREATE INDEX idx_facturas_orden    ON facturas (orden_id);
CREATE INDEX idx_facturas_estado   ON facturas (estado);
CREATE INDEX idx_login_ip_fecha    ON login_intentos (ip, creado);