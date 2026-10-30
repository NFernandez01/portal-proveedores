import { describe, expect, it } from 'vitest'
import { formatoFecha, formatoPesos } from '../formato'

describe('formato', () => {
  it('formatea fecha ISO a dd/mm/aaaa', () => {
    expect(formatoFecha('2026-10-25')).toBe('25/10/2026')
  })
  it('formatea pesos argentinos', () => {
    // Intl usa un espacio especial; comparamos sin espacios
    expect(formatoPesos('1500.5').replace(/\s/g, '')).toBe('$1.500,50')
  })
})