<?php

namespace App\Models;

use Database\Factories\WpSiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpSite extends Model
{
    /** @use HasFactory<WpSiteFactory> */
    use HasFactory;

    protected $fillable = ['name', 'path'];
}
