<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $guarded = ['id'];
    use HasFactory;


    public function details()
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id', 'id');
    }
}
