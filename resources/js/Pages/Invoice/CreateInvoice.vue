<script setup lang="ts">

import { CardContent, CardFooter } from "@/Components/ui/card";
import { ArrowLeft, CalendarIcon, PlusCircleIcon, PrinterIcon, SaveIcon, TrashIcon } from "lucide-vue-next";
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
import axios from "axios";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue, } from '@/components/ui/select'
import { toast } from "vue-sonner";
import Swal from "sweetalert2";

const props = defineProps({
    invoice: Object,
})

const state = ref({ isEdit: true })

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
    tax: undefined,
    invoice_details: []
})

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

function confirmSave() {
    Swal.fire({
        title: "Are you sure ?",
        text: "You won't be able to edit this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, save it!",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            saveInvoice()
        }
    });
}

function saveInvoice() {
    axios.post(route('invoices.store'), {
        ...invoiceData.value,
        date: df.format(invoiceData.value.invoice_date?.toDate(getLocalTimeZone())),
        due_date: df.format(invoiceData.value.due_date?.toDate(getLocalTimeZone())),
    })
        .then(res => {
            invoiceData.value.id = res.data.id;
            invoiceData.value.user_id = res.data.user_id;
            invoiceData.value.invoice_number = res.data.invoice_number;
            state.value.isEdit = false;

            Swal.fire({
                title: "Deleted!",
                text: "Your invoice has been saved.",
                icon: "success"
            });
        }).catch(err => {
        const errors = err.response.data.errors;
        Object.values(errors).flat().forEach(error => {
            toast.error(error)
        })
    })
}

function exportInvoice() {
    window.open(route('invoices.export', invoiceData.value.id), '_blank')
}

watch(invoiceData.value.invoice_details, () => {
    let total = 0;
    invoiceData.value.invoice_details.forEach((value, index,) => {
        total += value.total_price;
    })
    invoiceData.value.total = total;
    invoiceData.value.total_payment = invoiceData.value.total - ((invoiceData.value.tax ?? 0 / 100) * invoiceData.value.total);
}, { deep: true })

