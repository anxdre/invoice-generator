<script setup lang="ts">

import { CardContent, CardFooter } from "@/Components/ui/card";
import { ArrowLeft, CalendarIcon, Download, FilePlus2, FileSpreadsheet, Pencil, PlusCircleIcon, PrinterIcon, SaveIcon, TrashIcon, Upload } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "../../Components/ui/card/Card.vue";
import { Head, router } from "@inertiajs/vue3";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Popover, PopoverContent, PopoverTrigger } from "@/Components/ui/popover";
import { Calendar } from "@/Components/ui/calendar";
import { cn, dotFormat, idrFormat } from "@/lib/utils"
import { DateFormatter, getLocalTimeZone } from "@internationalized/date";
import { onMounted, ref, watch } from "vue";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import RichTextEditor from "@/Components/RichTextEditor.vue";
import axios from "axios";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue, } from '@/Components/ui/select'
import { toast } from "vue-sonner";
import Swal from "sweetalert2";

const props = defineProps({
    invoice: Object,
    category: String
})

const state = ref({ isEdit: true, canEdit: true })

const df = new DateFormatter('en-US', {
    dateStyle: 'long',
})

const invoiceData = ref({
    id: undefined,
    invoice_number: undefined,
    invoice_date: undefined,
    due_date: undefined,
    payment_number: undefined,
    payment_type: undefined,
    payment_date: undefined,
    to: undefined,
    recipient_address: undefined,
    total: 0,
    paid: null,
    tax: 0,
    category: undefined,
    total_payment: 0,
    status: 'draft',
    notes: undefined,
    invoice_details: []
})

const fileInput = ref(null)

function addItem() {
    invoiceData.value.invoice_details.push({
        id: undefined,
        invoice_id: invoiceData.value.invoice_id,
        item_name: undefined,
        item_code: undefined,
        item_price: 0,
        item_qty: 0,
        total_price: 0,
    })
    console.log(invoiceData.value.invoice_details)
}

function calculateTotal(amount: number, quantity: number) {
    return amount * quantity;
}

function displayDate(date) {
    if (!date) return ''
    if (typeof date.toDate === 'function') {
        return df.format(date.toDate(getLocalTimeZone()))
    }
    return date
}

function backendDate(date) {
    if (!date) return undefined
    if (typeof date.toDate === 'function') {
        return df.format(date.toDate(getLocalTimeZone()))
    }
    return date
}

function confirmSave(status = 'draft') {
    const isSubmit = status === 'submitted';
    Swal.fire({
        title: isSubmit ? "Submit this invoice?" : "Save as draft?",
        text: isSubmit ? "You won't be able to edit this after submission!" : "You can still edit this invoice before submitting it.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: isSubmit ? "Yes, submit it!" : "Yes, save it!",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            saveInvoice(status)
        }
    });
}

function saveInvoice(status = 'draft') {
    axios.post(route('invoices.store'), {
        ...invoiceData.value,
        status,
        date: backendDate(invoiceData.value.invoice_date),
        due_date: backendDate(invoiceData.value.due_date),
    })
        .then(res => {
            invoiceData.value.id = res.data.id;
            invoiceData.value.user_id = res.data.user_id;
            invoiceData.value.invoice_number = res.data.invoice_number;
            invoiceData.value.status = res.data.status;
            state.value.isEdit = false;
            state.value.canEdit = invoiceData.value.status !== 'submitted';

            if (status === 'submitted') {
                Swal.fire({
                    title: "Success!",
                    text: "Your invoice has been submitted.",
                    icon: "success"
                });
            } else {
                Swal.fire({
                    title: "Success!",
                    text: "Your invoice has been saved as draft.",
                    icon: "success"
                });
            }
        }).catch(err => {
        const errors = err.response.data.errors;
        if (errors) {
            Object.values(errors).flat().forEach(error => {
                toast.error(error)
            })
        } else {
            toast.error(err.response.data.message ?? "Gagal menyimpan invoice")
        }
    })
}

