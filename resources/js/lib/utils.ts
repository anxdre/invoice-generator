import type { Updater } from '@tanstack/vue-table'
import type { Ref } from 'vue'
import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export function valueUpdater<T extends Updater<any>>(updaterOrValue: T, ref: Ref) {
    ref.value
        = typeof updaterOrValue === 'function'
        ? updaterOrValue(ref.value)
        : updaterOrValue
}

export function idrFormat(number: number) {
    if (number == 0 || undefined) {
        return 'Rp. 0,00'
    }

    if(isNaN(number)){
        return 'Rp. 0,00'
    }
    return Intl.NumberFormat("id-ID", { style: 'currency', currency: 'IDR' }).format(number)
}

export function dotFormat(num: string) {
    if (!num) return ''
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

export function formatDate(date: string) {
    return new Date(date).toLocaleDateString('id-ID')
}
