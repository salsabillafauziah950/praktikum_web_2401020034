<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        $k = Kategori::where('nama', 'Pemrograman')->firstOrFail();
        Buku::firstOrCreate(['kode' => 'BK001'],
            ['judul' => 'Dasar PHP', 'kategori_id' => $k->id]);
        Buku::firstOrCreate(['kode' => 'BK002'],
            ['judul' => 'Belajar Laravel', 'kategori_id' => $k->id]);
    }
}