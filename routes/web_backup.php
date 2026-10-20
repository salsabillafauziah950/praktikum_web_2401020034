
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

// ===== Route Pertemuan 1 =====

Route::get('/', function () {
    return view('welcome');
});

// ===== Route Pertemuan 2 =====

Route::get('/latihan-php', function () {
    $nama = 'Siti Umayah';
    $nilai = [80, 75, 90, 65, 88];

    $hitungRataRata = function (array $data): float {
        $total = 0;

        foreach ($data as $angka) {
            $total += $angka;
        }

        return $total / count($data);
    };

    $rataRata = $hitungRataRata($nilai);
    $status = $rataRata >= 75 ? 'Lulus' : 'Perlu Perbaikan';

    return view('latihan-php', compact(
        'nama',
        'nilai',
        'rataRata',
        'status'
    ));
});

// ===== Route Pertemuan 3 =====

Route::get('/form-mahasiswa', function () {
    return view('form-mahasiswa');
});

Route::post('/form-mahasiswa', function (Request $request) {
    $dataBersih = [
        'nama' => strip_tags(trim((string) $request->input('nama'))),
        'email' => filter_var(
            (string) $request->input('email'),
            FILTER_SANITIZE_EMAIL
        ),
        'usia' => trim((string) $request->input('usia')),
        'nim' => trim((string) $request->input('nim')),
    ];

    $validator = Validator::make($dataBersih, [
        'nama' => ['required', 'min:3', 'max:50'],
        'email' => ['required', 'email'],
        'usia' => ['required', 'integer', 'min:17', 'max:60'],
        'nim' => ['required', 'digits_between:8,12'],
    ], [
        'nama.required' => 'Nama wajib diisi.',
        'nama.min' => 'Nama minimal 3 karakter.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'usia.required' => 'Usia wajib diisi.',
        'usia.integer' => 'Usia harus berupa angka.',
        'usia.min' => 'Usia minimal 17 tahun.',
        'usia.max' => 'Usia maksimal 60 tahun.',
        'nim.required' => 'NIM wajib diisi.',
        'nim.digits_between' => 'NIM harus berupa angka 8-12 digit.',
    ]);

    if ($validator->fails()) {
        return redirect('/form-mahasiswa')
            ->withErrors($validator)
            ->withInput();
    }

    $data = $validator->validated();
    $data['usia'] = (int) $data['usia'];

    return view('hasil-form', ['data' => $data]);
});

// ===== Modul 6 dan 7: Read, Search, Filter =====

