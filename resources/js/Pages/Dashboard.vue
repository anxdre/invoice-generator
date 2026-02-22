<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import Card from "@/Components/ui/card/Card.vue";
import { CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { EyeIcon, Plus, TrashIcon } from "lucide-vue-next";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { onMounted, ref } from "vue";
import axios from "axios";
import { toast } from "vue-sonner";
import { formatDate, idrFormat } from "@/lib/utils";
import Swal from "sweetalert2";

const dataset = ref([])

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
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-1/12 text-center">
                                        No
                                    </TableHead>
                                    <TableHead class="w-fit">
                                        Invoice Number
                                    </TableHead>
                                    <TableHead>Charged To</TableHead>
                                    <TableHead class="text-right">
                                        Amount
                                    </TableHead>
                                    <TableHead class="text-center w-fit">Status</TableHead>
                                    <TableHead class="text-center w-fit">Created At</TableHead>
                                    <TableHead class="text-center">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(item,index) in dataset" key="item.id">
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
                                    <table-cell class="text-center">{{formatDate(item.created_at)}}</table-cell>
                                    <TableCell class="justify-center gap-2 inline-flex w-full ">
                                        <Button  @click="router.get(route('invoices.detail',{id:item.id}))" class="bg-black text-white p-2">
                                            <EyeIcon/>
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
