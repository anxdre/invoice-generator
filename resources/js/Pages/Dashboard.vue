<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Card from "@/Components/ui/card/Card.vue";
import { CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { EyeIcon, FilePlus2, Plus, TrashIcon } from "lucide-vue-next";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Input } from "@/Components/ui/input";
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { formatDate, idrFormat } from "@/lib/utils";
import Swal from "sweetalert2";

const dataset = ref([])
const search = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')
const sortKey = ref('created_at')
const sortDir = ref('desc')

const filteredInvoices = computed(() => {
    let rows = dataset.value.filter(item => {
        const q = search.value.trim().toLowerCase()
        const matchSearch = !q
            || (item.invoice_number || '').toLowerCase().includes(q)
            || (item.to || '').toLowerCase().includes(q)
        const matchStatus = !statusFilter.value || item.status === statusFilter.value
        const category = String(item.invoice_number || '').split('-')[0]
        const matchCategory = !categoryFilter.value || category === categoryFilter.value
        return matchSearch && matchStatus && matchCategory
    })

    const dir = sortDir.value === 'asc' ? 1 : -1
    return rows.sort((a, b) => {
        let av, bv
        if (sortKey.value === 'total') {
            av = a.total; bv = b.total
        } else if (sortKey.value === 'created_at') {
            av = new Date(a.created_at); bv = new Date(b.created_at)
        } else {
            av = String(a[sortKey.value] || '').toLowerCase()
            bv = String(b[sortKey.value] || '').toLowerCase()
        }
        if (av < bv) return -1 * dir
        if (av > bv) return 1 * dir
        return 0
    })
})

function toggleSort(key) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortKey.value = key
        sortDir.value = 'asc'
    }
}

function sortIndicator(key) {
    if (sortKey.value !== key) return '↕'
    return sortDir.value === 'asc' ? '↑' : '↓'
}

function getData() {
    axios.get(route('invoices.index'))
        .then(res => {
            dataset.value = res.data;
        }).catch(err => {
        toast.error(err.response.data.message)
    })
}

function deleteInvoice(id) {
    axios.post(route('invoices.delete'),{id:id})
        .then(res => {
            Swal.fire({
                title: "Deleted!",
                text: "Your invoice has been deleted.",
                icon: "success"
            });
            getData();
        }).catch(err => {
        toast.error(err.response.data.message)
    })
}

function confirmDelete(id) {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            deleteInvoice(id);
        }
    });
}

function createInvoice() {
    Swal.fire({
        title: "Select type",
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: "Invoice",
        denyButtonText: `Purchase Order`,
        confirmButtonColor: "#f69f0a",
        denyButtonColor: "#000",
    }).then((result) => {
        if (result.isConfirmed) {
            router.get(route('invoices.create.index', { type: 'INV', }))
        } else if (result.isDenied) {
            router.get(route('invoices.create.index', { type: 'PO', }))
        }
    });

}

