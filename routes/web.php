
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// ==========================================
// Route Halaman Utama
// ==========================================

Route::get('/', function () {
    return view('welcome');
});

Route::get('/latihan-php', function () {
    return view('latihan-php');
});

// ==========================================
// Route Form Mahasiswa (Praktikum Sebelumnya)
// ==========================================

Route::get('/form-mahasiswa', function () {
    return view('form-mahasiswa');
});

Route::post('/form-mahasiswa', function (Request $request) {
    return back()->with('berhasil', 'Form berhasil dikirim.');
});

// ==========================================
// Route Pertemuan 6: Read, Search, dan Filter
// ==========================================

Route::get('/mahasiswa', function (Request $request) {
    try {
        $pdo = DB::connection()->getPdo();

        $q = trim((string) $request->query('q', ''));
        $programStudiId = $request->query('program_studi_id');

        $sql = 'SELECT m.id, m.nim, m.nama, m.email, m.usia,
                       m.program_studi_id, p.nama_prodi
                FROM mahasiswa AS m
                JOIN program_studi AS p
                    ON p.id = m.program_studi_id
                WHERE 1 = 1';

        $parameter = [];

        if ($q !== '') {
            $sql .= ' AND (
                m.nim LIKE :nim
                OR m.nama LIKE :nama
                OR m.email LIKE :email
            )';

            $parameter['nim'] = "%{$q}%";
            $parameter['nama'] = "%{$q}%";
            $parameter['email'] = "%{$q}%";
        }

        if ($programStudiId !== null && $programStudiId !== '') {
            $sql .= ' AND m.program_studi_id = :program_studi_id';
            $parameter['program_studi_id'] = $programStudiId;
        }

        $sql .= ' ORDER BY m.nim';

        $pernyataanMahasiswa = $pdo->prepare($sql);
        $pernyataanMahasiswa->execute($parameter);

        $daftarMahasiswa = $pernyataanMahasiswa->fetchAll(
            \PDO::FETCH_ASSOC
        );

        $pernyataanProdi = $pdo->prepare(
            'SELECT id, nama_prodi
             FROM program_studi
             ORDER BY nama_prodi'
        );

        $pernyataanProdi->execute();

        $daftarProgramStudi = $pernyataanProdi->fetchAll(
            \PDO::FETCH_ASSOC
        );

        return view('mahasiswa', compact(
            'daftarMahasiswa',
            'daftarProgramStudi',
            'q',
            'programStudiId'
        ));

    } catch (\Throwable $e) {
        report($e);

        return back()->with(
            'gagal',
            'Gagal mengambil data mahasiswa.'
        );
    }
})->name('mahasiswa.index');

// ==========================================
// Route Pertemuan 6: Create
// ==========================================

Route::post('/mahasiswa', function (Request $request) {
    $data = $request->validate([
        'nim' => 'required|string|max:10',
        'nama' => 'required|string|max:100',
        'email' => 'required|email|max:100',
        'usia' => 'required|integer|min:1|max:255',
        'program_studi_id' => 'required|integer',
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cek = $pdo->prepare(
            'SELECT COUNT(*)
             FROM mahasiswa
             WHERE nim = :nim OR email = :email'
        );

        $cek->execute([
            'nim' => $data['nim'],
            'email' => $data['email'],
        ]);

        if ($cek->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'NIM atau email sudah terdaftar.'
            );
        }

        $cekProdi = $pdo->prepare(
            'SELECT COUNT(*) FROM program_studi WHERE id = :id'
        );

        $cekProdi->execute([
            'id' => $data['program_studi_id'],
        ]);

        if ($cekProdi->fetchColumn() == 0) {
            return back()->withInput()->with(
                'gagal',
                'Program studi tidak ditemukan.'
            );
        }

        $insert = $pdo->prepare(
            'INSERT INTO mahasiswa
                (nim, nama, email, usia, program_studi_id,
                 created_at, updated_at)
             VALUES
                (:nim, :nama, :email, :usia, :program_studi_id,
                 NOW(), NOW())'
        );

        $insert->execute($data);

        return redirect()->route('mahasiswa.index')
            ->with('berhasil', 'Data mahasiswa berhasil ditambahkan.');

    } catch (\Throwable $e) {
        report($e);

        return back()->withInput()->with(
            'gagal',
            'Data mahasiswa gagal ditambahkan.'
        );
    }
})->name('mahasiswa.store');

// ==========================================
// Route Pertemuan 6: Edit
// ==========================================

Route::get('/mahasiswa/{id}/edit', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $pernyataan = $pdo->prepare(
            'SELECT id, nim, nama, email, usia, program_studi_id
             FROM mahasiswa
             WHERE id = :id'
        );

        $pernyataan->execute(['id' => $id]);

        $mahasiswa = $pernyataan->fetch(\PDO::FETCH_ASSOC);

        if (!$mahasiswa) {
            return redirect()->route('mahasiswa.index')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        $pernyataanProdi = $pdo->prepare(
            'SELECT id, nama_prodi
             FROM program_studi
             ORDER BY nama_prodi'
        );

        $pernyataanProdi->execute();

        $daftarProgramStudi = $pernyataanProdi->fetchAll(
            \PDO::FETCH_ASSOC
        );

        return view('mahasiswa-edit', compact(
            'mahasiswa',
            'daftarProgramStudi'
        ));

   } catch (\Throwable $e) {
    report($e);

    return response(
        'Error: ' . $e->getMessage(),
        500
    );
}
})->name('mahasiswa.edit');

// ==========================================
// Route Pertemuan 6: Update
// ==========================================

Route::put('/mahasiswa/{id}', function (Request $request, $id) {
    $data = $request->validate([
        'nim' => 'required|string|max:10',
        'nama' => 'required|string|max:100',
        'email' => 'required|email|max:100',
        'usia' => 'required|integer|min:1|max:255',
        'program_studi_id' => 'required|integer',
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cek = $pdo->prepare(
            'SELECT COUNT(*)
             FROM mahasiswa
             WHERE (nim = :nim OR email = :email)
             AND id <> :id'
        );

        $cek->execute([
            'nim' => $data['nim'],
            'email' => $data['email'],
            'id' => $id,
        ]);

        if ($cek->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'NIM atau email sudah digunakan mahasiswa lain.'
            );
        }

        $update = $pdo->prepare(
            'UPDATE mahasiswa
             SET nim = :nim,
                 nama = :nama,
                 email = :email,
                 usia = :usia,
                 program_studi_id = :program_studi_id,
                 updated_at = NOW()
             WHERE id = :id'
        );

        $data['id'] = $id;
        $update->execute($data);

        return redirect()->route('mahasiswa.index')
            ->with('berhasil', 'Data mahasiswa berhasil diperbarui.');

    } catch (\Throwable $e) {
        report($e);

        return back()->withInput()->with(
            'gagal',
            'Data mahasiswa gagal diperbarui.'
        );
    }
})->name('mahasiswa.update');

// ==========================================
// Route Pertemuan 6: Delete
// ==========================================

Route::delete('/mahasiswa/{id}', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $hapus = $pdo->prepare(
            'DELETE FROM mahasiswa WHERE id = :id'
        );

        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('mahasiswa.index')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        return redirect()->route('mahasiswa.index')
            ->with('berhasil', 'Data mahasiswa berhasil dihapus.');

    } catch (\Throwable $e) {
        report($e);

        return redirect()->route('mahasiswa.index')
            ->with('gagal', 'Data mahasiswa gagal dihapus.');
    }
})->name('mahasiswa.destroy');
