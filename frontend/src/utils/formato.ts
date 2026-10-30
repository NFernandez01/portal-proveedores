const pesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' })

export const formatoPesos = (valor: string | number) => pesos.format(Number(valor))

/** "2026-10-25" -> "25/10/2026" (sin pasar por Date, para evitar corrimientos por zona horaria) */
export const formatoFecha = (iso: string) => iso.slice(0, 10).split('-').reverse().join('/')