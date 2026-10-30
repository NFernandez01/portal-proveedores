export type Rol = 'admin' | 'proveedor'

export interface Usuario {
  id: number
  email: string
  rol: Rol
  proveedor_id: number | null
  razon_social: string | null
}

export type EstadoOrden = 'abierta' | 'facturada' | 'cerrada'
export type EstadoFactura = 'pendiente' | 'aprobada' | 'rechazada'

export interface Factura {
  id: number
  orden_id: number
  orden_numero: string
  razon_social: string
  tipo: 'A' | 'B' | 'C'
  punto_venta: number
  numero: number
  fecha: string
  importe: string
  estado: EstadoFactura
  motivo_rechazo: string | null
}

export interface Orden {
  id: number
  numero: string
  fecha: string
  total: string
  saldo: string
  estado: EstadoOrden
  razon_social: string
  facturas?: Factura[]
}

export interface Pagina<T> {
  items: T[]
  total: number
  pagina: number
  porPagina: number
}

/** Respuesta de GET /api/dashboard: cantidad e importe de facturas por estado */
export interface Resumen {
  facturas: Record<EstadoFactura, { cantidad: number; importe: string }>
}

/** Formato de error que devuelve la API */
export interface ErrorApi {
  error: string
  errores?: Record<string, string>
}