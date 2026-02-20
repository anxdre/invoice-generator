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

#[Prefix('invoices'), Name('invoices'), Middleware('auth')]
class InvoiceController extends Controller
{
    #[Get('/', name: '.index')]
    function index()
    {
        return response()->json(Invoice::query()->where('user_id', Auth::id())->get());
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
        return Inertia::render('Invoice/CreateInvoice', []);
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
        ], [
            'to.required' => 'Receipent name field is required',
            'paid.required' => 'Status field is required'
        ]);

        try {
            DB::beginTransaction();
            $parentData = Invoice::query()->updateOrCreate([
                'id' => $request->id,
            ], [
                'user_id' => Auth::id(),
                'invoice_number' => Carbon::now()->format('ymd-His') . '-' . rand(100, 999),
                'invoice_date' => Carbon::parse($request->date)->format('d-m-Y'),
                'to' => $request->to,
                'recipient_address' => $request->recipient_address,
                'total' => $request->total,
                'paid' => $request->paid,
                'payment_number' => $request->payment_number ?? null,
                'total_payment' => $request->total_payment ?? null,
                'tax' => $request->tax ?? 0,
                'due_date' => $request->due_date ? Carbon::parse($request->due_date)->format('d-m-Y') : null
            ]);

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

    #[Get('/export/{id}', name: '.export')]
    public function pdf($id)
    {
        if (!$id){
            return redirect()->back();
        }

        $invoice = Invoice::with('details')->findOrFail($id);
        $company = auth()->user();

        if ($invoice->details){
            $invoice->details->map(function ($item) {
                $item->total_price = $item->item_price * $item->item_qty;
            });
        }

        $pdf = Pdf::loadView('exportpdf', compact('invoice','company'));
        return $pdf->download("invoice-$id.pdf");
    }
}
