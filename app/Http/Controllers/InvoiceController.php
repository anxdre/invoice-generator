<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Dentro\Yalr\Attributes\Get;
use Dentro\Yalr\Attributes\Middleware;
use Dentro\Yalr\Attributes\Name;
use Dentro\Yalr\Attributes\Post;
use Dentro\Yalr\Attributes\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

#[Prefix('invoices'), Name('invoices'), Middleware('auth')]
class InvoiceController extends Controller
{
    #[Get('/', name: '.index')]
    function index()
    {
        return response()->json(Invoice::query()->where('user_id', Auth::id())->get());
    }

    #[Get('/template/import', name: '.import.template')]
    function importTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template');
        $sheet->fromArray(['Item Name', 'Item Code', 'Item Price', 'Item Qty'], null, 'A1');
        $sheet->fromArray(['Contoh Barang', 'BRG001', 100000, 2], null, 'A2');
        $sheet->fromArray(['Contoh Barang Kedua', 'BRG002', 50000, 4], null, 'A3');

        $header = $sheet->getStyle('A1:D1');
        $header->getFont()->setBold(true);
        $header->getFont()->getColor()->setARGB('FFFFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF000000');

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(10);

        $temp = tempnam(sys_get_temp_dir(), 'template');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($temp);

        return response()->download($temp, 'item-import-template.xlsx')->deleteFileAfterSend(true);
    }

    #[Post('/import-items', name: '.import.items')]
    function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('file');
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray();

            $items = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                if (empty(array_filter($row))) {
                    continue;
                }

                $name = trim((string) ($row[0] ?? ''));
                if ($name === '') {
                    continue;
                }

                $items[] = [
                    'item_name' => $name,
                    'item_code' => isset($row[1]) ? trim((string) $row[1]) : null,
                    'item_price' => $this->parsePrice($row[2] ?? 0),
                    'item_qty' => $this->parsePrice($row[3] ?? 1) ?: 1,
                ];
            }

            return response()->json($items);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membaca file excel. Pastikan format file sesuai template.'], 422);
        }
    }

    #[Get('/{id}', name: '.detail')]
    function detail(Request $request, int $id)
    {
        $data = Invoice::query()
            ->where('id', $id)
            ->with('details')
            ->firstOrFail();
        return Inertia::render('Invoice/CreateInvoice', ['invoice' => $data]);
    }

    #[Get('/create/new', name: '.create.index')]
    function createPage(Request $request)
    {
        return Inertia::render('Invoice/CreateInvoice', ['category' => $request->type ?? '']);
    }

    #[Post('/store', name: '.store')]
    function create(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'to' => 'required|string',
            'recipient_address' => 'required|string',
            'total' => 'required|numeric',
            'paid' => 'required|boolean',
            'status' => 'sometimes|in:draft,submitted',
        ], [
            'to.required' => 'Receipent name field is required',
            'paid.required' => 'Status field is required'
        ]);

        try {
            DB::beginTransaction();

            $invoice = $request->id
                ? Invoice::query()->where('id', $request->id)->where('user_id', Auth::id())->first()
                : null;

            if ($request->id && !$invoice) {
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            if ($invoice && $invoice->status === 'submitted') {
                return response()->json(['message' => 'Submitted invoices can no longer be edited'], 403);
            }

            $data = [
                'user_id' => Auth::id(),
                'invoice_date' => Carbon::parse($request->date)->format('d-m-Y'),
                'to' => $request->to,
                'recipient_address' => $request->recipient_address,
                'total' => $request->total,
                'paid' => $request->paid,
                'payment_number' => $request->payment_number ?? null,
                'total_payment' => $request->total_payment
                    ?? ((float) $request->total + (((float) $request->tax ?? 0) / 100) * (float) $request->total),
                'tax' => $request->tax ?? 0,
                'due_date' => $request->due_date ? Carbon::parse($request->due_date)->format('d-m-Y') : null,
                'recipient_number' => $request->recipient_number ?? null,
                'status' => $request->status ?? 'draft',
                'notes' => $request->notes ?? null,
            ];

            if ($invoice) {
                $invoice->update($data);
                $parentData = $invoice;
            } else {
                $data['invoice_number'] = $request->category == 'INV'
                    ? 'INV-' . Carbon::now()->format('ymd') . '-' . rand(100, 999)
                    : 'PO-' . Carbon::now()->format('ymd') . '-' . rand(100, 999);
                $parentData = Invoice::query()->create($data);
            }

            if ($request->invoice_details) {
                $exsistingDataBuilder = InvoiceDetail::query()
                    ->where('invoice_id', $parentData->id);

                if ($exsistingDataBuilder->count() > 0) {
                    $exsistingDataBuilder->delete();
                }

                foreach ($request->invoice_details as $invoiceDetail) {
                    InvoiceDetail::query()->create([
                        'invoice_id' => $parentData->id,
                        'item_name' => $invoiceDetail['item_name'],
                        'item_code' => $invoiceDetail['item_code'] ?? null,
                        'item_price' => $invoiceDetail['item_price'],
                        'item_qty' => $invoiceDetail['item_qty'],
                    ]);
                }
            }
            DB::commit();
            return response()->json($parentData->load('details'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    #[Post('/delete', name: '.delete')]
    function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:invoices,id'
        ]);

        Invoice::query()
            ->where('id', $request->id)
            ->delete();
        return response()->json('success');
    }

    #[Post('/generate/{id}', name: '.generate.invoice')]
    function generateInvoice(Request $request, int $id)
    {
        $po = Invoice::with('details')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (explode('-', $po->invoice_number)[0] !== 'PO') {
            return response()->json(['message' => 'Hanya Purchase Order yang bisa digenerate menjadi invoice'], 422);
        }

        if ($po->status !== 'submitted') {
            return response()->json(['message' => 'PO harus berstatus submitted sebelum digenerate menjadi invoice'], 422);
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::create([
                'user_id' => Auth::id(),
                'invoice_number' => 'INV-' . Carbon::now()->format('ymd') . '-' . rand(100, 999),
                'invoice_date' => Carbon::now()->format('d-m-Y'),
                'to' => $po->to,
                'recipient_address' => $po->recipient_address,
                'recipient_number' => $po->recipient_number,
                'total' => $po->total,
                'paid' => false,
                'payment_number' => $po->payment_number,
                'total_payment' => $po->total_payment,
                'tax' => $po->tax,
                'due_date' => $po->due_date,
                'status' => 'draft',
                'notes' => $po->notes,
            ]);

            foreach ($po->details as $detail) {
                InvoiceDetail::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $detail->item_name,
                    'item_code' => $detail->item_code,
                    'item_price' => $detail->item_price,
                    'item_qty' => $detail->item_qty,
                ]);
            }

            DB::commit();
            return response()->json($invoice->load('details'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    #[Get('/export/{id}', name: '.export')]
    public function pdf($id, Request $request)
    {
        if (!$id) {
            return redirect()->back();
        }

        $invoice = Invoice::with('details')->findOrFail($id);
        $company = auth()->user();

        if ($invoice->details) {
            $invoice->details->map(function ($item) {
                $item->total_price = $item->item_price * $item->item_qty;
            });
        }

        $type = explode('-', $invoice->invoice_number)[0];
        if ($type == 'INV') {
            $invoice->category = 'INVOICE';
        } else {
            $invoice->category = 'PURCHASE ORDER';
        }

        $prefix = $invoice->status === 'draft' ? 'draft-' : '';
        $fileBase = $type === 'PO' ? $invoice->invoice_number : "invoice-{$invoice->invoice_number}";

        if ($request->lang == 'en') {
            $pdf = Pdf::loadView('exportpdf-en', compact('invoice', 'company'));
            return $pdf->download("{$prefix}{$fileBase}.pdf");
        }

        $pdf = Pdf::loadView('exportpdf-id', compact('invoice', 'company'));
        return $pdf->download("{$prefix}{$fileBase}.pdf");
    }

    #[Get('/export-excel/{id}', name: '.export.excel')]
    public function exportExcel($id, Request $request)
    {
        if (!$id) {
            return redirect()->back();
        }

        $invoice = Invoice::with('details')->findOrFail($id);
        $company = auth()->user();
        $type = explode('-', $invoice->invoice_number)[0];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Invoice');

        $sheet->setCellValue('A1', $company->name);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', $invoice->invoice_number);
        $sheet->setCellValue('A3', 'Date: ' . $invoice->invoice_date);
        $sheet->setCellValue('A4', 'Due Date: ' . ($invoice->due_date ?? '-'));

        $headerRow = 6;
        $sheet->fromArray(['No', 'Item Name', 'Item Code', 'Item Price', 'Item Qty', 'Total Price'], null, "A{$headerRow}");
        $header = $sheet->getStyle("A{$headerRow}:F{$headerRow}");
        $header->getFont()->setBold(true);
        $header->getFont()->getColor()->setARGB('FFFFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF000000');

        $row = $headerRow + 1;
        $total = 0;
        foreach ($invoice->details as $index => $item) {
            $lineTotal = $item->item_price * $item->item_qty;
            $sheet->fromArray([
                $index + 1,
                $item->item_name,
                $item->item_code,
                $item->item_price,
                $item->item_qty,
                $lineTotal,
            ], null, "A{$row}");
            $total += $lineTotal;
            $row++;
        }

        $sheet->setCellValue("D{$row}", 'Total');
        $sheet->setCellValue("F{$row}", $total);
        $sheet->getStyle("D{$row}:F{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("D{$row}", 'Tax');
        $sheet->setCellValue("F{$row}", $invoice->tax . '%');
        $row++;
        $sheet->setCellValue("D{$row}", 'Total Payment');
        $sheet->setCellValue("F{$row}", $invoice->total_payment);
        $sheet->getStyle("D{$row}:F{$row}")->getFont()->setBold(true);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $temp = tempnam(sys_get_temp_dir(), 'invoice');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($temp);

        $fileBase = $type === 'PO' ? $invoice->invoice_number : "invoice-{$invoice->invoice_number}";

        return response()->download($temp, "{$fileBase}.xlsx")->deleteFileAfterSend(true);
    }

    private function parsePrice($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = str_replace(['.', ' '], '', (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : 0;
    }
}