function exportInvoice() {
    Swal.fire({
        title: "Select languange",
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: "Indonesia",
        denyButtonText: `English`,
        confirmButtonColor: "#f69f0a",
        denyButtonColor: "#000",
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(route('invoices.export', { id: invoiceData.value.id, lang: 'id' }), '_blank')
        } else if (result.isDenied) {
            window.open(route('invoices.export', { id: invoiceData.value.id, lang: 'en' }), '_blank')
        }
    });

}

function exportExcel() {
    window.open(route('invoices.export.excel', { id: invoiceData.value.id }), '_blank')
}

function generateInvoice() {
    Swal.fire({
        title: "Generate Invoice?",
        html: `
            <div class="text-left text-sm">
                <p>Invoice baru akan dibuat berdasarkan Purchase Order berikut:</p>
                <table class="w-full mt-3 text-sm">
                    <tr>
                        <td class="py-1 text-gray-500">PO Number</td>
                        <td class="py-1 text-right font-medium">${invoiceData.value.invoice_number}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Ditujukan Kepada</td>
                        <td class="py-1 text-right font-medium">${invoiceData.value.to || '-'}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Total</td>
                        <td class="py-1 text-right font-medium">${idrFormat(invoiceData.value.total)}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Jumlah Item</td>
                        <td class="py-1 text-right font-medium">${invoiceData.value.invoice_details.length} item</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Status Invoice Baru</td>
                        <td class="py-1 text-right font-medium">Draft</td>
                    </tr>
                </table>
                <p class="mt-3 text-xs text-gray-500">Seluruh item akan disalin dan nomor invoice baru akan dibuat otomatis.</p>
            </div>`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Generate",
        cancelButtonText: "Batal",
        confirmButtonColor: "#3085d6",
        reverseButtons: true
    }).then((result) => {
        if (!result.isConfirmed) return

        axios.post(route('invoices.generate.invoice', { id: invoiceData.value.id }))
            .then(res => {
                const invoice = res.data
                Swal.fire({
                    title: "Invoice Berhasil Digenerate!",
                    text: `Invoice ${invoice.invoice_number} telah dibuat sebagai draft.`,
                    icon: "success",
                    showCancelButton: true,
                    confirmButtonText: "Buka Invoice",
                    cancelButtonText: "Tutup",
                    confirmButtonColor: "#3085d6",
                    reverseButtons: true
                }).then((r) => {
                    if (r.isConfirmed) {
                        router.get(route('invoices.detail', { id: invoice.id }))
                    }
                })
            })
            .catch(err => {
                toast.error(err.response?.data?.message ?? "Gagal generate invoice")
            })
    })
}

