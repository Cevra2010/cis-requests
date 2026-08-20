<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, CisUuid, SoftDeletes;

    protected $fillable = ['name', 'category_id'];

    // ── Relationships ────────────────────────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function prices()
    {
        return $this->hasMany(Price::class, 'cis_row_id_product', 'cis_row_id');
    }

    /** Unterprodukte (Kinder) dieses Produkts */
    public function childs()
    {
        return $this->belongsToMany(
            Product::class,
            'product_child',
            'cis_row_id_parent',
            'cis_row_id_child',
            'cis_row_id',
            'cis_row_id'
        );
    }

    /** Eltern dieses Produkts (Produkte, deren Unterprodukt dieses ist) */
    public function parents()
    {
        return $this->belongsToMany(
            Product::class,
            'product_child',
            'cis_row_id_child',
            'cis_row_id_parent',
            'cis_row_id',
            'cis_row_id'
        );
    }

    public function parameters()
    {
        return $this->hasMany(ProductParameter::class, 'cis_row_id_product', 'cis_row_id');
    }

    // ── Preis-Helfer ─────────────────────────────────────────────────────────

    public function price(): ?Price
    {
        return $this->prices()->with('source')->orderByDesc('created_at')->first();
    }

    public function priceForHumans(): string
    {
        $p = $this->price();
        if (! $p) {
            return '– Preis nicht gesetzt –';
        }
        return number_format($p->amount, 2, ',', '.') . ' €';
    }

    public function getGroupPrice(): float
    {
        $amount = 0.0;
        foreach ($this->getChild() as $child) {
            if ($child->price()) {
                $amount += $child->price()->amount;
            }
        }
        if ($this->price() && $this->price()->amount != 0) {
            $amount += $this->price()->amount;
        }
        return $amount;
    }

    public function getGroupPriceForHumans(): string
    {
        return number_format($this->getGroupPrice(), 2, ',', '.') . ' €';
    }

    public function newPrice(float|string $amount): void
    {
        $amount = str_replace(',', '.', $amount);
        $price = new Price();
        $price->cis_row_id_product = $this->cis_row_id;
        $price->amount = $amount;
        $this->updated_at = Carbon::now();
        $this->save();
        $price->save();
    }

    // ── Eltern/Kind-Helfer ───────────────────────────────────────────────────

    public function hasParent(): bool
    {
        return $this->parents()->exists();
    }

    public function getParent(): ?Product
    {
        return $this->parents()->first();
    }

    /** Alle Elternprodukte – ein Unterprodukt kann mehreren Produkten zugeordnet sein. */
    public function getParents()
    {
        return $this->parents()->get();
    }

    public function hasChild(): bool
    {
        return $this->childs()->exists();
    }

    public function getChild()
    {
        return $this->childs()->get();
    }

    // ── Beschreibung ─────────────────────────────────────────────────────────

    public function description(): ?ProductDescription
    {
        return ProductDescription::where('cis_row_id_product', $this->cis_row_id)->first();
    }
}
