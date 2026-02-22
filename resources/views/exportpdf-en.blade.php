<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
            font-size: 12px;
        }

        .card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }

        .header-table {
            width: 100%;
        }

        .logo {
            width: 120px;
            aspect-ratio: 1/1
        }

        .title {
            background: black;
            color: white;
            font-size: 24px;
            font-weight: bold;
            padding: 4px;
            text-align: center;
            margin-top: 1em;
        }

        .hr {
            border-top: 1px dashed #999;
            margin: 10px 0;
        }

        .box {
            border: 1px solid #000;
        }

        .box-header {
            background: black;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 4px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th, .table td {
            font-size: 12px;
            border: 1px solid #000;
            padding: 6px;
        }

        .bg-black {
            background: black;
            color: white;
        }

        .bg-green {
            background: #22c55e;
            color: white;
        }

        .bg-red {
            background: #ef4444;
            color: white;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .small {
            font-size: 10px;
        }
    </style>
</head>

<body>
<div class="card">

    <!-- HEADER -->
    <table>
        <thead>
        <tr>
            <td>
                <img src="{{ public_path(str_replace(asset(''), '', $company->img_url)) }}" class="logo"><br>
            </td>
            <td style="padding-left: 1em">
                <b style="font-size: 2em;">{{ $company->name }}</b>
                <br>
                <span style="font-size: 1em;border-top:2px solid;width: fit-content">{{ $company->address }}</span>
                <span style="font-size: 1em">{{ $company->phone }}</span>
                <span style="font-size: 1em">{{ $company->email }}</span>
            </td>
        </tr>
        </thead>
    </table>
    <div class="title" style="width: 100%">{{$invoice->category}}</div>

    <div class="hr"></div>

    <!-- CHARGED TO + META -->
    <table width="100%" cellspacing="6">
        <tr>
            <td width="50%" class="box">
                <div class="box-header">Charged To</div>
                <div style="padding:6px">
                    <b>{{ $invoice->to }}</b><br>
                    {{ $invoice->recipient_address }}<br>
                    {{ $invoice->payment_number }}
                </div>
            </td>
            @if($invoice->category == 'INVOICE')
            <td width="50%" class="box">
                <table width="100%">
                    <tr>
                        <td class="bg-black" style="font-weight: bold">Invoice Number</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="bg-black" style="font-weight: bold">Date</td>
                        <td>{{ $invoice->invoice_date }}</td>
                    </tr>
                    @if($invoice->due_date)
                        <tr>
                            <td class="bg-black" style="font-weight: bold">Due Date</td>
                            <td>{{ $invoice->due_date }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="bg-black" style="font-weight: bold">Status</td>
                        <td class="{{ $invoice->paid ? 'bg-green' : 'bg-red' }}">
                            {{ $invoice->paid ? 'Paid' : 'Unpaid' }}
                        </td>
                    </tr>
                </table>
            </td>
            @else
                <td width="50%" class="box">
                    <table width="100%">
                        <tr>
                            <td class="bg-black" style="font-weight: bold">PO Number</td>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="bg-black" style="font-weight: bold">Date</td>
                            <td>{{ $invoice->invoice_date }}</td>
                        </tr>
                    </table>
                </td>
            @endif
        </tr>
    </table>

    @if($invoice->category == 'PURCHASE ORDER')
        <p class="small">
            This letter is created as proof of Purchasing Order for goods/services received by us with the following items:
        </p>
    @endif

    <!-- ITEMS -->
    <table class="table">
        <thead>
        <tr class="bg-black">
            <th width="5%">No</th>
            <th>Name</th>
            <th>Code</th>
            <th width="20%">Price</th>
            <th width="10%">Qty</th>
            <th width="20%">Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoice->details as $i => $item)
            <tr>
                <td class="text-center">{{ $i+1 }}</td>
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->item_code }}</td>
                <td class="text-right" style="text-wrap: nowrap">
                    Rp {{ number_format($item->item_price,2,',','.') }}</td>
                <td class="text-center">{{ $item->item_qty }}</td>
                <td class="text-right" style="text-wrap: nowrap">
                    Rp {{ number_format($item->total_price,2,',','.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center">Empty Data</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- TOTAL -->
    <table class="table">
        <tr>
            <td colspan="5" style="font-weight: bold">Total</td>
            <td class="text-right">Rp {{ number_format($invoice->total,2,',','.') }}</td>
        </tr>
        <tr>
            <td colspan="5" style="font-weight: bold">Tax</td>
            <td class="text-right">{{ $invoice->tax }}%</td>
        </tr>
        <tr>
            <td colspan="5" style="font-weight: bold">Total Tax</td>
            <td class="text-right">
                + Rp {{ number_format(($invoice->tax/100)*$invoice->total,2,',','.') }}
            </td>
        </tr>
        <tr>
            <td colspan="5" style="font-weight: bold">Total Payment</td>
            <td class="bg-green text-right">
                Rp {{ number_format($invoice->total_payment,2,',','.') }}
            </td>
        </tr>
        @if($invoice->category == 'INVOICE')
            <tr>
                <td colspan="5" style="font-weight: bold">Payment To</td>
                <td class="text-right">
                    {{$invoice->payment_number ?? '-'}}
                </td>
            </tr>
        @endif
    </table>

    <div class="hr"></div>

    <p class="small">
        *This {{$invoice->category}} generated by system, Manual signature is not necessary
    </p>

</div>
</body>
</html>