function importItems(event) {
    const file = event.target.files[0]
    if (!file) return

    const formData = new FormData()
    formData.append('file', file)

    axios.post(route('invoices.import.items'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
        .then(res => {
            const items = res.data
            items.forEach(item => {
                const price = Number(item.item_price)
                const qty = Number(item.item_qty)
                invoiceData.value.invoice_details.push({
                    id: undefined,
                    invoice_id: invoiceData.value.invoice_id,
                    item_name: item.item_name,
                    item_code: item.item_code,
                    item_price: price,
                    item_qty: qty,
                    total_price: calculateTotal(qty, price),
                })
            })
            toast.success(`${items.length} item berhasil diimport`)
            if (fileInput.value) fileInput.value.value = ''
        })
        .catch(err => {
            toast.error(err.response?.data?.message || 'Gagal membaca file excel')
            if (fileInput.value) fileInput.value.value = ''
        })
}

function downloadTemplate() {
    window.open(route('invoices.import.template'), '_blank')
}

function renderNotes(notes) {
    if (!notes) return '-'
    if (notes.includes('<')) return notes
    return escapeHtml(notes).replace(/\n/g, '<br>')
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
}

function recalculateTotal() {
    let total = 0;
    invoiceData.value.invoice_details.forEach((value, index,) => {
        total += value.total_price;
    })
    invoiceData.value.total = total;
    invoiceData.value.total_payment = invoiceData.value.total + ((invoiceData.value.tax / 100) * invoiceData.value.total);
}

watch(() => invoiceData.value.invoice_details, recalculateTotal, { deep: true })

onMounted(() => {
    if (props.invoice) {
        const copyData = { ...props.invoice }

        copyData.invoice_details = copyData.details.map(item => ({ ...item, item_price: Number(item.item_price) }))
        copyData.invoice_details.forEach(item => item.total_price = calculateTotal(item.item_qty, item.item_price))
        copyData.paid = copyData.paid === 1
        invoiceData.value = copyData;
        invoiceData.value.category = copyData.invoice_number?.split('-').shift()
        recalculateTotal()
        state.value.canEdit = copyData.status !== 'submitted'
        state.value.isEdit = false
    }

    if (props.category) {
        invoiceData.value.category = props.category
    }

    if (invoiceData.value.category == 'PO') {
        invoiceData.value.paid = false;
    }

})
</script>

<template>

    <Head title="Detail Invoice"/>

    <AuthenticatedLayout class="relative">
        <template #header>
            <div class="inline-flex gap-4 items-center">
                <Button @click="router.get(route('dashboard'))" class="hover:bg-gray-200" variant="outline">
                    <ArrowLeft class="size-5"/>
                </Button>
                <div class="flex flex-col md:flex-row md:items-center gap-2">
                    <h2 v-if="!invoiceData.id" class="font-semibold text-xl text-gray-800 leading-tight">Create New
                        {{ invoiceData.category == 'INV' ? 'Invoice' : 'Purchase Order' }}</h2>
                    <h2 v-else class="font-semibold text-xl text-gray-800 leading-tight">Detail
                        {{ invoiceData.category == 'INV' ? 'Invoice' : 'Purchase Order' }}</h2>
                    <span v-if="invoiceData.id" class="px-2 py-0.5 rounded-full text-xs font-semibold text-white w-fit"
                          :class="invoiceData.status === 'submitted' ? 'bg-green-500' : 'bg-gray-400'">
                        {{ invoiceData.status === 'submitted' ? 'Submitted' : 'Draft' }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-4 sm:px-6 lg:px-8">
                <Card>
                    <CardContent class="overflow-scroll border m-5 rounded p-5">
                        <div class="w-full flex flex-col md:flex-row justify-between items-center pb-4">
                            <div class="flex flex-col md:flex-row flex-1">
                                <img :src="$attrs.auth?.user.img_url" class="aspect-square w-[200px]">
                                <div class="flex flex-col w-full px-2">
                                    <p class="font-bold text-xl md:text-3xl underline">{{ $attrs.auth?.user.name }}</p>
                                    <p class="text-sm">{{ $attrs.auth?.user.address }}</p>
                                    <p class="text-sm">{{ $attrs.auth?.user.phone }}</p>
                                    <p class="text-sm">{{ $attrs.auth?.user.email }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 w-full md:w-fit">
                                <div class="w-full mt-6 mr-6 bg-black text-white pl-1 text-center md:text-start">
                                    <p v-if="invoiceData.category == 'INV'" class="font-bold text-3xl">INVOICE</p>
                                    <p v-else-if="invoiceData.category == 'PO'" class="font-bold text-3xl">Purchase
                                        Order</p>
                                    <p v-else class="font-bold text-3xl">INVOICE</p>
                                </div>
                            </div>
                        </div>
                        <div class="outline-dashed outline-1 outline-gray-500"></div>
                        <div class="flex flex-col w-full mt-4">
                            <div class="flex flex-col gap-2 md:flex-row w-full justify-between items-center">
                                <div class="flex flex-col w-full md:w-1/3 border">
                                    <div class=" bg-black">
                                        <p class="font-bold text-xl text-center text-white">{{
                                                invoiceData.category == 'INV' ? 'Ditagihkan Kepada' : 'Ditujukan Kepada'
                                            }}</p>
                                    </div>
                                    <div class="p-2">
                                        <Input v-if="state.isEdit" v-model="invoiceData.to"
                                               placeholder="Receipent name"/>
                                        <p v-else class="font-bold text-lg underline">{{ invoiceData.to }}</p>
                                        <Textarea class="mt-1" v-if="state.isEdit"
                                                  v-model="invoiceData.recipient_address" type="text"
                                                  placeholder="Recipient Address"/>
                                        <p class="text-sm" v-else>{{ invoiceData.recipient_address }}</p>
                                        <Input class="mt-1" v-if="state.isEdit" v-model="invoiceData.recipient_number"
                                               type="text" placeholder="Recipient Phone"/>
                                        <p class="text-sm" v-else>{{ invoiceData.recipient_number || '' }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-row  border">
                                    <div class="flex flex-col bg-black">
                                        <span v-if="invoiceData.category == 'INV'"
                                              class="text-white px-1 text-nowrap  flex-1 w-full">Invoice Number :
                                        </span>
                                        <span v-else class="text-white px-1 text-nowrap  flex-1 w-full">PO Number :
                                        </span>
                                        <span class="text-nowrap  text-white px-1  flex-1 w-full">Date : </span>
                                        <span v-if="invoiceData.paid == false && invoiceData.category == 'INV'"
                                              class="text-nowrap  text-white px-1 bg-red-500 flex-1 w-full">Due Date :
                                        </span>
                                        <span v-if="invoiceData.category == 'INV'"
                                              class="text-nowrap  text-white px-1 flex-1 w-full">Status : </span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="md:self-end flex-1 text-nowrap  px-1">{{
                                                invoiceData.invoice_number || '-'
                                            }}</span>
                                        <Popover v-if="state.isEdit">
                                            <PopoverTrigger as-child>
                                                <Button class="w-full rounded-0 border-t" :class="cn(
                                                    ' justify-start text-left font-normal',
                                                    !invoiceData.invoice_date && 'text-muted-foreground',
                                                )">
                                                    <CalendarIcon class="mr-2 h-4 w-4"/>
                                                    {{
                                                        invoiceData.invoice_date ? displayDate(invoiceData.invoice_date) : "Pick a date"
                                                    }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-auto p-0">
                                                <Calendar v-model="invoiceData.invoice_date" initial-focus/>
                                            </PopoverContent>
                                        </Popover>
                                        <span class=" flex-1 border-t  px-1" v-else>{{
                                                invoiceData.invoice_date
                                            }}</span>
                                        <Popover
                                            v-if="state.isEdit && invoiceData.paid == false && invoiceData.category == 'INV'">
                                            <PopoverTrigger as-child>
                                                <Button class="w-full rounded-0 border-t" :class="cn(
                                                    ' justify-start text-left font-normal',
                                                    !invoiceData.due_date && 'text-muted-foreground',
                                                )">
                                                    <CalendarIcon class="mr-2 h-4 w-4"/>
                                                    {{
                                                        invoiceData.due_date ? displayDate(invoiceData.due_date) : "Pick a date"
                                                    }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-auto p-0">
                                                <Calendar v-model="invoiceData.due_date" initial-focus/>
                                            </PopoverContent>
                                        </Popover>
                                        <span v-if="!state.isEdit  && invoiceData.category == 'INV'"
                                              class=" flex-1 border-t  px-1">{{
                                                invoiceData.due_date || '-'
                                            }}</span>
                                        <Select v-if="state.isEdit && invoiceData.category == 'INV'"
                                                v-model="invoiceData.paid">
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Select a status"/>
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectItem :value="true">
                                                        Paid
                                                    </SelectItem>
                                                    <SelectItem :value="false">
                                                        Unpaid
                                                    </SelectItem>
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        <span v-if="!state.isEdit && invoiceData.category == 'INV'"
                                              class="w-full px-1 text-white font-bold"
                                              :class="invoiceData.paid ? 'bg-green-500' : 'bg-red-500'">{{
                                                invoiceData.paid
                                                    ? 'Paid' : 'Unpaid'
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col w-full mt-12 gap-2">
                            <div v-if="state.isEdit" class="flex flex-wrap gap-2">
                                <Button class="bg-amber-500 text-white" @click="addItem()">
                                    <PlusCircleIcon class="size-5"/>
                                    Add Item
                                </Button>
                                <Button class="bg-blue-500 text-white" @click="fileInput?.click()">
                                    <Upload class="size-5"/>
                                    Import Items
                                </Button>
                                <Button class="bg-gray-500 text-white" @click="downloadTemplate()">
                                    <Download class="size-5"/>
                                    Download Template
                                </Button>
                                <input ref="fileInput" type="file" accept=".xlsx,.xls" class="hidden"
                                       @change="importItems($event)">
                            </div>
                            <Table class="w-[1000px] md:w-full border border-black">
                                <TableHeader>
                                    <TableRow class="bg-black hover:bg-black">
                                        <TableHead class="w-[100px] text-white text-center">
                                            No
                                        </TableHead>
                                        <TableHead class="text-white w-44">Name</TableHead>
                                        <TableHead class="text-white w-44">Code</TableHead>
                                        <TableHead class="text-white w-44 text-center">Price</TableHead>
                                        <TableHead class="text-white w-24 text-center">
                                            Amount
                                        </TableHead>
                                        <TableHead class="text-white text-center w-44">
                                            Total
                                        </TableHead>
                                        <TableHead v-if="state.isEdit" class="text-white text-center w-24">
                                            Action
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody v-if="state.isEdit">
                                    <TableRow v-for="(item, index) in invoiceData.invoice_details"
                                              v-if="invoiceData.invoice_details.length != 0">
                                        <TableCell class="text-center font-medium">
                                            {{ index + 1 }}
                                        </TableCell>
                                        <TableCell>
                                            <Input class="w-full" v-model="item.item_name" required
                                                   placeholder="Item Name"/>
                                        </TableCell>
                                        <TableCell>
                                            <Input class="w-full" v-model="item.item_code" placeholder="Item Code"/>
                                        </TableCell>
                                        <TableCell class="p-1">
                                            <div
                                                class="border border-black rounded-md inline-flex w-full items-center overflow-clip">
                                                <div class="h-full w-fit bg-black text-white p-2">
                                                    <span>Rp.</span>
                                                </div>
                                                <Input
                                                    class="w-full border-0 rounded-none focus:rounded-tr focus:rounded-br"
                                                    :model-value="dotFormat(item.item_price)" @update:modelValue="val => {
                                                        const raw = val.replace(/[^0-9]/g, '')
                                                        item.item_price = raw ? Number(raw) : 0
                                                        item.total_price = calculateTotal(item.item_qty, item.item_price)
                                                    }" placeholder="Item Price"/>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Input class="w-24 justify-self-center " v-model="item.item_qty"
                                                   type="number"
                                                   @update:modelValue="() => { item.total_price = calculateTotal(item.item_qty, item.item_price) }"
                                                   required placeholder="Item Quantity"/>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span class="font-bold text-lg text-center w-full block">{{
                                                    idrFormat(item.total_price)
                                                }}</span>
                                        </TableCell>
                                        <TableCell>
                                            <Button @click="invoiceData.invoice_details.splice(index, 1)"
                                                    class="bg-destructive text-white w-full">
                                                <TrashIcon class="size-4"/>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else>
                                        <TableCell class="text-center" colspan="7">Empty Data,..</TableCell>
                                    </TableRow>
                                    <table-row>
                                        <table-cell class=" border-black border" colspan="5">
                                            <span class="text-black font-bold text-xl">Total</span>
                                        </table-cell>
                                        <table-cell class="border border-black" colspan="2">
                                            <span class="font-bold text-lg text-end">{{
                                                    idrFormat(invoiceData.total)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border !py-0" colspan="5">
                                            <span class="text-black font-bold text-xl">Tax</span>
                                        </table-cell>
                                        <table-cell class="border border-black !p-0" colspan="2">
                                            <div class="flex items-stretch">
                                                <div class="bg-red-500 text-black px-3 flex items-center">
                                                    <span>%</span>
                                                </div>

                                                <Input type="number" class="flex-1 border-0 rounded-none"
                                                       v-model="invoiceData.tax"
                                                       @update:modelValue="() => { invoiceData.total_payment = invoiceData.total + ((invoiceData.tax / 100) * invoiceData.total) }"
                                                       placeholder="Invoice Tax"/>
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border" colspan="5">
                                            <span class="text-black font-bold text-xl">Total Tax</span>
                                        </table-cell>
                                        <table-cell class=" border-black text-black" colspan="2">
                                            <div class="flex items-stretch font-bold text-lg"> +
                                                {{ idrFormat((invoiceData.tax / 100) * invoiceData.total) }}
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border bg-black" colspan="5">
                                            <span class="text-white font-bold text-xl">Total Payment</span>
                                        </table-cell>
                                        <table-cell class="border border-black bg-green-500 text-white" colspan="2">
                                            <span class="font-bold text-lg text-end">{{
                                                    idrFormat(invoiceData.total_payment)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                    <table-row v-if="invoiceData.category == 'INV'">
                                        <table-cell class=" border-black border bg-black" colspan="5">
                                            <span class="text-white font-bold text-xl">Payment To</span>
                                        </table-cell>
                                        <table-cell class="border border-black" colspan="2">
                                            <Input type="text" placeholder="Payment Method"
                                                   v-model="invoiceData.payment_number"/>
                                        </table-cell>
                                    </table-row>
                                </TableBody>

                                <TableBody v-else>
                                    <TableRow v-for="(item, index) in invoiceData.invoice_details"
                                              v-if="invoiceData.invoice_details.length != 0">
                                        <TableCell class="text-center font-medium">
                                            {{ index + 1 }}
                                        </TableCell>
                                        <TableCell>
                                            {{ item.item_name }}
                                        </TableCell>
                                        <TableCell>
                                            {{ item.item_code }}
                                        </TableCell>
                                        <TableCell class="p-1">
                                            {{ idrFormat(item.item_price) }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            {{ item.item_qty }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <span>{{ idrFormat(item.total_price) }}</span>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else>
                                        <TableCell class="text-center" colspan="7">Empty Data,..</TableCell>
                                    </TableRow>
                                    <table-row>
                                        <table-cell class=" border-black border" colspan="5">
                                            <span class="text-black font-bold text-xl">Total Payment</span>
                                        </table-cell>
                                        <table-cell class="border border-black" colspan="2">
                                            <span class="font-bold text-lg text-center w-full block">{{
                                                    idrFormat(invoiceData.total)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border !py-0" colspan="5">
                                            <span class="text-black font-bold text-xl">Tax</span>
                                        </table-cell>
                                        <table-cell class="border border-black !p-0" colspan="2">
                                            <div class="flex items-stretch">
                                                <div class="bg-red-500 text-white px-3 flex items-center">
                                                    <span>%</span>
                                                </div>

                                                <Input type="number" class="flex-1 border-0 rounded-none"
                                                       v-model="invoiceData.tax"
                                                       @update:modelValue="() => { invoiceData.total_payment = invoiceData.total - ((invoiceData.tax / 100) * invoiceData.total) }"
                                                       placeholder="Invoice Tax" readonly/>
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border" colspan="5">
                                            <span class="text-black font-bold text-xl">Total Tax</span>
                                        </table-cell>
                                        <table-cell class=" bg-red-500 border border-black text-white" colspan="2">
                                            <div class="flex items-stretch font-bold text-lg"> +
                                                {{ idrFormat((invoiceData.tax / 100) * invoiceData.total) }}
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border " colspan="5">
                                            <span class="text-black font-bold text-xl">Total Payment</span>
                                        </table-cell>
                                        <table-cell class="border border-black bg-green-500 text-white" colspan="2">
                                            <span class="font-bold text-lg text-end">{{
                                                    idrFormat(invoiceData.total_payment)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                    <table-row v-if="invoiceData.category == 'INV'">
                                        <table-cell class=" border-black border" colspan="5">
                                            <span class="text-black font-bold text-xl">Payment To</span>
                                        </table-cell>
                                        <table-cell class="border border-black" colspan="2">
                                            {{ invoiceData.payment_number || '-' }}
                                        </table-cell>
                                    </table-row>
                                </TableBody>
                            </Table>
                        </div>
                        <div class="flex flex-col w-full mt-6 gap-2">
                            <p class="font-bold text-xl">Notes</p>
                            <RichTextEditor v-if="state.isEdit" v-model="invoiceData.notes"
                                            placeholder="Catatan untuk invoice / purchase order..."/>
                            <div v-else class="rich-view break-words border border-gray-300 rounded p-3 min-h-10"
                                 v-html="renderNotes(invoiceData.notes)"></div>
                        </div>
                        <div class="outline-dashed outline-1 outline-gray-400 rounded-full mt-4"></div>
                        <p class="text-sm font-light">*This {{ invoiceData.category == 'INV' ? 'invoice' : 'PO' }}
                            generated by system, Manual signature is not
                            necessary</p>
                    </CardContent>
                    <CardFooter class="block">
                        <div v-if="!state.isEdit" class="mt-5 flex flex-col md:flex-row gap-3">
                            <Button class="bg-black text-white w-full" @click="exportInvoice()">
                                <PrinterIcon class="size-5"/>
                                Export Invoice
                            </Button>
                            <Button class="bg-green-600 text-white w-full" @click="exportExcel()">
                                <FileSpreadsheet class="size-5"/>
                                Export Excel
                            </Button>
                            <Button v-if="invoiceData.category == 'PO' && invoiceData.status === 'submitted'"
                                    class="bg-blue-500 text-white w-full"
                                    @click="generateInvoice()">
                                <FilePlus2 class="size-5"/>
                                Generate Invoice
                            </Button>
                            <Button v-if="state.canEdit" class="bg-green-500 text-white w-full"
                                    @click="confirmSave('submitted')">
                                <SaveIcon class="size-5"/>
                                Submit Invoice
                            </Button>
                            <Button v-if="state.canEdit" class="bg-amber-500 text-white w-full"
                                    @click="state.isEdit = true">
                                <Pencil class="size-5"/>
                                Edit
                            </Button>
                        </div>
                        <Button v-else class="bg-green-500 text-white w-full mt-5" @click="confirmSave('draft')">
                            <SaveIcon class="size-5"/>
                            Save as Draft
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.rich-view :deep(p) {
    margin: 0.5em 0;
}

.rich-view :deep(ul),
.rich-view :deep(ol) {
    padding-left: 1.5rem;
    margin: 0.5em 0;
}

.rich-view :deep(blockquote) {
    border-left: 3px solid #d1d5db;
    padding-left: 0.75rem;
    color: #4b5563;
    margin: 0.5em 0;
}

.rich-view :deep(h1) {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0.5em 0;
}

.rich-view :deep(h2) {
    font-size: 1.25rem;
    font-weight: bold;
    margin: 0.5em 0;
}

.rich-view :deep(h3) {
    font-size: 1.1rem;
    font-weight: bold;
    margin: 0.5em 0;
}

.rich-view :deep(u) {
    text-decoration: underline;
}
</style>
