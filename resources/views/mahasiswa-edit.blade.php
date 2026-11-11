
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mahasiswa</title>
</head>
<body>
    <h1>Edit Data Mahasiswa</h1>

    @if ($errors->any())
        <ul style="color: red;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('mahasiswa.update', $mahasiswa['id']) }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label>NIM</label><br>
            <input type="text" name="nim" value="{{ old('nim', $mahasiswa['nim']) }}" required>
        </p>

        <p>
            <label>Nama</label><br>
            <input type="text" name="nama" value="{{ old('nama', $mahasiswa['nama']) }}" required>
        </p>

        <p>
            <label>Email</label><br>
            <input type="email" name="email" value="{{ old('email', $mahasiswa['email']) }}" required>
        </p>

        <p>
            <label>Usia</label><br>
            <input type="number" name="usia" value="{{ old('usia', $mahasiswa['usia']) }}" required>
        </p>

        <p>
            <label>Program Studi</label><br>
            <select name="program_studi_id" required>
                @foreach ($daftarProgramStudi as $programStudi)
                    <option value="{{ $programStudi['id'] }}"
                        {{ old('program_studi_id', $mahasiswa['program_studi_id']) == $programStudi['id'] ? 'selected' : '' }}>
                        {{ $programStudi['nama_prodi'] }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Update</button>
        <a href="{{ route('mahasiswa.index') }}">Kembali</a>
    </form>
</body>
</html>
