import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusBadge from '../StatusBadge.vue'

describe('StatusBadge', () => {
  it('muestra el estado con mayúscula inicial', () => {
    const w = mount(StatusBadge, { props: { estado: 'pendiente' } })
    expect(w.text()).toBe('Pendiente')
  })

  it('usa el color verde para aprobada y rojo para rechazada', () => {
    expect(mount(StatusBadge, { props: { estado: 'aprobada' } }).classes()).toContain('bg-green-100')
    expect(mount(StatusBadge, { props: { estado: 'rechazada' } }).classes()).toContain('bg-red-100')
  })
})