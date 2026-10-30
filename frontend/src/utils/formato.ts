const pesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' })

export const formatoPesos = (valor: string | number) => pesos.format(Number(valor))

/** "2026-10-25" -> "25/10/2026" (sin pasar por Date, para evitar corrimientos por zona horaria) */
export const formatoFecha = (iso: string) => iso.slice(0, 10).split('-').reverse().join('/')


/** { tipo: 'A', punto_venta: 1, numero: 1234 } → "A 0001-00001234" (formato de comprobante AFIP) */
export const formatoComprobante = (f: { tipo: string; punto_venta: number; numero: number }) =>
  `${f.tipo} ${String(f.punto_venta).padStart(4, '0')}-${String(f.numero).padStart(8, '0')}`