function generateInvoice(item) {
    Swal.fire({
        title: "Generate Invoice?",
        html: `
            <div class="text-left text-sm">
                <p>Invoice baru akan dibuat berdasarkan Purchase Order berikut:</p>
                <table class="w-full mt-3 text-sm">
                    <tr>
                        <td class="py-1 text-gray-500">PO Number</td>
                        <td class="py-1 text-right font-medium">${item.invoice_number}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Ditujukan Kepada</td>
                        <td class="py-1 text-right font-medium">${item.to || '-'}</td>
                    </tr>
                    <tr>
                        <td class="py-1 text-gray-500">Total</td>
                        <td class="py-1 text-right font-medium">${idrFormat(item.total)}</td>
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

        axios.post(route('invoices.generate.invoice', { id: item.id }))
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
                    } else {
                        getData()
                    }
                })
            })
            .catch(err => {
                toast.error(err.response?.data?.message ?? "Gagal generate invoice")
            })
    })
}

onMounted(() => {
    getData();
})
</script>

<template>
    <Head title="Dashboard"/>

    <AuthenticatedLayout class="relative">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <Button @click="createInvoice()"
                class="bg-black text-white rounded-full absolute bottom-0 right-0 mr-4 mb-4 size-16 md:hidden md:rounded-lg md:w-fit md:h-fit">
            <Plus class="size-6 md:mr-2"/>
            <span class="hidden md:block">Create</span></Button>

        <div class="py-12">
            <div class="mx-4 sm:px-6 lg:px-8">
                <Card>
                    <CardHeader>
                        <CardTitle>Invoices</CardTitle>
                        <CardDescription>
                            <div class="inline-flex w-full justify-between items-center">
                                <p class="text-sm text-gray-600">Manage your invoices here </p>
                                <Button @click="createInvoice()"
                                        class="bg-black text-white rounded-full hidden  mr-4 mb-4 size-16 md:flex md:rounded-lg md:w-fit md:h-fit">
                                    <Plus class="size-6 md:mr-2"/>
                                    <span class="hidden md:block">Create</span></Button>
                            </div>
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="overflow-scroll">
                        <div class="flex flex-col md:flex-row gap-3 mb-4">
                            <Input v-model="search" placeholder="Search invoice number or charged to..."
                                   class="md:max-w-sm"/>
                            <select v-model="statusFilter"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="submitted">Submitted</option>
                            </select>
                            <select v-model="categoryFilter"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white">
                                <option value="">All Type</option>
                                <option value="INV">Invoice</option>
                                <option value="PO">Purchase Order</option>
                            </select>
                        </div>
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-1/12 text-center">
                                        No
                                    </TableHead>
                                    <TableHead class="w-fit cursor-pointer select-none"
                                               @click="toggleSort('invoice_number')">
                                        Invoice Number <span class="text-xs text-gray-400">{{ sortIndicator('invoice_number') }}</span>
                                    </TableHead>
                                    <TableHead class="cursor-pointer select-none" @click="toggleSort('to')">
                                        Ditujukan Kepada <span class="text-xs text-gray-400">{{ sortIndicator('to') }}</span>
                                    </TableHead>
                                    <TableHead class="text-right cursor-pointer select-none"
                                               @click="toggleSort('total')">
                                        Amount <span class="text-xs text-gray-400">{{ sortIndicator('total') }}</span>
                                    </TableHead>
                                    <TableHead class="text-center w-fit">Paid</TableHead>
                                    <TableHead class="text-center w-fit cursor-pointer select-none"
                                               @click="toggleSort('status')">
                                        Status <span class="text-xs text-gray-400">{{ sortIndicator('status') }}</span>
                                    </TableHead>
                                    <TableHead class="text-center w-fit cursor-pointer select-none"
                                               @click="toggleSort('created_at')">
                                        Created At <span class="text-xs text-gray-400">{{ sortIndicator('created_at') }}</span>
                                    </TableHead>
                                    <TableHead class="text-center">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="filteredInvoices.length === 0">
                                    <TableCell class="text-center" colspan="8">
                                        No invoices found
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="(item,index) in filteredInvoices" key="item.id">
                                    <TableCell class="text-center">
                                        {{ index + 1 }}
                                    </TableCell>
                                    <TableCell class="font-medium text-nowrap">
                                        {{ item.invoice_number }}
                                    </TableCell>
                                    <TableCell>{{ item.to }}</TableCell>
                                    <TableCell class="text-right">
                                        {{ idrFormat(item.total) }}
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <span v-if="item.paid" class="bg-green-500 text-white px-4 py-1 rounded-full">Paid</span>
                                        <span v-else class="bg-red-500 text-white px-4 py-1 rounded-full">Unpaid</span>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <span v-if="item.status === 'submitted'"
                                              class="bg-green-500 text-white px-4 py-1 rounded-full">Submitted</span>
                                        <span v-else class="bg-gray-400 text-white px-4 py-1 rounded-full">Draft</span>
                                    </TableCell>
                                    <table-cell class="text-center">{{formatDate(item.created_at)}}</table-cell>
                                    <TableCell class="justify-center gap-2 inline-flex w-full ">
                                        <Button  @click="router.get(route('invoices.detail',{id:item.id}))" class="bg-black text-white p-2">
                                            <EyeIcon/>
                                        </Button>
                                        <Button v-if="String(item.invoice_number).startsWith('PO') && item.status === 'submitted'"
                                                @click="generateInvoice(item)" class="bg-blue-500 text-white p-2"
                                                title="Generate Invoice">
                                            <FilePlus2/>
                                        </Button>
                                        <Button @click="confirmDelete(item.id)" class="bg-destructive text-white p-2">
                                            <TrashIcon/>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                    <CardFooter class="text-xs text-black/50">
                        Build by CV. Niaga Raya Sejahtera with ❤️ in Surabaya
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