Route::get('/mahasiswa', function (Request $request) {
    try {
        $pdo = DB::connection()->getPdo();

        $q = trim((string) $request->query('q', ''));
        $programStudiId = $request->query('program_studi_id');

        $sql = "
            SELECT m.id, m.nim, m.nama, m.email, m.usia,
                   m.program_studi_id, p.nama_prodi
            FROM mahasiswa AS m
            JOIN program_studi AS p
                ON p.id = m.program_studi_id
            WHERE 1 = 1
        ";

        $params = [];

        if ($q !== '') {
            $sql .= "
                AND (
                    m.nim LIKE :nim
                    OR m.nama LIKE :nama
                    OR m.email LIKE :email
                )
            ";

            $keyword = '%' . $q . '%';
            $params['nim'] = $keyword;
            $params['nama'] = $keyword;
            $params['email'] = $keyword;
        }

        if ($programStudiId !== null && $programStudiId !== '') {
            $sql .= " AND m.program_studi_id = :program_studi_id";
            $params['program_studi_id'] = $programStudiId;
        }

        $sql .= " ORDER BY m.nim";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $daftarMahasiswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtProdi = $pdo->prepare("
            SELECT id, nama_prodi
            FROM program_studi
            ORDER BY nama_prodi
        ");
        $stmtProdi->execute();
        $daftarProgramStudi = $stmtProdi->fetchAll(PDO::FETCH_ASSOC);

        return view('mahasiswa', compact(
            'daftarMahasiswa',
            'daftarProgramStudi',
            'q',
            'programStudiId'
        ));
    } catch (\Throwable $e) {
        report($e);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->name('mahasiswa.index');

// ===== Modul 6: Create =====

Route::post('/mahasiswa', function (Request $request) {
    $data = $request->validate([
        'nim' => ['required', 'string', 'max:10'],
        'nama' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100'],
        'usia' => ['required', 'integer', 'min:15', 'max:100'],
        'program_studi_id' => [
            'required',
            'integer',
            'exists:program_studi,id',
        ],
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cek = $pdo->prepare("
            SELECT COUNT(*)
            FROM mahasiswa
            WHERE nim = :nim OR email = :email
        ");

        $cek->execute([
            'nim' => $data['nim'],
            'email' => $data['email'],
        ]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'NIM atau email sudah digunakan.'
            );
        }

        $simpan = $pdo->prepare("
            INSERT INTO mahasiswa
                (nim, nama, email, usia, program_studi_id)
            VALUES
                (:nim, :nama, :email, :usia, :program_studi_id)
        ");

        $simpan->execute([
            'nim' => $data['nim'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'usia' => $data['usia'],
            'program_studi_id' => $data['program_studi_id'],
        ]);

        return redirect()->route('mahasiswa.index')
            ->with('berhasil', 'Data mahasiswa berhasil ditambahkan.');
    } catch (\Throwable $e) {
        report($e);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->name('mahasiswa.store');

// ===== Modul 7: Edit =====

Route::get('/mahasiswa/{id}/edit', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare("
            SELECT id, nim, nama, email, usia, program_studi_id
            FROM mahasiswa
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mahasiswa) {
            return redirect()->route('mahasiswa.index')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        $stmtProdi = $pdo->prepare("
            SELECT id, nama_prodi
            FROM program_studi
            ORDER BY nama_prodi
        ");
        $stmtProdi->execute();
        $daftarProgramStudi = $stmtProdi->fetchAll(PDO::FETCH_ASSOC);

        return view('mahasiswa-edit', compact(
            'mahasiswa',
            'daftarProgramStudi'
        ));
    } catch (\Throwable $e) {
        report($e);

        return response($e->getMessage(), 500);
    }
})->name('mahasiswa.edit');

// ===== Modul 7: Update =====

Route::put('/mahasiswa/{id}', function (Request $request, $id) {
    $data = $request->validate([
        'nim' => ['required', 'string', 'max:10'],
        'nama' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100'],
        'usia' => ['required', 'integer', 'min:15', 'max:100'],
        'program_studi_id' => [
            'required',
            'integer',
            'exists:program_studi,id',
        ],
    ]);

    try {
        $pdo = DB::connection()->getPdo();

        $cek = $pdo->prepare("
            SELECT COUNT(*)
            FROM mahasiswa
            WHERE (nim = :nim OR email = :email)
              AND id != :id
        ");
        $cek->execute([
            'nim' => $data['nim'],
            'email' => $data['email'],
            'id' => $id,
        ]);

        if ((int) $cek->fetchColumn() > 0) {
            return back()->withInput()->with(
                'gagal',
                'NIM atau email sudah digunakan mahasiswa lain.'
            );
        }

        $update = $pdo->prepare("
            UPDATE mahasiswa
            SET nim = :nim,
                nama = :nama,
                email = :email,
                usia = :usia,
                program_studi_id = :program_studi_id
            WHERE id = :id
        ");
        $update->execute([
            'nim' => $data['nim'],
            'nama' => $data['nama'],
            'email' => $data['email'],
            'usia' => $data['usia'],
            'program_studi_id' => $data['program_studi_id'],
            'id' => $id,
        ]);

        $cekId = $pdo->prepare(
            'SELECT COUNT(*) FROM mahasiswa WHERE id = :id'
        );
        $cekId->execute(['id' => $id]);

        if ((int) $cekId->fetchColumn() === 0) {
            return redirect()->route('mahasiswa.index')
                ->with('gagal', 'Data mahasiswa tidak ditemukan.');
        }

        return redirect()->route('mahasiswa.index')
            ->with('berhasil', 'Data mahasiswa berhasil diperbarui.');
    } catch (\Throwable $e) {
        report($e);

        return back()->withInput()->with(
            'gagal',
            'Data gagal diperbarui. Periksa koneksi dan data.'
        );
    }
})->name('mahasiswa.update');

// ===== Modul 7: Delete =====

Route::delete('/mahasiswa/{id}', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $hapus = $pdo->prepare(
            'DELETE FROM mahasiswa WHERE id = :id'
        );
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('mahasiswa.index')
                ->with('gagal', 'Data tidak ditemukan atau sudah dihapus.');
        }

        return redirect()->route('mahasiswa.index')
            ->with('berhasil', 'Data mahasiswa berhasil dihapus.');
    } catch (\Throwable $e) {
        report($e);

        return redirect()->route('mahasiswa.index')
            ->with('gagal', 'Data gagal dihapus.');
    }
})->name('mahasiswa.destroy');