onMounted(() => {
    if (props.invoice) {
        state.value.isEdit = false;
        const copyData = { ...props.invoice }

        copyData.invoice_details = copyData.details.map(item => ({ ...item, item_price: Number(item.item_price) }))
        copyData.invoice_details.forEach(item => item.total_price = calculateTotal(item.item_qty, item.item_price))
        copyData.paid = copyData.paid === 1
        invoiceData.value = copyData;
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
                <h2 v-if="state.isEdit" class="font-semibold text-xl text-gray-800 leading-tight">Create New
                    Invoice</h2>
                <h2 v-else class="font-semibold text-xl text-gray-800 leading-tight">Detail Invoice</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-4 sm:px-6 lg:px-8">
                <Card>
                    <CardContent class="overflow-scroll border m-5 rounded p-5">
                        <div class="w-full flex flex-col md:flex-row justify-between items-center pb-4">
                            <div class="flex flex-row flex-1">
                                <img :src=" $attrs.auth?.user.img_url" class="aspect-square w-[200px]">
                                <div class="flex flex-col w-full px-2">
                                    <p class="font-bold text-xl md:text-3xl underline">{{ $attrs.auth?.user.name }}</p>
                                    <p class="text-sm">{{ $attrs.auth?.user.address }}</p>
                                    <p class="text-sm">{{ $attrs.auth?.user.phone }}</p>
                                    <p class="text-sm">{{ $attrs.auth?.user.email }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 w-full md:w-fit">
                                <div
                                    class="w-full mt-6 mr-6 bg-black text-white pl-1 text-center md:text-start">
                                    <p class="font-bold text-3xl">INVOICE</p>
                                </div>
                            </div>
                        </div>
                        <div class="outline-dashed outline-1 outline-gray-500"></div>
                        <div class="flex flex-col w-full mt-4">
                            <div class="flex flex-col gap-2 md:flex-row w-full justify-between items-center">
                                <div class="flex flex-col w-full md:w-1/3 border">
                                    <div class=" bg-black">
                                        <p class="font-bold text-xl text-center text-white">Charged to</p>
                                    </div>
                                    <div class="p-2">
                                        <Input v-if="state.isEdit" v-model="invoiceData.to"
                                               placeholder="Receipent name"/>
                                        <p v-else class="font-bold text-lg underline">{{ invoiceData.to }}</p>
                                        <Textarea class="mt-1" v-if="state.isEdit"
                                                  v-model="invoiceData.recipient_address" type="text"
                                                  placeholder="Recipient Address"/>
                                        <p class="text-sm" v-else>{{ invoiceData.recipient_address }}</p>
                                        <Input class="mt-1" v-if="state.isEdit" v-model="invoiceData.payment_number"
                                               type="text"
                                               placeholder="Recipient Phone"/>
                                        <p class="text-sm" v-else>{{ invoiceData.payment_number || '' }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-row  border">
                                    <div class="flex flex-col bg-black">
                                        <span
                                            class="text-white px-1 text-nowrap  flex-1 w-full">Invoice Number : </span>
                                        <span class="text-nowrap  text-white px-1  flex-1 w-full">Date : </span>
                                        <span v-if="invoiceData.paid == false && invoiceData.due_date"
                                              class="text-nowrap  text-white px-1 bg-red-500 flex-1 w-full">Due Date : </span>
                                        <span class="text-nowrap  text-white px-1 flex-1 w-full">Status : </span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="md:self-end flex-1 text-nowrap  px-1">{{
                                                invoiceData.invoice_number || '-'
                                            }}</span>
                                        <Popover v-if="state.isEdit">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    class="w-full rounded-0 border-t"
                                                    :class="cn(
                                                          ' justify-start text-left font-normal',
                                                          !invoiceData.invoice_date && 'text-muted-foreground',
                                                        )">
                                                    <CalendarIcon class="mr-2 h-4 w-4"/>
                                                    {{
                                                        invoiceData.invoice_date ? df.format(invoiceData.invoice_date.toDate(getLocalTimeZone())) : "Pick a date"
                                                    }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-auto p-0">
                                                <Calendar v-model="invoiceData.invoice_date" initial-focus/>
                                            </PopoverContent>
                                        </Popover>
                                        <span class=" flex-1 border-t  px-1"
                                              v-else>{{ invoiceData.invoice_date }}</span>
                                        <Popover v-if="state.isEdit && invoiceData.paid == false">
                                            <PopoverTrigger as-child>
                                                <Button
                                                    class="w-full rounded-0 border-t"
                                                    :class="cn(
                                                          ' justify-start text-left font-normal',
                                                          !invoiceData.due_date && 'text-muted-foreground',
                                                        )">
                                                    <CalendarIcon class="mr-2 h-4 w-4"/>
                                                    {{
                                                        invoiceData.due_date ? df.format(invoiceData.due_date.toDate(getLocalTimeZone())) : "Pick a date"
                                                    }}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent class="w-auto p-0">
                                                <Calendar v-model="invoiceData.due_date" initial-focus/>
                                            </PopoverContent>
                                        </Popover>
                                        <span v-if="!state.isEdit && invoiceData.due_date"
                                              class=" flex-1 border-t  px-1"
                                        >{{ invoiceData.due_date }}</span>
                                        <Select v-if="state.isEdit" v-model="invoiceData.paid">
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
                                        <span class="w-full px-1 text-white font-bold"
                                              :class="invoiceData.paid ? 'bg-green-500' : 'bg-red-500'"
                                              v-else>{{ invoiceData.paid ? 'Paid' : 'Unpaid' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col w-full mt-12 gap-2">
                            <Button v-if="state.isEdit" class="bg-amber-500 text-white" @click="addItem()">
                                <PlusCircleIcon class="size-5"/>
                                Add Item
                            </Button>
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
                                    <TableRow v-for="(item,index) in invoiceData.invoice_details"
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
                                                    :model-value="dotFormat(item.item_price)"
                                                    @update:modelValue="val => {
                                                    const raw = val.replace(/[^0-9]/g, '')
                                                    item.item_price = raw ? Number(raw) : 0
                                                    item.total_price = calculateTotal(item.item_qty, item.item_price)
                                                  }"
                                                    placeholder="Item Price"
                                                />
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Input class="w-24 justify-self-center " v-model="item.item_qty"
                                                   type="number"
                                                   @update:modelValue="()=>{item.total_price = calculateTotal(item.item_qty,item.item_price)}"
                                                   required placeholder="Item Quantity"/>
                                        </TableCell>
                                        <TableCell class="text-center">
                                           <span
                                               class="font-bold text-lg text-center w-full block">{{
                                                   idrFormat(item.total_price)
                                               }}</span>
                                        </TableCell>
                                        <TableCell>
                                            <Button @click="invoiceData.invoice_details.splice(index,1)"
                                                    class="bg-destructive text-white w-full">
                                                <TrashIcon class="size-4"/>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-else>
                                        <TableCell class="text-center" colspan="7">Empty Data,..</TableCell>
                                    </TableRow>
                                    <table-row>
                                        <table-cell class="bg-black border-black border" colspan="5">
                                            <span class="text-white font-bold text-xl">Total</span>
                                        </table-cell>
                                        <table-cell class="border border-black" colspan="2">
                                            <span
                                                class="font-bold text-lg text-end">{{
                                                    idrFormat(invoiceData.total)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class="bg-black border-black border !py-0" colspan="5">
                                            <span class="text-white font-bold text-xl">Tax</span>
                                        </table-cell>
                                        <table-cell class="border border-black !p-0" colspan="2">
                                            <div class="flex items-stretch">
                                                <div class="bg-red-500 text-white px-3 flex items-center">
                                                    <span>%</span>
                                                </div>

                                                <Input
                                                    type="number"
                                                    class="flex-1 border-0 rounded-none"
                                                    v-model="invoiceData.tax"
                                                    @update:modelValue="()=>{invoiceData.total_payment = invoiceData.total - ((invoiceData.tax / 100) * invoiceData.total)}"
                                                    placeholder="Invoice Tax"
                                                />
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class="bg-black border-black border" colspan="5">
                                            <span class="text-white font-bold text-xl">Total Tax</span>
                                        </table-cell>
                                        <table-cell class=" bg-red-500 border border-black text-white" colspan="2">
                                            <div class="flex items-stretch font-bold text-lg"> -
                                                {{ idrFormat((invoiceData.tax / 100) * invoiceData.total) }}
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border bg-black" colspan="5">
                                            <span class="text-white font-bold text-xl">Total Payment</span>
                                        </table-cell>
                                        <table-cell class="border border-black bg-green-500 text-white" colspan="2">
                                            <span
                                                class="font-bold text-lg text-end">{{
                                                    idrFormat(invoiceData.total_payment)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                </TableBody>
                                <TableBody v-else>
                                    <TableRow v-for="(item,index) in invoiceData.invoice_details"
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
                                        <table-cell class="bg-black border-black border" colspan="5">
                                            <span class="text-white font-bold text-xl">Total Payment</span>
                                        </table-cell>
                                        <table-cell class="border border-black" colspan="2">
                                            <span
                                                class="font-bold text-lg text-center w-full block">{{
                                                    idrFormat(invoiceData.total)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class="bg-black border-black border !py-0" colspan="5">
                                            <span class="text-white font-bold text-xl">Tax</span>
                                        </table-cell>
                                        <table-cell class="border border-black !p-0" colspan="2">
                                            <div class="flex items-stretch">
                                                <div class="bg-red-500 text-white px-3 flex items-center">
                                                    <span>%</span>
                                                </div>

                                                <Input
                                                    type="number"
                                                    class="flex-1 border-0 rounded-none"
                                                    v-model="invoiceData.tax"
                                                    @update:modelValue="()=>{invoiceData.total_payment = invoiceData.total - ((invoiceData.tax / 100) * invoiceData.total)}"
                                                    placeholder="Invoice Tax"
                                                    readonly
                                                />
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class="bg-black border-black border" colspan="5">
                                            <span class="text-white font-bold text-xl">Total Tax</span>
                                        </table-cell>
                                        <table-cell class=" bg-red-500 border border-black text-white" colspan="2">
                                            <div class="flex items-stretch font-bold text-lg"> -
                                                {{ idrFormat((invoiceData.tax / 100) * invoiceData.total) }}
                                            </div>
                                        </table-cell>
                                    </table-row>
                                    <table-row>
                                        <table-cell class=" border-black border bg-black" colspan="5">
                                            <span class="text-white font-bold text-xl">Total Payment</span>
                                        </table-cell>
                                        <table-cell class="border border-black bg-green-500 text-white" colspan="2">
                                            <span
                                                class="font-bold text-lg text-end">{{
                                                    idrFormat(invoiceData.total_payment)
                                                }}</span>
                                        </table-cell>
                                    </table-row>
                                </TableBody>
                            </Table>
                        </div>
                        <div class="outline-dashed outline-1 outline-gray-400 rounded-full mt-4"></div>
                        <p
                            class="text-sm font-light">*This invoice generated by system, Manual signature is not
                            necessary</p>
                    </CardContent>
                    <CardFooter class="block">
                        <Button v-if="state.isEdit" class="bg-green-500 text-white w-full mt-5" @click="confirmSave()">
                            <SaveIcon class="size-5"/>
                            Save Invoice
                        </Button>
                        <Button v-else class="bg-black text-white w-full mt-5" @click="exportInvoice()">
                            <PrinterIcon class="size-5"/>
                            Export Invoice
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
