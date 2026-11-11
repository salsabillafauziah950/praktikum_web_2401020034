<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        Kategori::firstOrCreate(['nama' => 'Pemrograman']);
        Kategori::firstOrCreate(['nama' => 'Basis Data']);
    }
}