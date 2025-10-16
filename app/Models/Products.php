<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model implements AuditableContract
{
    use HasFactory;
    use Auditable;
    use SoftDeletes;
    protected $fillable = [
        'product_name',
        'product_desc',
        'product_image',
        'product_barcode',
        'product_cost_price',
        'product_quantity',
        'product_selling_price',
        'category_id',
        'iva_type_id',
        'brand_id',
    ];


    public function category()
    {
        return $this->belongsTo(Categories::class);
    }

    public function ivaType()
    {
        return $this->belongsTo(IvaType::class);
    }

    public function ProductsProvider()
    {
        return $this->belongsTo(ProductsProvider::class);
    }

    public function salesDetails()
    {
        return $this->hasMany(SalesDetails::class,'product_id','id');
    }

    public function purchaseDetails(){
        return $this->hasMany(PurchasesDetails::class,'product_id','id');
    }

}
